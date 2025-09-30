<?php
// Test Redis Connection
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Set a test value
$redis->set('test_key', 'Hello, Redis!');

// Get the test value
$value = $redis->get('test_key');
echo "Test Key Value: " . $value;
?>
