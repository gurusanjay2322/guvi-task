<?php
// php/redis.php
$redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
$redisPort = getenv('REDIS_PORT') ?: 6379;
$redisTtl  = intval(getenv('REDIS_TTL') ?: 86400);

$redis = new Redis();
try {
    $redis->connect($redisHost, $redisPort);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Redis connection failed.']);
    exit;
}
