<?php
// php/register.php
require_once 'helpers.php';
require_once 'db.php';
require_once 'mongo.php'; // needs composer autoload or include

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

// Check if user/email exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1');
$stmt->execute([':u' => $username, ':e' => $email]);
if ($stmt->fetch()) {
    echo json_encode(['success'=>false, 'message'=>'Username or email already exists.']);
    exit;
}

// Hash password
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

    // Mongo backup (non-blocking idea: push to a queue, but here we do directly)
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
        // If mongo fails, we can still commit MySQL but log the issue.
        // For the assignment keep it simple: continue.
    }

    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>'User registered successfully.']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Registration failed.']);
}
