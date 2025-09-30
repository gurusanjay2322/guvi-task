<?php
require_once 'helpers.php';
require_once 'redis.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? null;
if (!$token) { echo json_encode(['success'=>false,'message'=>'Missing token']); exit; }

$redisKey = "session:$token";
$redis->del($redisKey);
echo json_encode(['success'=>true,'message'=>'Logged out.']);
