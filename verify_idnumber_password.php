<?php
// This file is part of Moodle - http://moodle.org/

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');

require_login();
require_sesskey();

// Only process AJAX POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die();
}

$password = required_param('password', PARAM_RAW);

// Get configured password
$configured_password = get_config('quizaccess_faceid', 'idnumber_password');

// Response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if password protection is enabled (password is configured)
if (empty($configured_password)) {
    $response['success'] = false;
    $response['message'] = get_string('idnumberpassword_notconfigured', 'quizaccess_faceid');
} else if ($password === $configured_password) {
    // Password matches
    $response['success'] = true;
    $response['message'] = get_string('idnumberpassword_correct', 'quizaccess_faceid');
} else {
    // Password does not match
    $response['success'] = false;
    $response['message'] = get_string('idnumberpassword_incorrect', 'quizaccess_faceid');
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
