<?php
/**
 * Participants API Endpoint
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
    'delete'
];

// Check if the action is allowed
if (!in_array($action, $allowed_actions)) {
    send_json_response(['error' => 'Invalid action'], 400);
}

// Handle the action
switch ($action) {
    case 'list':
        // Get all participants
        $participants = get_all_participants();
        send_json_response(['participants' => $participants]);
        break;
        
    case 'get':
        // Get a specific participant
        $participant_id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($participant_id)) {
            send_json_response(['error' => 'Participant ID is required'], 400);
        }
        
        $participant = get_participant($participant_id);
        
        if (!$participant) {
            send_json_response(['error' => 'Participant not found'], 404);
        }
        
        send_json_response(['participant' => $participant]);
        break;
        
    case 'create':
        // Create a new participant (admin only)
        if (!is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['name' => $name],
            ['name']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Create the participant
        $participant_id = generate_id('p');
        $participant = [
            'id' => $participant_id,
            'name' => $name,
            'description' => $description,
            'created_at' => date('Y-m-d\TH:i:s\Z')
        ];
        
        if (!save_participant($participant)) {
            send_json_response(['error' => 'Failed to create participant'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Participant created successfully',
            'participant_id' => $participant_id
        ]);
        break;
        
    case 'update':
        // Update a participant (admin only)
        if (!is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $participant_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['id' => $participant_id, 'name' => $name],
            ['id', 'name']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Get the participant
        $participant = get_participant($participant_id);
        
        if (!$participant) {
            send_json_response(['error' => 'Participant not found'], 404);
        }
        
        // Update the participant
        $participant['name'] = $name;
        $participant['description'] = $description;
        
        if (!save_participant($participant)) {
            send_json_response(['error' => 'Failed to update participant'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Participant updated successfully'
        ]);
        break;
        
    case 'delete':
        // Delete a participant (admin only)
        if (!is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $participant_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        
        if (empty($participant_id)) {
            send_json_response(['error' => 'Participant ID is required'], 400);
        }
        
        // Delete the participant
        if (!delete_participant($participant_id)) {
            send_json_response(['error' => 'Failed to delete participant'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Participant deleted successfully'
        ]);
        break;
        
    default:
        send_json_response(['error' => 'Invalid action'], 400);
}