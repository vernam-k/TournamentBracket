<?php
/**
 * Predictions API Endpoint
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
    'get_leaderboard'
];

// Check if the action is allowed
if (!in_array($action, $allowed_actions)) {
    send_json_response(['error' => 'Invalid action'], 400);
}

// Handle the action
switch ($action) {
    case 'list':
        // Get predictions for a user or tournament
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
        $tournament_id = isset($_GET['tournament_id']) ? $_GET['tournament_id'] : '';
        
        if (empty($user_id) && empty($tournament_id)) {
            send_json_response(['error' => 'User ID or Tournament ID is required'], 400);
        }
        
        $predictions = read_json_file(PREDICTIONS_FILE);
        
        if (!$predictions) {
            send_json_response(['predictions' => []]);
        }
        
        $filtered_predictions = [];
        
        foreach ($predictions['predictions'] as $prediction) {
            if ((!empty($user_id) && $prediction['user_id'] === $user_id) ||
                (!empty($tournament_id) && $prediction['tournament_id'] === $tournament_id)) {
                $filtered_predictions[] = $prediction;
            }
        }
        
        send_json_response(['predictions' => $filtered_predictions]);
        break;
        
    case 'get':
        // Get a specific prediction
        $prediction_id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($prediction_id)) {
            send_json_response(['error' => 'Prediction ID is required'], 400);
        }
        
        $predictions = read_json_file(PREDICTIONS_FILE);
        
        if (!$predictions) {
            send_json_response(['error' => 'Prediction not found'], 404);
        }
        
        $prediction = null;
        
        foreach ($predictions['predictions'] as $p) {
            if ($p['id'] === $prediction_id) {
                $prediction = $p;
                break;
            }
        }
        
        if (!$prediction) {
            send_json_response(['error' => 'Prediction not found'], 404);
        }
        
        send_json_response(['prediction' => $prediction]);
        break;
        
    case 'create':
        // Create a new prediction (requires login)
        if (!is_logged_in()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $tournament_id = isset($_POST['tournament_id']) ? sanitize_input($_POST['tournament_id']) : '';
        $match_id = isset($_POST['match_id']) ? sanitize_input($_POST['match_id']) : '';
        $predicted_winner = isset($_POST['predicted_winner']) ? sanitize_input($_POST['predicted_winner']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            [
                'tournament_id' => $tournament_id,
                'match_id' => $match_id,
                'predicted_winner' => $predicted_winner
            ],
            ['tournament_id', 'match_id', 'predicted_winner']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Get the match
        $match = get_match($match_id);
        
        if (!$match) {
            send_json_response(['error' => 'Match not found'], 404);
        }
        
        // Check if the match belongs to the tournament
        if ($match['tournament_id'] !== $tournament_id) {
            send_json_response(['error' => 'Match does not belong to the specified tournament'], 400);
        }
        
        // Check if the predicted winner is a participant in the match
        if ($predicted_winner !== $match['participant1'] && $predicted_winner !== $match['participant2']) {
            send_json_response(['error' => 'Predicted winner must be a participant in the match'], 400);
        }
        
        // Check if the match is already completed
        if ($match['status'] === 'completed') {
            send_json_response(['error' => 'Cannot predict for a completed match'], 400);
        }
        
        // Get existing predictions
        $predictions = read_json_file(PREDICTIONS_FILE);
        
        if (!$predictions) {
            $predictions = ['predictions' => []];
        }
        
        // Check if the user already has a prediction for this match
        $user_id = $_SESSION['user_id'];
        $existing_prediction = null;
        
        foreach ($predictions['predictions'] as $key => $p) {
            if ($p['user_id'] === $user_id && $p['match_id'] === $match_id) {
                $existing_prediction = $key;
                break;
            }
        }
        
        if ($existing_prediction !== null) {
            // Update existing prediction
            $predictions['predictions'][$existing_prediction]['predicted_winner'] = $predicted_winner;
            $predictions['predictions'][$existing_prediction]['updated_at'] = date('Y-m-d\TH:i:s\Z');
            
            if (!write_json_file(PREDICTIONS_FILE, $predictions)) {
                send_json_response(['error' => 'Failed to update prediction'], 500);
            }
            
            send_json_response([
                'success' => true,
                'message' => 'Prediction updated successfully',
                'prediction_id' => $predictions['predictions'][$existing_prediction]['id']
            ]);
        } else {
            // Create new prediction
            $prediction_id = generate_id('pred');
            
            $prediction = [
                'id' => $prediction_id,
                'user_id' => $user_id,
                'tournament_id' => $tournament_id,
                'match_id' => $match_id,
                'predicted_winner' => $predicted_winner,
                'created_at' => date('Y-m-d\TH:i:s\Z'),
                'updated_at' => date('Y-m-d\TH:i:s\Z'),
                'points_earned' => 0,
                'is_correct' => null
            ];
            
            $predictions['predictions'][] = $prediction;
            
            if (!write_json_file(PREDICTIONS_FILE, $predictions)) {
                send_json_response(['error' => 'Failed to create prediction'], 500);
            }
            
            send_json_response([
                'success' => true,
                'message' => 'Prediction created successfully',
                'prediction_id' => $prediction_id
            ]);
        }
        break;
        
    case 'update':
        // Update a prediction (requires login)
        if (!is_logged_in()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $prediction_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        $predicted_winner = isset($_POST['predicted_winner']) ? sanitize_input($_POST['predicted_winner']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['id' => $prediction_id, 'predicted_winner' => $predicted_winner],
            ['id', 'predicted_winner']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Get existing predictions
        $predictions = read_json_file(PREDICTIONS_FILE);
        
        if (!$predictions) {
            send_json_response(['error' => 'Prediction not found'], 404);
        }
        
        // Find the prediction
        $prediction_index = null;
        $prediction = null;
        
        foreach ($predictions['predictions'] as $key => $p) {
            if ($p['id'] === $prediction_id) {
                $prediction_index = $key;
                $prediction = $p;
                break;
            }
        }
        
        if ($prediction_index === null) {
            send_json_response(['error' => 'Prediction not found'], 404);
        }
        
        // Check if the prediction belongs to the user
        if ($prediction['user_id'] !== $_SESSION['user_id']) {
            send_json_response(['error' => 'You can only update your own predictions'], 403);
        }
        
        // Get the match
        $match = get_match($prediction['match_id']);
        
        if (!$match) {
            send_json_response(['error' => 'Match not found'], 404);
        }
        
        // Check if the match is already completed
        if ($match['status'] === 'completed') {
            send_json_response(['error' => 'Cannot update prediction for a completed match'], 400);
        }
        
        // Check if the predicted winner is a participant in the match
        if ($predicted_winner !== $match['participant1'] && $predicted_winner !== $match['participant2']) {
            send_json_response(['error' => 'Predicted winner must be a participant in the match'], 400);
        }
        
        // Update the prediction
        $predictions['predictions'][$prediction_index]['predicted_winner'] = $predicted_winner;
        $predictions['predictions'][$prediction_index]['updated_at'] = date('Y-m-d\TH:i:s\Z');
        
        if (!write_json_file(PREDICTIONS_FILE, $predictions)) {
            send_json_response(['error' => 'Failed to update prediction'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Prediction updated successfully'
        ]);
        break;
        
    case 'delete':
        // Delete a prediction (requires login)
        if (!is_logged_in()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get POST data
        $prediction_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        
        if (empty($prediction_id)) {
            send_json_response(['error' => 'Prediction ID is required'], 400);
        }
        
        // Get existing predictions
        $predictions = read_json_file(PREDICTIONS_FILE);
        
        if (!$predictions) {
            send_json_response(['error' => 'Prediction not found'], 404);
        }
        
        // Find the prediction
        $prediction_index = null;
        $prediction = null;
        
        foreach ($predictions['predictions'] as $key => $p) {
            if ($p['id'] === $prediction_id) {
                $prediction_index = $key;
                $prediction = $p;
                break;
            }
        }
        
        if ($prediction_index === null) {
            send_json_response(['error' => 'Prediction not found'], 404);
        }
        
        // Check if the prediction belongs to the user
        if ($prediction['user_id'] !== $_SESSION['user_id'] && !is_admin()) {
            send_json_response(['error' => 'You can only delete your own predictions'], 403);
        }
        
        // Delete the prediction
        unset($predictions['predictions'][$prediction_index]);
        $predictions['predictions'] = array_values($predictions['predictions']);
        
        if (!write_json_file(PREDICTIONS_FILE, $predictions)) {
            send_json_response(['error' => 'Failed to delete prediction'], 500);
        }
        
        send_json_response([
            'success' => true,
            'message' => 'Prediction deleted successfully'
        ]);
        break;
        
    case 'get_leaderboard':
        // Get prediction leaderboard for a tournament
        $tournament_id = isset($_GET['tournament_id']) ? $_GET['tournament_id'] : '';
        
        if (empty($tournament_id)) {
            send_json_response(['error' => 'Tournament ID is required'], 400);
        }
        
        // Get all predictions for the tournament
        $predictions = read_json_file(PREDICTIONS_FILE);
        
        if (!$predictions) {
            send_json_response(['leaderboard' => []]);
        }
        
        // Filter predictions for the tournament
        $tournament_predictions = [];
        
        foreach ($predictions['predictions'] as $prediction) {
            if ($prediction['tournament_id'] === $tournament_id) {
                $tournament_predictions[] = $prediction;
            }
        }
        
        // Get all users
        $users = get_all_users();
        $user_map = [];
        
        foreach ($users as $user) {
            $user_map[$user['id']] = $user['username'];
        }
        
        // Calculate points for each user
        $user_points = [];
        
        foreach ($tournament_predictions as $prediction) {
            $user_id = $prediction['user_id'];
            
            if (!isset($user_points[$user_id])) {
                $user_points[$user_id] = [
                    'user_id' => $user_id,
                    'username' => isset($user_map[$user_id]) ? $user_map[$user_id] : 'Unknown',
                    'total_predictions' => 0,
                    'correct_predictions' => 0,
                    'points' => 0
                ];
            }
            
            $user_points[$user_id]['total_predictions']++;
            
            if ($prediction['is_correct'] === true) {
                $user_points[$user_id]['correct_predictions']++;
                $user_points[$user_id]['points'] += $prediction['points_earned'];
            }
        }
        
        // Sort by points (descending)
        usort($user_points, function($a, $b) {
            if ($a['points'] != $b['points']) {
                return $b['points'] - $a['points'];
            }
            return $b['correct_predictions'] - $a['correct_predictions'];
        });
        
        send_json_response(['leaderboard' => array_values($user_points)]);
        break;
        
    default:
        send_json_response(['error' => 'Invalid action'], 400);
}