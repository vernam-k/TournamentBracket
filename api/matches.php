<?php
/**
 * Matches API Endpoint
 */

// Check if this file is included from the API entry point
if (!defined('ROOT_PATH')) {
    exit('Direct access not allowed');
}

// Get the action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Define allowed actions
$allowed_actions = [
    'list',
    'get',
    'update_score'
];

// Check if the action is allowed
if (!in_array($action, $allowed_actions)) {
    send_json_response(['error' => 'Invalid action'], 400);
}

// Handle the action
switch ($action) {
    case 'list':
        // Get matches for a tournament
        $tournament_id = isset($_GET['tournament_id']) ? $_GET['tournament_id'] : '';
        
        if (empty($tournament_id)) {
            send_json_response(['error' => 'Tournament ID is required'], 400);
        }
        
        $matches = get_tournament_matches($tournament_id);
        send_json_response(['matches' => $matches]);
        break;
        
    case 'get':
        // Get a specific match
        $match_id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($match_id)) {
            send_json_response(['error' => 'Match ID is required'], 400);
        }
        
        $match = get_match($match_id);
        
        if (!$match) {
            send_json_response(['error' => 'Match not found'], 404);
        }
        
        send_json_response(['match' => $match]);
        break;
        
    case 'update_score':
        // Update match score (admin only)
        if (!is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $match_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        $score1 = isset($_POST['score1']) ? intval($_POST['score1']) : 0;
        $score2 = isset($_POST['score2']) ? intval($_POST['score2']) : 0;
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['id' => $match_id],
            ['id']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Validate scores
        if ($score1 < 0 || $score2 < 0) {
            send_json_response(['error' => 'Scores cannot be negative'], 400);
        }
        
        // Update the match score
        if (!update_match_score($match_id, $score1, $score2)) {
            send_json_response(['error' => 'Failed to update match score'], 500);
        }
        
        // Get the updated match
        $match = get_match($match_id);
        
        send_json_response([
            'success' => true,
            'message' => 'Match score updated successfully',
            'match' => $match
        ]);
        break;
        
    default:
        send_json_response(['error' => 'Invalid action'], 400);
}