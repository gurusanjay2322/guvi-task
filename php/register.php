<?php
require_once 'helpers.php';
require_once 'db.php';
require_once 'mongo.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success'=>false, 'message'=>'Invalid JSON input.']);
    exit;
}

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$age = isset($input['age']) ? (int)$input['age'] : null;
$dob = $input['dob'] ?? null;
$contact = trim($input['contact'] ?? '');

if (!$username || !$email || !$password) {
    echo json_encode(['success'=>false, 'message'=>'username, email and password required.']);
    exit;
}

if (!preg_match('/^(?=.*[A-Za-z])[A-Za-z0-9_.]{3,30}$/', $username)) {
    echo json_encode(['success'=>false, 'message'=>'Invalid username.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    echo json_encode(['success'=>false, 'message'=>'Invalid email.']);
    exit;
}

$pwdLen = strlen((string)$password);
if ($pwdLen < 8 || $pwdLen > 128) {
    echo json_encode(['success'=>false, 'message'=>'Invalid password length.']);
    exit;
}

if (!is_null($age) && ($age < 1 || $age > 120)) {
    echo json_encode(['success'=>false, 'message'=>'Invalid age.']);
    exit;
}

if (!empty($dob)) {
    $d = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$d || $d->format('Y-m-d') !== $dob) {
        echo json_encode(['success'=>false, 'message'=>'Invalid date of birth.']);
        exit;
    }
    $minDob = new DateTime('today');
    $minDob->modify('-10 years');
    if ($d > $minDob) {
        echo json_encode(['success'=>false, 'message'=>'Invalid date of birth.']);
        exit;
    }
}

if (!empty($contact) && !preg_match('/^[0-9+()\-\s]{7,20}$/', $contact)) {
    echo json_encode(['success'=>false, 'message'=>'Invalid contact number.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1');
$stmt->execute([':u' => $username, ':e' => $email]);
if ($stmt->fetch()) {
    echo json_encode(['success'=>false, 'message'=>'Username or email already exists.']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();
    $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, age, dob, contact) VALUES (:username, :email, :ph, :age, :dob, :contact)');
    $insert->execute([
        ':username' => $username,
        ':email' => $email,
        ':ph' => $hash,
        ':age' => $age,
        ':dob' => $dob ?: null,
        ':contact' => $contact ?: null
    ]);
    $userId = $pdo->lastInsertId();

    try {
        $col = $mongodb->selectCollection('users_backup');
        $col->insertOne([
            'user_id' => (int)$userId,
            'username' => $username,
            'email' => $email,
            'age' => $age,
            'dob' => $dob,
            'contact' => $contact,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);
    } catch (Exception $e) {
    }

    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>'User registered successfully.']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Registration failed.']);
}
