<?php
require_once 'helpers.php';
require_once 'db.php';
require_once 'redis.php';
require_once 'mongo.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? null;
if (!$token) {
    echo json_encode(['success'=>false,'message'=>'Missing token']);
    exit;
}

$redisKey = "session:$token";
$session = $redis->get($redisKey);
if (!$session) {
    echo json_encode(['success'=>false,'message'=>'Invalid or expired session']);
    exit;
}
$sessionData = json_decode($session, true);
$userId = (int)$sessionData['user_id'];

$fields = [];
$params = [':id' => $userId];

if (isset($input['age'])) {
    $age = (int)$input['age'];
    if ($age < 1 || $age > 120) {
        echo json_encode(['success'=>false,'message'=>'Invalid age.']);
        exit;
    }
    $fields[] = "age = :age";
    $params[':age'] = $age;
}
if (isset($input['dob'])) {
    $dob = $input['dob'];
    if (!empty($dob)) {
        $d = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$d || $d->format('Y-m-d') !== $dob) {
            echo json_encode(['success'=>false,'message'=>'Invalid date of birth.']);
            exit;
        }
        $minDob = new DateTime('today');
        $minDob->modify('-10 years');
        if ($d > $minDob) {
            echo json_encode(['success'=>false,'message'=>'Invalid date of birth.']);
            exit;
        }
    }
    $fields[] = "dob = :dob";
    $params[':dob'] = $dob ?: null;
}
if (isset($input['contact'])) {
    $contact = trim($input['contact']);
    if (!empty($contact) && !preg_match('/^[0-9+()\-\s]{7,20}$/', $contact)) {
        echo json_encode(['success'=>false,'message'=>'Invalid contact number.']);
        exit;
    }
    $fields[] = "contact = :contact";
    $params[':contact'] = $contact ?: null;
}

if (empty($fields)) {
    echo json_encode(['success'=>false,'message'=>'No fields to update']);
    exit;
}

$sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
$updateStmt = $pdo->prepare($sql);

try {
    $updateStmt->execute($params);

    try {
        $col = $mongodb->selectCollection('users_backup');
        $updateMongo = [];
        foreach (['age','dob','contact'] as $key) {
            if (isset($input[$key])) $updateMongo[$key] = $input[$key];
        }
        if ($updateMongo) {
            $updateMongo['updated_at'] = new MongoDB\BSON\UTCDateTime();
            $col->updateOne(['user_id' => $userId], ['$set' => $updateMongo]);
        }
    } catch (Exception $e) {
    }

    echo json_encode(['success'=>true,'message'=>'Profile updated.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Update failed.']);
}
