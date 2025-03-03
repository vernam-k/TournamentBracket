<?php
/**
 * Tournaments API Endpoint
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
    'create',
    'update',
    'delete',
    'start',
    'add_participant',
    'remove_participant',
    'get_bracket_data',
    'get_standings'
];

// Check if the action is allowed
if (!in_array($action, $allowed_actions)) {
    send_json_response(['error' => 'Invalid action'], 400);
}

// Handle the action
switch ($action) {
    case 'list':
        // Get all tournaments
        $tournaments = get_all_tournaments();
        send_json_response(['tournaments' => $tournaments]);
        break;
        
    case 'get':
        // Get a specific tournament
        $tournament_id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($tournament_id)) {
            send_json_response(['error' => 'Tournament ID is required'], 400);
        }
        
        $tournament = get_tournament($tournament_id);
        
        if (!$tournament) {
            send_json_response(['error' => 'Tournament not found'], 404);
        }
        
        send_json_response(['tournament' => $tournament]);
        break;
        
    case 'create':
        // Create a new tournament (admin only)
        if (!is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
        $type = isset($_POST['type']) ? sanitize_input($_POST['type']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['name' => $name, 'type' => $type],
            ['name', 'type']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Validate tournament type
        $valid_types = ['single_elimination', 'double_elimination', 'round_robin'];
        if (!in_array($type, $valid_types)) {
            send_json_response(['error' => 'Invalid tournament type'], 400);
        }
        
        // Create the tournament
        $tournament_id = create_tournament($name, $type, $_SESSION['username']);
        
        if (!$tournament_id) {
            send_json_response(['error' => 'Failed to create tournament'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Tournament created successfully',
            'tournament_id' => $tournament_id
        ]);
        break;
        
    case 'update':
        // Update a tournament (admin only)
        if (!is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $tournament_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['id' => $tournament_id, 'name' => $name],
            ['id', 'name']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Get the tournament
        $tournament = get_tournament($tournament_id);
        
        if (!$tournament) {
            send_json_response(['error' => 'Tournament not found'], 404);
        }
        
        // Update the tournament
        $tournament['name'] = $name;
        
        if (!save_tournament($tournament)) {
            send_json_response(['error' => 'Failed to update tournament'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Tournament updated successfully'
        ]);
        break;
        
    case 'delete':
        // Delete a tournament (admin only)
        if (!is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $tournament_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        
        if (empty($tournament_id)) {
            send_json_response(['error' => 'Tournament ID is required'], 400);
        }
        
        // Delete the tournament
        if (!delete_tournament($tournament_id)) {
            send_json_response(['error' => 'Failed to delete tournament'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Tournament deleted successfully'
        ]);
        break;
        
    case 'start':
        // Start a tournament (admin only)
        if (!is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $tournament_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        
        if (empty($tournament_id)) {
            send_json_response(['error' => 'Tournament ID is required'], 400);
        }
        
        // Start the tournament
        if (!start_tournament($tournament_id)) {
            send_json_response(['error' => 'Failed to start tournament'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Tournament started successfully'
        ]);
        break;
        
    case 'add_participant':
        // Add a participant to a tournament (admin only)
        if (!is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $tournament_id = isset($_POST['tournament_id']) ? sanitize_input($_POST['tournament_id']) : '';
        $participant_id = isset($_POST['participant_id']) ? sanitize_input($_POST['participant_id']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['tournament_id' => $tournament_id, 'participant_id' => $participant_id],
            ['tournament_id', 'participant_id']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Add the participant
        if (!add_tournament_participant($tournament_id, $participant_id)) {
            send_json_response(['error' => 'Failed to add participant to tournament'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Participant added to tournament successfully'
        ]);
        break;
        
    case 'remove_participant':
        // Remove a participant from a tournament (admin only)
        if (!is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $tournament_id = isset($_POST['tournament_id']) ? sanitize_input($_POST['tournament_id']) : '';
        $participant_id = isset($_POST['participant_id']) ? sanitize_input($_POST['participant_id']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['tournament_id' => $tournament_id, 'participant_id' => $participant_id],
            ['tournament_id', 'participant_id']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Remove the participant
        if (!remove_tournament_participant($tournament_id, $participant_id)) {
            send_json_response(['error' => 'Failed to remove participant from tournament'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Participant removed from tournament successfully'
        ]);
        break;
        
    case 'get_bracket_data':
        // Get tournament bracket data for visualization
        $tournament_id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($tournament_id)) {
            send_json_response(['error' => 'Tournament ID is required'], 400);
        }
        
        $bracket_data = get_tournament_bracket_data($tournament_id);
        
        send_json_response(['bracket_data' => $bracket_data]);
        break;
        
    case 'get_standings':
        // Get tournament standings (for round robin)
        $tournament_id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($tournament_id)) {
            send_json_response(['error' => 'Tournament ID is required'], 400);
        }
        
        $tournament = get_tournament($tournament_id);
        
        if (!$tournament) {
            send_json_response(['error' => 'Tournament not found'], 404);
        }
        
        if ($tournament['type'] !== 'round_robin') {
            send_json_response(['error' => 'Standings are only available for round robin tournaments'], 400);
        }
        
        $standings = get_round_robin_standings($tournament_id);
        
        send_json_response(['standings' => $standings]);
        break;
        
    default:
        send_json_response(['error' => 'Invalid action'], 400);
}