<?php
// php/mongo.php
require_once realpath(__DIR__ . '/../vendor/autoload.php');
$mongoUri = getenv('MONGO_URI') ?: 'mongodb://127.0.0.1:27017';
$mongoDb  = getenv('MONGO_DB') ?: 'guvi_intern';

try {
    $manager = new MongoDB\Client($mongoUri);
    $mongodb = $manager->selectDatabase($mongoDb);
} catch (Exception $e) {
    // Do not die the whole app — log and continue (but inform).
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'MongoDB connection failed.']);
    exit;
}
