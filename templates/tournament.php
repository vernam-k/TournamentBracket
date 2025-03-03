<?php
/**
 * Single Tournament View Template
 */

// Get tournament ID
$tournament_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($tournament_id)) {
    set_flash_message('error', 'Tournament ID is required.');
    redirect('index.php?page=tournaments');
}

// Get tournament data
$tournament = get_tournament($tournament_id);

if (!$tournament) {
    set_flash_message('error', 'Tournament not found.');
    redirect('index.php?page=tournaments');
}

// Get tournament matches
$matches = get_tournament_matches($tournament_id);

// Get participants
$participants = [];
foreach ($tournament['participants'] as $participant_id) {
    $participant = get_participant($participant_id);
    if ($participant) {
        $participants[$participant_id] = $participant;
    }
}

// Get bracket data for visualization
$bracket_data = get_tournament_bracket_data($tournament_id);

// Get standings for round robin tournaments
$standings = [];
if ($tournament['type'] === 'round_robin') {
    $standings = get_round_robin_standings($tournament_id);
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
?>

<!-- Tournament Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
    <div>
        <?php if (is_admin()): ?>
            <a href="index.php?page=admin_tournament&id=<?php echo $tournament_id; ?>" class="btn btn-primary me-2">
                <i class="fas fa-edit me-2"></i>Edit Tournament
            </a>
        <?php endif; ?>
        <button class="btn btn-outline-primary" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Print Bracket
        </button>
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
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> <?php echo $tournament_type_label; ?></p>
                        <p>
                            <strong>Status:</strong> 
                            <span class="badge <?php echo $tournament_status_class; ?>"><?php echo $tournament_status_label; ?></span>
                        </p>
                        <p><strong>Created:</strong> <?php echo format_date($tournament['created_at']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Participants:</strong> <?php echo count($participants); ?></p>
                        <?php if ($tournament['status'] === 'active'): ?>
                            <p><strong>Current Round:</strong> <?php echo $tournament['current_round']; ?></p>
                        <?php endif; ?>
                        <?php if ($tournament['status'] === 'completed' && $tournament['winner']): ?>
                            <p>
                                <strong>Winner:</strong> 
                                <?php 
                                $winner = isset($participants[$tournament['winner']]) ? $participants[$tournament['winner']] : null;
                                echo $winner ? htmlspecialchars($winner['name']) : 'Unknown';
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Participants</h5>
            </div>
            <div class="card-body">
                <?php if (empty($participants)): ?>
                    <p class="text-muted">No participants yet.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($participants as $participant): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($participant['name']); ?>
                                <?php if ($tournament['winner'] === $participant['id']): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-trophy me-1"></i>Winner
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tournament Bracket -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-sitemap me-2"></i>Tournament Bracket</h5>
    </div>
    <div class="card-body">
        <div id="bracket-container" class="tournament-bracket" data-tournament-id="<?php echo $tournament_id; ?>">
            <?php if ($tournament['status'] === 'setup'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>The tournament has not started yet. Brackets will be generated when the tournament begins.
                </div>
            <?php else: ?>
                <!-- Bracket visualization will be rendered by JavaScript -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading bracket...</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Round Robin Standings (if applicable) -->
<?php if ($tournament['type'] === 'round_robin' && !empty($standings)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-list-ol me-2"></i>Standings</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Participant</th>
                            <th>Wins</th>
                            <th>Losses</th>
                            <th>Ties</th>
                            <th>Points</th>
                            <th>Points Against</th>
                            <th>Differential</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standings as $index => $standing): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($standing['name']); ?></td>
                                <td><?php echo $standing['wins']; ?></td>
                                <td><?php echo $standing['losses']; ?></td>
                                <td><?php echo $standing['ties']; ?></td>
                                <td><?php echo $standing['points']; ?></td>
                                <td><?php echo $standing['points_against']; ?></td>
                                <td><?php echo $standing['point_differential']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Matches -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-gamepad me-2"></i>Matches</h5>
    </div>
    <div class="card-body">
        <?php if (empty($matches)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No matches have been created yet.
            </div>
        <?php else: ?>
            <div class="accordion" id="matchesAccordion">
                <?php 
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
                
                foreach ($matches_by_round as $round => $round_matches):
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
                                                <?php if (is_logged_in() && $tournament['status'] === 'active'): ?>
                                                    <th>Prediction</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($round_matches as $match): ?>
                                                <tr class="match-row" data-match-id="<?php echo $match['id']; ?>">
                                                    <td><?php echo $match['match_number']; ?></td>
                                                    <td>
                                                        <?php 
                                                        if ($match['participant1']) {
                                                            $participant1 = isset($participants[$match['participant1']]) ? $participants[$match['participant1']] : null;
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
                                                            $participant2 = isset($participants[$match['participant2']]) ? $participants[$match['participant2']] : null;
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
                                                    <?php if (is_logged_in() && $tournament['status'] === 'active'): ?>
                                                        <td>
                                                            <?php if ($match['participant1'] && $match['participant2'] && $match['status'] !== 'completed'): ?>
                                                                <button class="btn btn-sm btn-outline-primary predict-btn" 
                                                                        data-bs-toggle="modal" data-bs-target="#predictModal"
                                                                        data-match-id="<?php echo $match['id']; ?>"
                                                                        data-participant1="<?php echo $match['participant1']; ?>"
                                                                        data-participant2="<?php echo $match['participant2']; ?>"
                                                                        data-participant1-name="<?php echo isset($participants[$match['participant1']]) ? htmlspecialchars($participants[$match['participant1']]['name']) : 'Unknown'; ?>"
                                                                        data-participant2-name="<?php echo isset($participants[$match['participant2']]) ? htmlspecialchars($participants[$match['participant2']]['name']) : 'Unknown'; ?>">
                                                                    <i class="fas fa-chart-line me-1"></i>Predict
                                                                </button>
                                                            <?php elseif ($match['status'] === 'completed'): ?>
                                                                <!-- Prediction result would be shown here -->
                                                                <span class="text-muted">Closed</span>
                                                            <?php else: ?>
                                                                <span class="text-muted">Not available</span>
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

<!-- Prediction Modal -->
<?php if (is_logged_in() && $tournament['status'] === 'active'): ?>
    <div class="modal fade" id="predictModal" tabindex="-1" aria-labelledby="predictModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="predictModalLabel"><i class="fas fa-chart-line me-2"></i>Predict Match Outcome</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="prediction-form">
                        <input type="hidden" id="match_id" name="match_id">
                        <input type="hidden" id="tournament_id" name="tournament_id" value="<?php echo $tournament_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Select Winner:</label>
                            <div class="d-flex justify-content-between">
                                <div class="form-check form-check-inline flex-grow-1">
                                    <input class="form-check-input" type="radio" name="predicted_winner" id="participant1" value="">
                                    <label class="form-check-label" for="participant1" id="participant1-label"></label>
                                </div>
                                <div class="form-check form-check-inline flex-grow-1">
                                    <input class="form-check-input" type="radio" name="predicted_winner" id="participant2" value="">
                                    <label class="form-check-label" for="participant2" id="participant2-label"></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Your prediction will be locked once the match starts. Points are awarded for correct predictions.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submit-prediction">Submit Prediction</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- CSS for bracket visualization -->
<style>
    .tournament-bracket {
        overflow-x: auto;
        padding: 20px 0;
    }
    
    .bracket-round {
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        min-height: 400px;
    }
    
    .bracket-wrapper {
        display: flex;
        flex-direction: row;
        justify-content: space-around;
    }
    
    .bracket-match {
        position: relative;
        width: 200px;
        margin: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .bracket-match-header {
        padding: 5px 10px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
        font-size: 0.8rem;
    }
    
    .bracket-match-body {
        padding: 10px;
    }
    
    .bracket-participant {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
    }
    
    .bracket-participant.winner {
        font-weight: bold;
        color: #28a745;
    }
    
    .bracket-participant-name {
        flex-grow: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .bracket-participant-score {
        margin-left: 10px;
    }
    
    .bracket-connector {
        position: absolute;
        right: -10px;
        top: 50%;
        width: 10px;
        height: 2px;
        background-color: #ddd;
    }
    
    .bracket-connector-vertical {
        position: absolute;
        right: -10px;
        height: 50%;
        width: 2px;
        background-color: #ddd;
    }
    
    .bracket-connector-down {
        top: 50%;
    }
    
    .bracket-connector-up {
        bottom: 50%;
    }
    
    /* Print styles */
    @media print {
        .tournament-bracket {
            overflow: visible;
        }
        
        .no-print {
            display: none !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .card-header {
            background-color: #f8f9fa !important;
            color: #000 !important;
            border-bottom: 1px solid #ddd !important;
        }
        
        .bracket-match {
            box-shadow: none !important;
        }
    }
</style>

<!-- JavaScript for bracket visualization -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize bracket visualization
        const bracketData = <?php echo json_encode($bracket_data); ?>;
        if (bracketData) {
            renderBracket(bracketData);
        }
        
        // Initialize prediction modal
        const predictModal = document.getElementById('predictModal');
        if (predictModal) {
            predictModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const matchId = button.getAttribute('data-match-id');
                const participant1 = button.getAttribute('data-participant1');
                const participant2 = button.getAttribute('data-participant2');
                const participant1Name = button.getAttribute('data-participant1-name');
                const participant2Name = button.getAttribute('data-participant2-name');
                
                document.getElementById('match_id').value = matchId;
                document.getElementById('participant1').value = participant1;
                document.getElementById('participant2').value = participant2;
                document.getElementById('participant1-label').textContent = participant1Name;
                document.getElementById('participant2-label').textContent = participant2Name;
            });
            
            // Handle prediction submission
            document.getElementById('submit-prediction').addEventListener('click', function() {
                const form = document.getElementById('prediction-form');
                const formData = new FormData(form);
                
                // Validate form
                const predictedWinner = formData.get('predicted_winner');
                if (!predictedWinner) {
                    alert('Please select a winner.');
                    return;
                }
                
                // Submit prediction via AJAX
                fetch('api/index.php?endpoint=predictions&action=create', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(predictModal);
                        modal.hide();
                        
                        // Show success message
                        alert('Prediction submitted successfully!');
                    } else {
                        alert('Error: ' + (data.error || 'Failed to submit prediction.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        }
    });
    
    /**
     * Render tournament bracket
     * 
     * @param {Object} bracketData Bracket data
     */
    function renderBracket(bracketData) {
        const container = document.getElementById('bracket-container');
        if (!container) return;
        
        // Clear loading indicator
        container.innerHTML = '';
        
        // Create bracket wrapper
        const bracketWrapper = document.createElement('div');
        bracketWrapper.className = 'bracket-wrapper';
        
        if (bracketData.type === 'single_elimination' || bracketData.type === 'round_robin') {
            // Render single elimination or round robin bracket
            const rounds = bracketData.rounds;
            
            // Create a column for each round
            for (const roundNumber in rounds) {
                const roundMatches = rounds[roundNumber];
                
                const roundColumn = document.createElement('div');
                roundColumn.className = 'bracket-round';
                roundColumn.dataset.round = roundNumber;
                
                // Add round header
                const roundHeader = document.createElement('div');
                roundHeader.className = 'text-center mb-3';
                roundHeader.innerHTML = `<h6>Round ${roundNumber}</h6>`;
                roundColumn.appendChild(roundHeader);
                
                // Add matches
                roundMatches.forEach(match => {
                    const matchElement = createMatchElement(match);
                    roundColumn.appendChild(matchElement);
                });
                
                bracketWrapper.appendChild(roundColumn);
            }
        } else if (bracketData.type === 'double_elimination') {
            // Render double elimination bracket
            const brackets = bracketData.brackets;
            
            // Create a container for winners bracket
            const winnersContainer = document.createElement('div');
            winnersContainer.className = 'mb-4';
            
            const winnersHeader = document.createElement('h5');
            winnersHeader.className = 'text-center mb-3';
            winnersHeader.textContent = 'Winners Bracket';
            winnersContainer.appendChild(winnersHeader);
            
            const winnersWrapper = document.createElement('div');
            winnersWrapper.className = 'bracket-wrapper';
            
            // Create a column for each round in winners bracket
            for (const roundNumber in brackets.winners) {
                const roundMatches = brackets.winners[roundNumber];
                
                const roundColumn = document.createElement('div');
                roundColumn.className = 'bracket-round';
                roundColumn.dataset.round = roundNumber;
                
                // Add round header
                const roundHeader = document.createElement('div');
                roundHeader.className = 'text-center mb-3';
                roundHeader.innerHTML = `<h6>Round ${roundNumber}</h6>`;
                roundColumn.appendChild(roundHeader);
                
                // Add matches
                roundMatches.forEach(match => {
                    const matchElement = createMatchElement(match);
                    roundColumn.appendChild(matchElement);
                });
                
                winnersWrapper.appendChild(roundColumn);
            }
            
            winnersContainer.appendChild(winnersWrapper);
            container.appendChild(winnersContainer);
            
            // Create a container for losers bracket
            const losersContainer = document.createElement('div');
            losersContainer.className = 'mb-4';
            
            const losersHeader = document.createElement('h5');
            losersHeader.className = 'text-center mb-3';
            losersHeader.textContent = 'Losers Bracket';
            losersContainer.appendChild(losersHeader);
            
            const losersWrapper = document.createElement('div');
            losersWrapper.className = 'bracket-wrapper';
            
            // Create a column for each round in losers bracket
            for (const roundNumber in brackets.losers) {
                const roundMatches = brackets.losers[roundNumber];
                
                const roundColumn = document.createElement('div');
                roundColumn.className = 'bracket-round';
                roundColumn.dataset.round = roundNumber;
                
                // Add round header
                const roundHeader = document.createElement('div');
                roundHeader.className = 'text-center mb-3';
                roundHeader.innerHTML = `<h6>Round ${roundNumber}</h6>`;
                roundColumn.appendChild(roundHeader);
                
                // Add matches
                roundMatches.forEach(match => {
                    const matchElement = createMatchElement(match);
                    roundColumn.appendChild(matchElement);
                });
                
                losersWrapper.appendChild(roundColumn);
            }
            
            losersContainer.appendChild(losersWrapper);
            container.appendChild(losersContainer);
            
            // Create a container for finals
            if (brackets.finals) {
                const finalsContainer = document.createElement('div');
                finalsContainer.className = 'mb-4';
                
                const finalsHeader = document.createElement('h5');
                finalsHeader.className = 'text-center mb-3';
                finalsHeader.textContent = 'Finals';
                finalsContainer.appendChild(finalsHeader);
                
                const finalsWrapper = document.createElement('div');
                finalsWrapper.className = 'bracket-wrapper justify-content-center';
                
                // Add finals matches
                brackets.finals[Object.keys(brackets.finals)[0]].forEach(match => {
                    const matchElement = createMatchElement(match);
                    finalsWrapper.appendChild(matchElement);
                });
                
                finalsContainer.appendChild(finalsWrapper);
                container.appendChild(finalsContainer);
            }
            
            return;
        }
        
        container.appendChild(bracketWrapper);
    }
    
    /**
     * Create a match element
     * 
     * @param {Object} match Match data
     * @return {HTMLElement} Match element
     */
    function createMatchElement(match) {
        const matchElement = document.createElement('div');
        matchElement.className = 'bracket-match';
        matchElement.dataset.matchId = match.id;
        
        // Match header
        const matchHeader = document.createElement('div');
        matchHeader.className = 'bracket-match-header';
        matchHeader.textContent = `Match ${match.match_number}`;
        matchElement.appendChild(matchHeader);
        
        // Match body
        const matchBody = document.createElement('div');
        matchBody.className = 'bracket-match-body';
        
        // Participant 1
        const participant1 = document.createElement('div');
        participant1.className = 'bracket-participant';
        if (match.winner === match.participant1.id) {
            participant1.classList.add('winner');
        }
        
        const participant1Name = document.createElement('div');
        participant1Name.className = 'bracket-participant-name';
        participant1Name.textContent = match.participant1.name || 'TBD';
        participant1.appendChild(participant1Name);
        
        const participant1Score = document.createElement('div');
        participant1Score.className = 'bracket-participant-score';
        participant1Score.textContent = match.status === 'completed' ? match.participant1.score : '-';
        participant1.appendChild(participant1Score);
        
        matchBody.appendChild(participant1);
        
        // Participant 2
        const participant2 = document.createElement('div');
        participant2.className = 'bracket-participant';
        if (match.winner === match.participant2.id) {
            participant2.classList.add('winner');
        }
        
        const participant2Name = document.createElement('div');
        participant2Name.className = 'bracket-participant-name';
        participant2Name.textContent = match.participant2.name || 'TBD';
        participant2.appendChild(participant2Name);
        
        const participant2Score = document.createElement('div');
        participant2Score.className = 'bracket-participant-score';
        participant2Score.textContent = match.status === 'completed' ? match.participant2.score : '-';
        participant2.appendChild(participant2Score);
        
        matchBody.appendChild(participant2);
        
        matchElement.appendChild(matchBody);
        
        // Add connector lines
        if (match.next_match) {
            const connector = document.createElement('div');
            connector.className = 'bracket-connector';
            matchElement.appendChild(connector);
        }
        
        return matchElement;
    }
    
    /**
     * Update bracket visualization
     * 
     * @param {Object} bracketData Bracket data
     */
    function updateBracketVisualization(bracketData) {
        renderBracket(bracketData);
        
        // Also update match rows in the matches table
        if (bracketData.type === 'single_elimination' || bracketData.type === 'round_robin') {
            const rounds = bracketData.rounds;
            
            for (const roundNumber in rounds) {
                const roundMatches = rounds[roundNumber];
                
                roundMatches.forEach(match => {
                    updateMatchRow(match);
                });
            }
        } else if (bracketData.type === 'double_elimination') {
            const brackets = bracketData.brackets;
            
            // Update winners bracket matches
            for (const roundNumber in brackets.winners) {
                const roundMatches = brackets.winners[roundNumber];
                
                roundMatches.forEach(match => {
                    updateMatchRow(match);
                });
            }
            
            // Update losers bracket matches
            for (const roundNumber in brackets.losers) {
                const roundMatches = brackets.losers[roundNumber];
                
                roundMatches.forEach(match => {
                    updateMatchRow(match);
                });
            }
            
            // Update finals matches
            if (brackets.finals) {
                for (const roundNumber in brackets.finals) {
                    const roundMatches = brackets.finals[roundNumber];
                    
                    roundMatches.forEach(match => {
                        updateMatchRow(match);
                    });
                }
            }
        }
    }
    
    /**
     * Update a match row in the matches table
     * 
     * @param {Object} match Match data
     */
    function updateMatchRow(match) {
        const matchRow = document.querySelector(`.match-row[data-match-id="${match.id}"]`);
        if (!matchRow) return;
        
        // Update participant names
        const participant1Cell = matchRow.cells[1];
        const participant2Cell = matchRow.cells[3];
        
        if (match.participant1.id) {
            participant1Cell.textContent = match.participant1.name || 'Unknown';
        } else {
            participant1Cell.innerHTML = '<span class="text-muted">TBD</span>';
        }
        
        if (match.participant2.id) {
            participant2Cell.textContent = match.participant2.name || 'Unknown';
        } else {
            participant2Cell.innerHTML = '<span class="text-muted">TBD</span>';
        }
        
        // Update score
        const scoreCell = matchRow.cells[2];
        if (match.status === 'completed') {
            scoreCell.innerHTML = `<span class="badge bg-light text-dark">${match.participant1.score} - ${match.participant2.score}</span>`;
        } else {
            scoreCell.innerHTML = '<span class="text-muted">-</span>';
        }
        
        // Update status
        const statusCell = matchRow.cells[4];
        let statusClass = '';
        let statusLabel = '';
        
        switch (match.status) {
            case 'pending':
                statusClass = 'bg-secondary';
                statusLabel = 'Pending';
                break;
            case 'in_progress':
                statusClass = 'bg-warning text-dark';
                statusLabel = 'In Progress';
                break;
            case 'completed':
                statusClass = 'bg-success';
                statusLabel = 'Completed';
                break;
        }
        
        statusCell.innerHTML = `<span class="badge ${statusClass}">${statusLabel}</span>`;
        
        // Update prediction button if present
        if (matchRow.cells.length > 5) {
            const predictionCell = matchRow.cells[5];
            const predictButton = predictionCell.querySelector('.predict-btn');
            
            if (predictButton) {
                if (match.participant1.id && match.participant2.id && match.status !== 'completed') {
                    predictButton.setAttribute('data-participant1', match.participant1.id);
                    predictButton.setAttribute('data-participant2', match.participant2.id);
                    predictButton.setAttribute('data-participant1-name', match.participant1.name || 'Unknown');
                    predictButton.setAttribute('data-participant2-name', match.participant2.name || 'Unknown');
                } else if (match.status === 'completed') {
                    predictionCell.innerHTML = '<span class="text-muted">Closed</span>';
                }
            }
        }
    }
</script>