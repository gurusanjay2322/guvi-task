<?php
// php/update_profile.php
require_once 'helpers.php';
require_once 'db.php';
require_once 'redis.php';
require_once 'mongo.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? null;
if (!$token) { echo json_encode(['success'=>false,'message'=>'Missing token']); exit; }

$redisKey = "session:$token";
$session = $redis->get($redisKey);
if (!$session) { echo json_encode(['success'=>false,'message'=>'Invalid or expired session']); exit; }
$sessionData = json_decode($session, true);
$userId = (int)$sessionData['user_id'];

// Allowed updates: age, dob, contact
<?php
// php/update_profile.php
require_once 'helpers.php';
require_once 'db.php';
require_once 'redis.php';
require_once 'mongo.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? null;
if (!$token) { echo json_encode(['success'=>false,'message'=>'Missing token']); exit; }

$redisKey = "session:$token";
$session = $redis->get($redisKey);
if (!$session) { echo json_encode(['success'=>false,'message'=>'Invalid or expired session']); exit; }
$sessionData = json_decode($session, true);
$userId = (int)$sessionData['user_id'];

$age = isset($input['age']) ? (int)$input['age'] : null;
$dob = $input['dob'] ?? null;
$contact = $input['contact'] ?? null;

$updateStmt = $pdo->prepare('UPDATE users SET age = :age, dob = :dob, contact = :contact WHERE id = :id');
try {
    $updateStmt->execute([
        ':age' => $age,
        ':dob' => $dob ?: null,
        ':contact' => $contact ?: null,
        ':id' => $userId
    ]);

    // Update mongo backup (best-effort)
    try {
        $col = $mongodb->selectCollection('users_backup');
        $updateMongo = [];
        foreach (['age', 'dob', 'contact'] as $key) {
            if (isset($input[$key])) $updateMongo[$key] = $input[$key];
        }
        if ($updateMongo) {
            $updateMongo['updated_at'] = new MongoDB\BSON\UTCDateTime();
            $col->updateOne(['user_id' => $userId], ['$set' => $updateMongo]);
        }

    } catch (Exception $e) {
        // ignore
    }

    echo json_encode(['success'=>true,'message'=>'Profile updated.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Update failed.']);
}


$updateStmt = $pdo->prepare('UPDATE users SET age = :age, dob = :dob, contact = :contact WHERE id = :id');
try {
    $updateStmt->execute([
        ':age' => $age,
        ':dob' => $dob ?: null,
        ':contact' => $contact ?: null,
        ':id' => $userId
    ]);

    // Update mongo backup (best-effort)
    try {
        $col = $mongodb->selectCollection('users_backup');
        $col->updateOne(['user_id' => $userId], ['$set' => [
            'age' => $age,
            'dob' => $dob,
            'contact' => $contact,
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ]]);
    } catch (Exception $e) {
        // ignore
    }

    echo json_encode(['success'=>true,'message'=>'Profile updated.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Update failed.']);
}
