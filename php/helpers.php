<?php
// php/helpers.php
header('Content-Type: application/json; charset=utf-8');
// Allow AJAX from your frontend during dev — adjust domain in production.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
