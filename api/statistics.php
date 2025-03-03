<?php
/**
 * Statistics API Endpoint
 */

// Check if this file is included from the API entry point
if (!defined('ROOT_PATH')) {
    exit('Direct access not allowed');
}

// Get the action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Define allowed actions
$allowed_actions = [
    'tournament_stats',
    'participant_stats',
    'user_prediction_stats'
];

// Check if the action is allowed
if (!in_array($action, $allowed_actions)) {
    send_json_response(['error' => 'Invalid action'], 400);
}

// Handle the action
switch ($action) {
    case 'tournament_stats':
        // Get statistics for a tournament
        $tournament_id = isset($_GET['tournament_id']) ? $_GET['tournament_id'] : '';
        
        if (empty($tournament_id)) {
            send_json_response(['error' => 'Tournament ID is required'], 400);
        }
        
        // Get the tournament
        $tournament = get_tournament($tournament_id);
        
        if (!$tournament) {
            send_json_response(['error' => 'Tournament not found'], 404);
        }
        
        // Get all matches for the tournament
        $matches = get_tournament_matches($tournament_id);
        
        // Calculate statistics
        $total_matches = count($matches);
        $completed_matches = 0;
        $pending_matches = 0;
        $total_points = 0;
        $highest_score = 0;
        $participant_stats = [];
        
        foreach ($matches as $match) {
            if ($match['status'] === 'completed') {
                $completed_matches++;
                $total_points += $match['score1'] + $match['score2'];
                $highest_score = max($highest_score, $match['score1'], $match['score2']);
                
                // Update participant stats
                $participant1 = $match['participant1'];
                $participant2 = $match['participant2'];
                
                if (!isset($participant_stats[$participant1])) {
                    $participant_stats[$participant1] = [
                        'matches_played' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'points_scored' => 0,
                        'points_against' => 0
                    ];
                }
                
                if (!isset($participant_stats[$participant2])) {
                    $participant_stats[$participant2] = [
                        'matches_played' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'points_scored' => 0,
                        'points_against' => 0
                    ];
                }
                
                // Update participant 1 stats
                $participant_stats[$participant1]['matches_played']++;
                $participant_stats[$participant1]['points_scored'] += $match['score1'];
                $participant_stats[$participant1]['points_against'] += $match['score2'];
                
                if ($match['winner'] === $participant1) {
                    $participant_stats[$participant1]['wins']++;
                } else {
                    $participant_stats[$participant1]['losses']++;
                }
                
                // Update participant 2 stats
                $participant_stats[$participant2]['matches_played']++;
                $participant_stats[$participant2]['points_scored'] += $match['score2'];
                $participant_stats[$participant2]['points_against'] += $match['score1'];
                
                if ($match['winner'] === $participant2) {
                    $participant_stats[$participant2]['wins']++;
                } else {
                    $participant_stats[$participant2]['losses']++;
                }
            } else {
                $pending_matches++;
            }
        }
        
        // Get participant names
        $participants = [];
        foreach ($tournament['participants'] as $participant_id) {
            $participant = get_participant($participant_id);
            if ($participant) {
                $participants[$participant_id] = $participant['name'];
            }
        }
        
        // Format participant stats with names
        $formatted_participant_stats = [];
        foreach ($participant_stats as $participant_id => $stats) {
            $name = isset($participants[$participant_id]) ? $participants[$participant_id] : 'Unknown';
            $formatted_participant_stats[] = array_merge(
                ['id' => $participant_id, 'name' => $name],
                $stats
            );
        }
        
        // Sort participants by wins (descending)
        usort($formatted_participant_stats, function($a, $b) {
            if ($a['wins'] != $b['wins']) {
                return $b['wins'] - $a['wins'];
            }
            return ($b['points_scored'] - $b['points_against']) - ($a['points_scored'] - $a['points_against']);
        });
        
        // Calculate average points per match
        $avg_points_per_match = $completed_matches > 0 ? $total_points / $completed_matches : 0;
        
        $stats = [
            'tournament_id' => $tournament_id,
            'tournament_name' => $tournament['name'],
            'tournament_type' => $tournament['type'],
            'tournament_status' => $tournament['status'],
            'total_matches' => $total_matches,
            'completed_matches' => $completed_matches,
            'pending_matches' => $pending_matches,
            'completion_percentage' => $total_matches > 0 ? ($completed_matches / $total_matches) * 100 : 0,
            'total_points' => $total_points,
            'avg_points_per_match' => $avg_points_per_match,
            'highest_score' => $highest_score,
            'participant_stats' => $formatted_participant_stats
        ];
        
        send_json_response(['statistics' => $stats]);
        break;
        
    case 'participant_stats':
        // Get statistics for a participant
        $participant_id = isset($_GET['participant_id']) ? $_GET['participant_id'] : '';
        
        if (empty($participant_id)) {
            send_json_response(['error' => 'Participant ID is required'], 400);
        }
        
        // Get the participant
        $participant = get_participant($participant_id);
        
        if (!$participant) {
            send_json_response(['error' => 'Participant not found'], 404);
        }
        
        // Get all tournaments
        $tournaments = get_all_tournaments();
        
        // Find tournaments the participant is in
        $participant_tournaments = [];
        foreach ($tournaments as $tournament) {
            if (in_array($participant_id, $tournament['participants'])) {
                $participant_tournaments[] = $tournament['id'];
            }
        }
        
        // Get all matches for the participant
        $all_matches = [];
        foreach ($participant_tournaments as $tournament_id) {
            $tournament_matches = get_tournament_matches($tournament_id);
            foreach ($tournament_matches as $match) {
                if ($match['participant1'] === $participant_id || $match['participant2'] === $participant_id) {
                    $all_matches[] = $match;
                }
            }
        }
        
        // Calculate statistics
        $total_matches = count($all_matches);
        $completed_matches = 0;
        $wins = 0;
        $losses = 0;
        $points_scored = 0;
        $points_against = 0;
        $tournament_wins = 0;
        
        foreach ($all_matches as $match) {
            if ($match['status'] === 'completed') {
                $completed_matches++;
                
                if ($match['participant1'] === $participant_id) {
                    $points_scored += $match['score1'];
                    $points_against += $match['score2'];
                    
                    if ($match['winner'] === $participant_id) {
                        $wins++;
                    } else {
                        $losses++;
                    }
                } else {
                    $points_scored += $match['score2'];
                    $points_against += $match['score1'];
                    
                    if ($match['winner'] === $participant_id) {
                        $wins++;
                    } else {
                        $losses++;
                    }
                }
            }
        }
        
        // Check if participant has won any tournaments
        foreach ($tournaments as $tournament) {
            if ($tournament['winner'] === $participant_id) {
                $tournament_wins++;
            }
        }
        
        $stats = [
            'participant_id' => $participant_id,
            'participant_name' => $participant['name'],
            'total_matches' => $total_matches,
            'completed_matches' => $completed_matches,
            'wins' => $wins,
            'losses' => $losses,
            'win_percentage' => $completed_matches > 0 ? ($wins / $completed_matches) * 100 : 0,
            'points_scored' => $points_scored,
            'points_against' => $points_against,
            'point_differential' => $points_scored - $points_against,
            'avg_points_per_match' => $completed_matches > 0 ? $points_scored / $completed_matches : 0,
            'tournament_wins' => $tournament_wins,
            'tournaments_participated' => count($participant_tournaments)
        ];
        
        send_json_response(['statistics' => $stats]);
        break;
        
    case 'user_prediction_stats':
        // Get prediction statistics for a user
        if (!is_logged_in()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['user_id'];
        
        // If requesting stats for another user, must be admin
        if ($user_id !== $_SESSION['user_id'] && !is_admin()) {
            send_json_response(['error' => 'Unauthorized'], 401);
        }
        
        // Get all predictions for the user
        $predictions = read_json_file(PREDICTIONS_FILE);
        
        if (!$predictions) {
            send_json_response(['statistics' => [
                'user_id' => $user_id,
                'total_predictions' => 0,
                'correct_predictions' => 0,
                'accuracy' => 0,
                'total_points' => 0,
                'tournaments' => []
            ]]);
        }
        
        // Filter predictions for the user
        $user_predictions = [];
        foreach ($predictions['predictions'] as $prediction) {
            if ($prediction['user_id'] === $user_id) {
                $user_predictions[] = $prediction;
            }
        }
        
        // Calculate overall statistics
        $total_predictions = count($user_predictions);
        $correct_predictions = 0;
        $total_points = 0;
        $tournament_stats = [];
        
        foreach ($user_predictions as $prediction) {
            $tournament_id = $prediction['tournament_id'];
            
            if (!isset($tournament_stats[$tournament_id])) {
                $tournament = get_tournament($tournament_id);
                $tournament_name = $tournament ? $tournament['name'] : 'Unknown';
                
                $tournament_stats[$tournament_id] = [
                    'tournament_id' => $tournament_id,
                    'tournament_name' => $tournament_name,
                    'total_predictions' => 0,
                    'correct_predictions' => 0,
                    'accuracy' => 0,
                    'total_points' => 0
                ];
            }
            
            $tournament_stats[$tournament_id]['total_predictions']++;
            
            if ($prediction['is_correct'] === true) {
                $correct_predictions++;
                $total_points += $prediction['points_earned'];
                $tournament_stats[$tournament_id]['correct_predictions']++;
                $tournament_stats[$tournament_id]['total_points'] += $prediction['points_earned'];
            }
        }
        
        // Calculate accuracy for each tournament
        foreach ($tournament_stats as &$stats) {
            $stats['accuracy'] = $stats['total_predictions'] > 0 
                ? ($stats['correct_predictions'] / $stats['total_predictions']) * 100 
                : 0;
        }
        
        // Sort tournaments by total points (descending)
        usort($tournament_stats, function($a, $b) {
            return $b['total_points'] - $a['total_points'];
        });
        
        $stats = [
            'user_id' => $user_id,
            'total_predictions' => $total_predictions,
            'correct_predictions' => $correct_predictions,
            'accuracy' => $total_predictions > 0 ? ($correct_predictions / $total_predictions) * 100 : 0,
            'total_points' => $total_points,
            'tournaments' => array_values($tournament_stats)
        ];
        
        send_json_response(['statistics' => $stats]);
        break;
        
    default:
        send_json_response(['error' => 'Invalid action'], 400);
}