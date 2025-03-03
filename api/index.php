<?php
/**
 * API Entry Point
 */

// Include configuration
require_once '../config.php';

// Include required files
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/tournament.php';
require_once INCLUDES_PATH . '/utils.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize database if needed
init_database();

// Start session
start_session();

// Get the requested endpoint
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Define allowed endpoints and their handlers
$allowed_endpoints = [
    'tournaments' => 'tournaments.php',
    'matches' => 'matches.php',
    'participants' => 'participants.php',
    'users' => 'users.php',
    'predictions' => 'predictions.php',
    'statistics' => 'statistics.php'
];

// Check if the requested endpoint is allowed
if (!isset($allowed_endpoints[$endpoint])) {
    send_json_response(['error' => 'Invalid endpoint'], 400);
}

// Include the endpoint handler
include $allowed_endpoints[$endpoint];

// If we reach here, the endpoint handler didn't send a response
send_json_response(['error' => 'No response from endpoint handler'], 500);