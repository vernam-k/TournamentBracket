/**
 * Tournament Bracket System - Tournament Visualization
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get tournament ID
    const tournamentContainer = document.getElementById('bracket-container');
    if (!tournamentContainer) return;
    
    const tournamentId = tournamentContainer.dataset.tournamentId;
    if (!tournamentId) return;
    
    // Fetch tournament bracket data
    fetchBracketData(tournamentId);
    
    // Initialize prediction modal if it exists
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
            
            // Show loading
            const submitButton = this;
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
            
            // Submit prediction via AJAX
            fetch('api/index.php?endpoint=predictions&action=create', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(predictModal);
                    modal.hide();
                    
                    // Show success message
                    showAlert('success', 'Prediction submitted successfully!');
                } else {
                    alert('Error: ' + (data.error || 'Failed to submit prediction.'));
                }
            })
            .catch(error => {
                // Reset button
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
});

/**
 * Fetch tournament bracket data
 * 
 * @param {string} tournamentId Tournament ID
 */
function fetchBracketData(tournamentId) {
    fetch(`api/index.php?endpoint=tournaments&action=get_bracket_data&id=${tournamentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.bracket_data) {
                renderBracket(data.bracket_data);
            }
        })
        .catch(error => {
            console.error('Error fetching bracket data:', error);
            document.getElementById('bracket-container').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>Failed to load tournament bracket. Please try again later.
                </div>
            `;
        });
}

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
    
    if (bracketData.type === 'single_elimination' || bracketData.type === 'round_robin') {
        renderSingleEliminationBracket(bracketData, container);
    } else if (bracketData.type === 'double_elimination') {
        renderDoubleEliminationBracket(bracketData, container);
    }
}

/**
 * Render single elimination bracket
 * 
 * @param {Object} bracketData Bracket data
 * @param {HTMLElement} container Container element
 */
function renderSingleEliminationBracket(bracketData, container) {
    const rounds = bracketData.rounds;
    
    // Create bracket wrapper
    const bracketWrapper = document.createElement('div');
    bracketWrapper.className = 'bracket-wrapper';
    
    // Create a column for each round
    for (const roundNumber in rounds) {
        const roundMatches = rounds[roundNumber];
        
        const roundColumn = document.createElement('div');
        roundColumn.className = 'bracket-round';
        roundColumn.dataset.round = roundNumber;
        
        // Add round header
        const roundHeader = document.createElement('div');
        roundHeader.className = 'bracket-round-title';
        roundHeader.textContent = `Round ${roundNumber}`;
        roundColumn.appendChild(roundHeader);
        
        // Add matches
        roundMatches.forEach(match => {
            const matchElement = createMatchElement(match);
            roundColumn.appendChild(matchElement);
        });
        
        bracketWrapper.appendChild(roundColumn);
    }
    
    container.appendChild(bracketWrapper);
}

/**
 * Render double elimination bracket
 * 
 * @param {Object} bracketData Bracket data
 * @param {HTMLElement} container Container element
 */
function renderDoubleEliminationBracket(bracketData, container) {
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
        roundHeader.className = 'bracket-round-title';
        roundHeader.textContent = `Round ${roundNumber}`;
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
        roundHeader.className = 'bracket-round-title';
        roundHeader.textContent = `Round ${roundNumber}`;
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
        for (const roundNumber in brackets.finals) {
            const roundMatches = brackets.finals[roundNumber];
            
            roundMatches.forEach(match => {
                const matchElement = createMatchElement(match);
                finalsWrapper.appendChild(matchElement);
            });
        }
        
        finalsContainer.appendChild(finalsWrapper);
        container.appendChild(finalsContainer);
    }
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