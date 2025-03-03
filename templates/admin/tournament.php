<?php
/**
 * Admin Single Tournament Management Template
 */

// Require admin authentication
require_admin();

// Get tournament ID
$tournament_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($tournament_id)) {
    set_flash_message('error', 'Tournament ID is required.');
    redirect('index.php?page=admin_tournaments');
}

// Get tournament data
$tournament = get_tournament($tournament_id);

if (!$tournament) {
    set_flash_message('error', 'Tournament not found.');
    redirect('index.php?page=admin_tournaments');
}

// Get tournament matches
$matches = get_tournament_matches($tournament_id);

// Get all participants
$all_participants = get_all_participants();

// Get tournament participants
$tournament_participants = [];
foreach ($tournament['participants'] as $participant_id) {
    $participant = get_participant($participant_id);
    if ($participant) {
        $tournament_participants[$participant_id] = $participant;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Update tournament
    if ($action === 'update_tournament') {
        $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
        
        if (!empty($name)) {
            $tournament['name'] = $name;
            
            if (save_tournament($tournament)) {
                set_flash_message('success', 'Tournament updated successfully.');
            } else {
                set_flash_message('error', 'Failed to update tournament.');
            }
        } else {
            set_flash_message('error', 'Tournament name is required.');
        }
    }
    
    // Add participant
    if ($action === 'add_participant') {
        $participant_id = isset($_POST['participant_id']) ? sanitize_input($_POST['participant_id']) : '';
        
        if (!empty($participant_id)) {
            if ($tournament['status'] !== 'setup') {
                set_flash_message('error', 'Cannot add participants to an active or completed tournament.');
            } else {
                if (add_tournament_participant($tournament_id, $participant_id)) {
                    set_flash_message('success', 'Participant added successfully.');
                    
                    // Refresh tournament data
                    $tournament = get_tournament($tournament_id);
                    
                    // Update tournament participants
                    $tournament_participants = [];
                    foreach ($tournament['participants'] as $pid) {
                        $participant = get_participant($pid);
                        if ($participant) {
                            $tournament_participants[$pid] = $participant;
                        }
                    }
                } else {
                    set_flash_message('error', 'Failed to add participant.');
                }
            }
        } else {
            set_flash_message('error', 'Participant ID is required.');
        }
    }
    
    // Remove participant
    if ($action === 'remove_participant') {
        $participant_id = isset($_POST['participant_id']) ? sanitize_input($_POST['participant_id']) : '';
        
        if (!empty($participant_id)) {
            if ($tournament['status'] !== 'setup') {
                set_flash_message('error', 'Cannot remove participants from an active or completed tournament.');
            } else {
                if (remove_tournament_participant($tournament_id, $participant_id)) {
                    set_flash_message('success', 'Participant removed successfully.');
                    
                    // Refresh tournament data
                    $tournament = get_tournament($tournament_id);
                    
                    // Update tournament participants
                    $tournament_participants = [];
                    foreach ($tournament['participants'] as $pid) {
                        $participant = get_participant($pid);
                        if ($participant) {
                            $tournament_participants[$pid] = $participant;
                        }
                    }
                } else {
                    set_flash_message('error', 'Failed to remove participant.');
                }
            }
        } else {
            set_flash_message('error', 'Participant ID is required.');
        }
    }
    
    // Start tournament
    if ($action === 'start_tournament') {
        if ($tournament['status'] !== 'setup') {
            set_flash_message('error', 'Tournament has already been started.');
        } else {
            if (count($tournament['participants']) < 2) {
                set_flash_message('error', 'Tournament must have at least 2 participants to start.');
            } else {
                if (start_tournament($tournament_id)) {
                    set_flash_message('success', 'Tournament started successfully.');
                    
                    // Refresh tournament data
                    $tournament = get_tournament($tournament_id);
                    
                    // Refresh matches
                    $matches = get_tournament_matches($tournament_id);
                } else {
                    set_flash_message('error', 'Failed to start tournament.');
                }
            }
        }
    }
    
    // Update match score
    if ($action === 'update_match') {
        $match_id = isset($_POST['match_id']) ? sanitize_input($_POST['match_id']) : '';
        $score1 = isset($_POST['score1']) ? intval($_POST['score1']) : 0;
        $score2 = isset($_POST['score2']) ? intval($_POST['score2']) : 0;
        
        if (!empty($match_id)) {
            if ($tournament['status'] !== 'active') {
                set_flash_message('error', 'Can only update matches in active tournaments.');
            } else {
                if (update_match_score($match_id, $score1, $score2)) {
                    set_flash_message('success', 'Match score updated successfully.');
                    
                    // Refresh tournament data
                    $tournament = get_tournament($tournament_id);
                    
                    // Refresh matches
                    $matches = get_tournament_matches($tournament_id);
                } else {
                    set_flash_message('error', 'Failed to update match score.');
                }
            }
        } else {
            set_flash_message('error', 'Match ID is required.');
        }
    }
    
    // Redirect to avoid form resubmission
    redirect('index.php?page=admin_tournament&id=' . $tournament_id);
}

// Format tournament type
$tournament_type_label = '';
switch ($tournament['type']) {
    case 'single_elimination':
        $tournament_type_label = 'Single Elimination';
        break;
    case 'double_elimination':
        $tournament_type_label = 'Double Elimination';
        break;
    case 'round_robin':
        $tournament_type_label = 'Round Robin';
        break;
}

// Format tournament status
$tournament_status_class = '';
$tournament_status_label = '';
switch ($tournament['status']) {
    case 'setup':
        $tournament_status_class = 'bg-secondary';
        $tournament_status_label = 'Setup';
        break;
    case 'active':
        $tournament_status_class = 'bg-success';
        $tournament_status_label = 'Active';
        break;
    case 'completed':
        $tournament_status_class = 'bg-info';
        $tournament_status_label = 'Completed';
        break;
}

// Group matches by round
$matches_by_round = [];
foreach ($matches as $match) {
    $round = $match['round'];
    if (!isset($matches_by_round[$round])) {
        $matches_by_round[$round] = [];
    }
    $matches_by_round[$round][] = $match;
}

// Sort rounds
ksort($matches_by_round);
?>

<!-- Tournament Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
    <div>
        <a href="index.php?page=tournament&id=<?php echo $tournament_id; ?>" class="btn btn-primary me-2">
            <i class="fas fa-eye me-2"></i>View Tournament
        </a>
        <a href="index.php?page=admin_tournaments" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Tournaments
        </a>
    </div>
</div>

<!-- Tournament Info -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Tournament Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=admin_tournament&id=<?php echo $tournament_id; ?>">
                    <input type="hidden" name="action" value="update_tournament">
                    
                    <div class="row mb-3">
                        <label for="name" class="col-sm-3 col-form-label">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($tournament['name']); ?>" 
                                   <?php echo $tournament['status'] !== 'setup' ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Type</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="<?php echo $tournament_type_label; ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Status</label>
                        <div class="col-sm-9">
                            <span class="badge <?php echo $tournament_status_class; ?>"><?php echo $tournament_status_label; ?></span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Created</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="<?php echo format_date($tournament['created_at']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Participants</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="<?php echo count($tournament_participants); ?>" readonly>
                        </div>
                    </div>
                    
                    <?php if ($tournament['status'] === 'active'): ?>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Current Round</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="<?php echo $tournament['current_round']; ?>" readonly>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($tournament['status'] === 'completed' && $tournament['winner']): ?>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Winner</label>
                            <div class="col-sm-9">
                                <?php 
                                $winner = isset($tournament_participants[$tournament['winner']]) ? $tournament_participants[$tournament['winner']] : null;
                                $winner_name = $winner ? htmlspecialchars($winner['name']) : 'Unknown';
                                ?>
                                <input type="text" class="form-control" value="<?php echo $winner_name; ?>" readonly>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($tournament['status'] === 'setup'): ?>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Tournament
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-cogs me-2"></i>Tournament Actions</h5>
            </div>
            <div class="card-body">
                <?php if ($tournament['status'] === 'setup'): ?>
                    <form method="post" action="index.php?page=admin_tournament&id=<?php echo $tournament_id; ?>" class="mb-3">
                        <input type="hidden" name="action" value="start_tournament">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success" <?php echo count($tournament['participants']) < 2 ? 'disabled' : ''; ?>>
                                <i class="fas fa-play me-2"></i>Start Tournament
                            </button>
                        </div>
                        <?php if (count($tournament['participants']) < 2): ?>
                            <div class="form-text text-danger">
                                <i class="fas fa-exclamation-circle me-1"></i>Tournament must have at least 2 participants to start.
                            </div>
                        <?php else: ?>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>Starting the tournament will generate brackets and cannot be undone.
                            </div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
                
                <a href="index.php?page=tournament&id=<?php echo $tournament_id; ?>" class="btn btn-primary d-block mb-2">
                    <i class="fas fa-eye me-2"></i>View Tournament
                </a>
                
                <button class="btn btn-outline-primary d-block mb-2" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Bracket
                </button>
                
                <?php if ($tournament['status'] === 'active'): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>Tournament is active. You can update match scores below.
                    </div>
                <?php elseif ($tournament['status'] === 'completed'): ?>
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-check-circle me-2"></i>Tournament is completed.
                        <?php if ($tournament['winner']): ?>
                            <?php 
                            $winner = isset($tournament_participants[$tournament['winner']]) ? $tournament_participants[$tournament['winner']] : null;
                            $winner_name = $winner ? htmlspecialchars($winner['name']) : 'Unknown';
                            ?>
                            <strong><?php echo $winner_name; ?></strong> is the winner!
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Participants Management -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Participants Management</h5>
    </div>
    <div class="card-body">
        <?php if ($tournament['status'] === 'setup'): ?>
            <div class="row mb-4">
                <div class="col-md-6">
                    <form method="post" action="index.php?page=admin_tournament&id=<?php echo $tournament_id; ?>">
                        <input type="hidden" name="action" value="add_participant">
                        
                        <div class="input-group">
                            <select class="form-select" name="participant_id" required>
                                <option value="">Select a participant to add</option>
                                <?php 
                                // Filter out participants already in the tournament
                                $available_participants = array_filter($all_participants, function($participant) use ($tournament) {
                                    return !in_array($participant['id'], $tournament['participants']);
                                });
                                
                                foreach ($available_participants as $participant): 
                                ?>
                                    <option value="<?php echo $participant['id']; ?>">
                                        <?php echo htmlspecialchars($participant['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add
                            </button>
                        </div>
                        
                        <?php if (empty($available_participants)): ?>
                            <div class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>No more participants available. <a href="index.php?page=admin_participants&action=create">Create a new participant</a>.
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <?php if ($tournament['status'] === 'setup'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tournament_participants)): ?>
                        <tr>
                            <td colspan="<?php echo $tournament['status'] === 'setup' ? '3' : '2'; ?>" class="text-center">
                                <i class="fas fa-info-circle me-2"></i>No participants yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tournament_participants as $participant): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($participant['name']); ?>
                                    <?php if ($tournament['status'] === 'completed' && $tournament['winner'] === $participant['id']): ?>
                                        <span class="badge bg-warning text-dark ms-2">
                                            <i class="fas fa-trophy me-1"></i>Winner
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($participant['description']); ?></td>
                                <?php if ($tournament['status'] === 'setup'): ?>
                                    <td>
                                        <form method="post" action="index.php?page=admin_tournament&id=<?php echo $tournament_id; ?>" class="d-inline">
                                            <input type="hidden" name="action" value="remove_participant">
                                            <input type="hidden" name="participant_id" value="<?php echo $participant['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Remove">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Matches Management -->
<?php if ($tournament['status'] !== 'setup'): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-gamepad me-2"></i>Matches Management</h5>
        </div>
        <div class="card-body">
            <?php if (empty($matches)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No matches have been created yet.
                </div>
            <?php else: ?>
                <div class="accordion" id="matchesAccordion">
                    <?php foreach ($matches_by_round as $round => $round_matches): ?>
                        <?php 
                        $round_id = "round-" . $round;
                        $is_current_round = $tournament['current_round'] == $round;
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?php echo $round_id; ?>">
                                <button class="accordion-button <?php echo $is_current_round ? '' : 'collapsed'; ?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $round_id; ?>" 
                                        aria-expanded="<?php echo $is_current_round ? 'true' : 'false'; ?>" 
                                        aria-controls="collapse-<?php echo $round_id; ?>">
                                    Round <?php echo $round; ?>
                                    <?php if ($is_current_round): ?>
                                        <span class="badge bg-success ms-2">Current Round</span>
                                    <?php endif; ?>
                                </button>
                            </h2>
                            <div id="collapse-<?php echo $round_id; ?>" class="accordion-collapse collapse <?php echo $is_current_round ? 'show' : ''; ?>" 
                                 aria-labelledby="heading-<?php echo $round_id; ?>" data-bs-parent="#matchesAccordion">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Match</th>
                                                    <th>Participant 1</th>
                                                    <th>Score</th>
                                                    <th>Participant 2</th>
                                                    <th>Status</th>
                                                    <?php if ($tournament['status'] === 'active'): ?>
                                                        <th>Actions</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($round_matches as $match): ?>
                                                    <tr>
                                                        <td><?php echo $match['match_number']; ?></td>
                                                        <td>
                                                            <?php 
                                                            if ($match['participant1']) {
                                                                $participant1 = isset($tournament_participants[$match['participant1']]) ? $tournament_participants[$match['participant1']] : null;
                                                                echo $participant1 ? htmlspecialchars($participant1['name']) : 'Unknown';
                                                            } else {
                                                                echo '<span class="text-muted">TBD</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($match['status'] === 'completed'): ?>
                                                                <span class="badge bg-light text-dark">
                                                                    <?php echo $match['score1']; ?> - <?php echo $match['score2']; ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            if ($match['participant2']) {
                                                                $participant2 = isset($tournament_participants[$match['participant2']]) ? $tournament_participants[$match['participant2']] : null;
                                                                echo $participant2 ? htmlspecialchars($participant2['name']) : 'Unknown';
                                                            } else {
                                                                echo '<span class="text-muted">TBD</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $match_status_class = '';
                                                            $match_status_label = '';
                                                            
                                                            switch ($match['status']) {
                                                                case 'pending':
                                                                    $match_status_class = 'bg-secondary';
                                                                    $match_status_label = 'Pending';
                                                                    break;
                                                                case 'in_progress':
                                                                    $match_status_class = 'bg-warning text-dark';
                                                                    $match_status_label = 'In Progress';
                                                                    break;
                                                                case 'completed':
                                                                    $match_status_class = 'bg-success';
                                                                    $match_status_label = 'Completed';
                                                                    break;
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $match_status_class; ?>">
                                                                <?php echo $match_status_label; ?>
                                                            </span>
                                                        </td>
                                                        <?php if ($tournament['status'] === 'active'): ?>
                                                            <td>
                                                                <?php if ($match['participant1'] && $match['participant2']): ?>
                                                                    <button type="button" class="btn btn-sm btn-primary update-match-btn" 
                                                                            data-bs-toggle="modal" data-bs-target="#updateMatchModal"
                                                                            data-match-id="<?php echo $match['id']; ?>"
                                                                            data-participant1="<?php echo isset($tournament_participants[$match['participant1']]) ? htmlspecialchars($tournament_participants[$match['participant1']]['name']) : 'Unknown'; ?>"
                                                                            data-participant2="<?php echo isset($tournament_participants[$match['participant2']]) ? htmlspecialchars($tournament_participants[$match['participant2']]['name']) : 'Unknown'; ?>"
                                                                            data-score1="<?php echo $match['score1']; ?>"
                                                                            data-score2="<?php echo $match['score2']; ?>"
                                                                            data-status="<?php echo $match['status']; ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Waiting for participants</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Update Match Modal -->
    <?php if ($tournament['status'] === 'active'): ?>
        <div class="modal fade" id="updateMatchModal" tabindex="-1" aria-labelledby="updateMatchModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="updateMatchModalLabel"><i class="fas fa-edit me-2"></i>Update Match</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="update-match-form" method="post" action="index.php?page=admin_tournament&id=<?php echo $tournament_id; ?>">
                            <input type="hidden" name="action" value="update_match">
                            <input type="hidden" id="match_id" name="match_id">
                            
                            <div class="mb-3">
                                <label class="form-label">Participants</label>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-center" id="participant1-name"></div>
                                    <div class="mx-2">vs</div>
                                    <div class="text-center" id="participant2-name"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Scores</label>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <input type="number" class="form-control" id="score1" name="score1" min="0" required>
                                    </div>
                                    <div class="mx-2">-</div>
                                    <div class="flex-grow-1">
                                        <input type="number" class="form-control" id="score2" name="score2" min="0" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Updating the match score will automatically determine the winner and advance them to the next round if applicable.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="update-match-form" class="btn btn-primary">Update Match</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            // Set match data in update match modal
            document.addEventListener('DOMContentLoaded', function() {
                const updateMatchModal = document.getElementById('updateMatchModal');
                if (updateMatchModal) {
                    updateMatchModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget;
                        const matchId = button.getAttribute('data-match-id');
                        const participant1 = button.getAttribute('data-participant1');
                        const participant2 = button.getAttribute('data-participant2');
                        const score1 = button.getAttribute('data-score1');
                        const score2 = button.getAttribute('data-score2');
                        const status = button.getAttribute('data-status');
                        
                        document.getElementById('match_id').value = matchId;
                        document.getElementById('participant1-name').textContent = participant1;
                        document.getElementById('participant2-name').textContent = participant2;
                        document.getElementById('score1').value = score1;
                        document.getElementById('score2').value = score2;
                    });
                }
            });
        </script>
    <?php endif; ?>
<?php endif; ?>