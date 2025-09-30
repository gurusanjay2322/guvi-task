<?php
// php/login.php
require_once 'helpers.php';
require_once 'db.php';
require_once 'redis.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['success'=>false,'message'=>'Invalid JSON']); exit; }

$identifier = trim($input['identifier'] ?? ''); // username or email
$password = $input['password'] ?? '';

if (!$identifier || !$password) {
    echo json_encode(['success'=>false,'message'=>'Identifier and password required.']);
    exit;
}

// Fetch user
$stmt = $pdo->prepare('SELECT id, username, email, password_hash FROM users WHERE username = :identifier OR email = :identifier2 LIMIT 1');
$stmt->execute([
    ':identifier' => $identifier,
    ':identifier2' => $identifier
]);

$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success'=>false,'message'=>'Invalid credentials.']);
    exit;
}

// Verify
if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid credentials.']);
    exit;
}

// Create session token
$token = bin2hex(random_bytes(32));
$sessionData = json_encode(['user_id' => (int)$user['id'], 'username' => $user['username']]);

// store in redis
$redisKey = "session:$token";
$ok = $redis->setex($redisKey, $redisTtl, $sessionData);
if (!$ok) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Could not create session.']);
    exit;
}

// Return token and minimal profile
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'token' => $token,
    'user' => ['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email']]
]);
