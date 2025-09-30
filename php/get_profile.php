<?php
require_once 'helpers.php';
require_once 'db.php';
require_once 'redis.php';

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
$token = null;
if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
    $token = $m[1];
} else {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $token = $input['token'] ?? null;
}

if (!$token) { echo json_encode(['success'=>false,'message'=>'Missing token']); exit; }

$redisKey = "session:$token";
$session = $redis->get($redisKey);
if (!$session) { echo json_encode(['success'=>false,'message'=>'Invalid or expired session']); exit; }
$sessionData = json_decode($session, true);
$userId = (int)$sessionData['user_id'];

$stmt = $pdo->prepare('SELECT id, username, email, age, dob, contact, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();
if (!$user) { echo json_encode(['success'=>false,'message'=>'User not found']); exit; }

echo json_encode(['success'=>true, 'user'=>$user]);
