<?php
/**
 * Predictions Page Template
 */

// Require user authentication
if (!is_logged_in()) {
    set_flash_message('error', 'You must be logged in to access this page.');
    redirect('index.php?page=login&redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get all tournaments
$tournaments = get_all_tournaments();

// Filter active tournaments
$active_tournaments = array_filter($tournaments, function($tournament) {
    return $tournament['status'] === 'active';
});

// Get user predictions
$predictions_data = read_json_file(PREDICTIONS_FILE);
$user_predictions = [];

if ($predictions_data && isset($predictions_data['predictions'])) {
    $user_predictions = array_filter($predictions_data['predictions'], function($prediction) use ($user_id) {
        return $prediction['user_id'] === $user_id;
    });
}

// Group predictions by tournament
$predictions_by_tournament = [];
foreach ($user_predictions as $prediction) {
    $tournament_id = $prediction['tournament_id'];
    if (!isset($predictions_by_tournament[$tournament_id])) {
        $predictions_by_tournament[$tournament_id] = [];
    }
    $predictions_by_tournament[$tournament_id][] = $prediction;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'create_prediction') {
        $tournament_id = isset($_POST['tournament_id']) ? sanitize_input($_POST['tournament_id']) : '';
        $match_id = isset($_POST['match_id']) ? sanitize_input($_POST['match_id']) : '';
        $predicted_winner = isset($_POST['predicted_winner']) ? sanitize_input($_POST['predicted_winner']) : '';
        
        if (empty($tournament_id) || empty($match_id) || empty($predicted_winner)) {
            set_flash_message('error', 'All fields are required.');
        } else {
            // Get the match
            $match = get_match($match_id);
            
            if (!$match) {
                set_flash_message('error', 'Match not found.');
            } elseif ($match['status'] === 'completed') {
                set_flash_message('error', 'Cannot predict for a completed match.');
            } else {
                // Check if user already has a prediction for this match
                $existing_prediction = null;
                foreach ($user_predictions as $key => $prediction) {
                    if ($prediction['match_id'] === $match_id) {
                        $existing_prediction = $prediction;
                        break;
                    }
                }
                
                if ($existing_prediction) {
                    // Update existing prediction
                    $existing_prediction['predicted_winner'] = $predicted_winner;
                    $existing_prediction['updated_at'] = date('Y-m-d\TH:i:s\Z');
                    
                    // Update in predictions array
                    foreach ($predictions_data['predictions'] as $key => $prediction) {
                        if ($prediction['id'] === $existing_prediction['id']) {
                            $predictions_data['predictions'][$key] = $existing_prediction;
                            break;
                        }
                    }
                    
                    if (write_json_file(PREDICTIONS_FILE, $predictions_data)) {
                        set_flash_message('success', 'Prediction updated successfully.');
                    } else {
                        set_flash_message('error', 'Failed to update prediction.');
                    }
                } else {
                    // Create new prediction
                    $prediction_id = generate_id('pred');
                    $new_prediction = [
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
                    
                    $predictions_data['predictions'][] = $new_prediction;
                    
                    if (write_json_file(PREDICTIONS_FILE, $predictions_data)) {
                        set_flash_message('success', 'Prediction created successfully.');
                    } else {
                        set_flash_message('error', 'Failed to create prediction.');
                    }
                }
                
                // Redirect to avoid form resubmission
                redirect('index.php?page=predictions');
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-chart-line me-2"></i>My Predictions</h1>
</div>

<!-- Active Tournaments -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-trophy me-2"></i>Active Tournaments</h5>
    </div>
    <div class="card-body">
        <?php if (empty($active_tournaments)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>There are no active tournaments at the moment.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($active_tournaments as $tournament): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($tournament['name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Current Round:</strong> <?php echo $tournament['current_round']; ?></p>
                                <p><strong>Participants:</strong> <?php echo count($tournament['participants']); ?></p>
                                
                                <?php
                                // Count user predictions for this tournament
                                $tournament_predictions = isset($predictions_by_tournament[$tournament['id']]) ? 
                                    $predictions_by_tournament[$tournament['id']] : [];
                                $prediction_count = count($tournament_predictions);
                                
                                // Get tournament matches
                                $matches = get_tournament_matches($tournament['id']);
                                $pending_matches = array_filter($matches, function($match) {
                                    return $match['status'] !== 'completed' && 
                                           $match['participant1'] && 
                                           $match['participant2'];
                                });
                                $pending_count = count($pending_matches);
                                ?>
                                
                                <p>
                                    <strong>Your Predictions:</strong> 
                                    <?php echo $prediction_count; ?> made
                                    <?php if ($pending_count > 0): ?>
                                        (<?php echo $pending_count; ?> matches available for prediction)
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="index.php?page=tournament&id=<?php echo $tournament['id']; ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-eye me-2"></i>View Tournament
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- My Predictions -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>My Prediction History</h5>
    </div>
    <div class="card-body">
        <?php if (empty($user_predictions)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>You haven't made any predictions yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tournament</th>
                            <th>Match</th>
                            <th>Your Prediction</th>
                            <th>Status</th>
                            <th>Result</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Sort predictions by creation date (newest first)
                        usort($user_predictions, function($a, $b) {
                            return strtotime($b['created_at']) - strtotime($a['created_at']);
                        });
                        
                        foreach ($user_predictions as $prediction): 
                            // Get tournament
                            $tournament = null;
                            foreach ($tournaments as $t) {
                                if ($t['id'] === $prediction['tournament_id']) {
                                    $tournament = $t;
                                    break;
                                }
                            }
                            
                            if (!$tournament) continue;
                            
                            // Get match
                            $match = get_match($prediction['match_id']);
                            if (!$match) continue;
                            
                            // Get participants
                            $participants = [];
                            foreach ($tournament['participants'] as $participant_id) {
                                $participant = get_participant($participant_id);
                                if ($participant) {
                                    $participants[$participant_id] = $participant;
                                }
                            }
                            
                            // Get participant names
                            $participant1_name = isset($participants[$match['participant1']]) ? 
                                $participants[$match['participant1']]['name'] : 'Unknown';
                            $participant2_name = isset($participants[$match['participant2']]) ? 
                                $participants[$match['participant2']]['name'] : 'Unknown';
                            
                            // Get predicted winner name
                            $predicted_winner_name = isset($participants[$prediction['predicted_winner']]) ? 
                                $participants[$prediction['predicted_winner']]['name'] : 'Unknown';
                            
                            // Determine row class based on prediction result
                            $row_class = '';
                            if ($match['status'] === 'completed') {
                                if ($match['winner'] === $prediction['predicted_winner']) {
                                    $row_class = 'table-success';
                                } else {
                                    $row_class = 'table-danger';
                                }
                            }
                        ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><?php echo htmlspecialchars($tournament['name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($participant1_name); ?> vs 
                                    <?php echo htmlspecialchars($participant2_name); ?>
                                </td>
                                <td><?php echo htmlspecialchars($predicted_winner_name); ?></td>
                                <td>
                                    <?php if ($match['status'] === 'completed'): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php elseif ($match['status'] === 'in_progress'): ?>
                                        <span class="badge bg-warning text-dark">In Progress</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($match['status'] === 'completed'): ?>
                                        <?php if ($match['winner'] === $prediction['predicted_winner']): ?>
                                            <span class="text-success"><i class="fas fa-check-circle me-1"></i>Correct</span>
                                        <?php else: ?>
                                            <span class="text-danger"><i class="fas fa-times-circle me-1"></i>Incorrect</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Waiting</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($match['status'] === 'completed' && $match['winner'] === $prediction['predicted_winner']) {
                                        echo '<span class="badge bg-primary">+10</span>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- How Predictions Work -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>How Predictions Work</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Making Predictions</h6>
                <ol>
                    <li>Navigate to an active tournament</li>
                    <li>Find upcoming matches in the current round</li>
                    <li>Click the "Predict" button next to a match</li>
                    <li>Select the participant you think will win</li>
                    <li>Submit your prediction</li>
                </ol>
            </div>
            <div class="col-md-6">
                <h6>Scoring</h6>
                <ul>
                    <li><strong>Correct prediction:</strong> +10 points</li>
                    <li><strong>Incorrect prediction:</strong> 0 points</li>
                    <li>Points are awarded when a match is completed</li>
                    <li>Predictions cannot be changed once a match starts</li>
                    <li>Leaderboards show the top predictors for each tournament</li>
                </ul>
            </div>
        </div>
    </div>
</div>