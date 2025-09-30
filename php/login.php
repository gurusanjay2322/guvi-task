<?php
require_once 'helpers.php';
require_once 'db.php';
require_once 'redis.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['success'=>false,'message'=>'Invalid JSON']); exit; }

$identifier = trim($input['identifier'] ?? '');
$password = $input['password'] ?? '';

if (!$identifier || !$password) {
    echo json_encode(['success'=>false,'message'=>'Identifier and password required.']);
    exit;
}

if (strlen($identifier) < 3 || strlen($identifier) > 254) {
    echo json_encode(['success'=>false,'message'=>'Invalid identifier.']);
    exit;
}

$pwdLen = strlen((string)$password);
if ($pwdLen < 8 || $pwdLen > 128) {
    echo json_encode(['success'=>false,'message'=>'Invalid password length.']);
    exit;
}

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

if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid credentials.']);
    exit;
}

$token = bin2hex(random_bytes(32));
$sessionData = json_encode(['user_id' => (int)$user['id'], 'username' => $user['username']]);

$redisKey = "session:$token";
$ok = $redis->setex($redisKey, $redisTtl, $sessionData);
if (!$ok) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Could not create session.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'token' => $token,
    'user' => ['id' => (int)$user['id'], 'username' => $user['username'], 'email' => $user['email']]
]);
