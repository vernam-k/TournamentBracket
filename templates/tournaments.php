<?php
/**
 * Tournaments List Template
 */

// Get all tournaments
$tournaments = get_all_tournaments();

// Sort tournaments by creation date (newest first)
usort($tournaments, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Filter tournaments by status if requested
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

if (!empty($status_filter)) {
    $tournaments = array_filter($tournaments, function($tournament) use ($status_filter) {
        return $tournament['status'] === $status_filter;
    });
}

// Get tournament types for filter
$tournament_types = [
    'single_elimination' => 'Single Elimination',
    'double_elimination' => 'Double Elimination',
    'round_robin' => 'Round Robin'
];

// Get tournament statuses for filter
$tournament_statuses = [
    'setup' => 'Setup',
    'active' => 'Active',
    'completed' => 'Completed'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-trophy me-2"></i>Tournaments</h1>
    <?php if (is_admin()): ?>
        <a href="index.php?page=admin_tournaments" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Tournament
        </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
    </div>
    <div class="card-body">
        <form method="get" action="index.php" id="filter-form">
            <input type="hidden" name="page" value="tournaments">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <?php foreach ($tournament_statuses as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $status_filter === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($tournament_types as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo isset($_GET['type']) && $_GET['type'] === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Tournament name..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Apply Filters
                </button>
                <a href="index.php?page=tournaments" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tournaments List -->
<?php if (empty($tournaments)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>No tournaments found matching your criteria.
    </div>
<?php else: ?>
    <div class="row" id="tournaments-container">
        <?php foreach ($tournaments as $tournament): ?>
            <div class="col-md-6 col-lg-4 mb-4 tournament-card" data-tournament-id="<?php echo $tournament['id']; ?>">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <?php echo htmlspecialchars($tournament['name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <strong>Type:</strong> 
                            <?php 
                            $type_label = '';
                            switch ($tournament['type']) {
                                case 'single_elimination':
                                    $type_label = 'Single Elimination';
                                    break;
                                case 'double_elimination':
                                    $type_label = 'Double Elimination';
                                    break;
                                case 'round_robin':
                                    $type_label = 'Round Robin';
                                    break;
                            }
                            echo $type_label;
                            ?>
                        </p>
                        <p class="card-text">
                            <strong>Status:</strong> 
                            <?php 
                            $status_class = '';
                            $status_label = '';
                            
                            switch ($tournament['status']) {
                                case 'setup':
                                    $status_class = 'bg-secondary';
                                    $status_label = 'Setup';
                                    break;
                                case 'active':
                                    $status_class = 'bg-success';
                                    $status_label = 'Active';
                                    break;
                                case 'completed':
                                    $status_class = 'bg-info';
                                    $status_label = 'Completed';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                        </p>
                        <p class="card-text">
                            <strong>Participants:</strong> <?php echo count($tournament['participants']); ?>
                        </p>
                        <?php if ($tournament['status'] === 'active'): ?>
                            <p class="card-text">
                                <strong>Current Round:</strong> <?php echo $tournament['current_round']; ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($tournament['status'] === 'completed' && $tournament['winner']): ?>
                            <p class="card-text">
                                <strong>Winner:</strong> 
                                <?php 
                                $winner = get_participant($tournament['winner']);
                                echo $winner ? htmlspecialchars($winner['name']) : 'Unknown';
                                ?>
                            </p>
                        <?php endif; ?>
                        <p class="card-text">
                            <strong>Created:</strong> <?php echo format_date($tournament['created_at']); ?>
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

<!-- JavaScript for real-time updates -->
<script>
    // This function will be called by the polling mechanism in footer.php
    function updateTournamentsList(tournaments) {
        if (!tournaments || !Array.isArray(tournaments)) return;
        
        const container = document.getElementById('tournaments-container');
        if (!container) return;
        
        // Update existing tournament cards
        tournaments.forEach(tournament => {
            const card = document.querySelector(`.tournament-card[data-tournament-id="${tournament.id}"]`);
            if (card) {
                // Update status
                const statusBadge = card.querySelector('.badge');
                if (statusBadge) {
                    let statusClass = '';
                    let statusLabel = '';
                    
                    switch (tournament.status) {
                        case 'setup':
                            statusClass = 'bg-secondary';
                            statusLabel = 'Setup';
                            break;
                        case 'active':
                            statusClass = 'bg-success';
                            statusLabel = 'Active';
                            break;
                        case 'completed':
                            statusClass = 'bg-info';
                            statusLabel = 'Completed';
                            break;
                    }
                    
                    statusBadge.className = `badge ${statusClass}`;
                    statusBadge.textContent = statusLabel;
                }
                
                // Update current round if active
                if (tournament.status === 'active') {
                    const roundElement = card.querySelector('p:contains("Current Round")');
                    if (roundElement) {
                        roundElement.innerHTML = `<strong>Current Round:</strong> ${tournament.current_round}`;
                    } else {
                        // Add current round if not present
                        const participantsElement = card.querySelector('p:contains("Participants")');
                        if (participantsElement) {
                            const roundElement = document.createElement('p');
                            roundElement.className = 'card-text';
                            roundElement.innerHTML = `<strong>Current Round:</strong> ${tournament.current_round}`;
                            participantsElement.after(roundElement);
                        }
                    }
                }
                
                // Update winner if completed
                if (tournament.status === 'completed' && tournament.winner) {
                    const winnerElement = card.querySelector('p:contains("Winner")');
                    if (winnerElement) {
                        // Winner element exists, update it
                        // Note: We can't get the winner's name here without an API call
                        // So we'll just show "Updated" for now
                        winnerElement.innerHTML = `<strong>Winner:</strong> Updated`;
                    } else {
                        // Add winner element if not present
                        const participantsElement = card.querySelector('p:contains("Participants")');
                        if (participantsElement) {
                            const winnerElement = document.createElement('p');
                            winnerElement.className = 'card-text';
                            winnerElement.innerHTML = `<strong>Winner:</strong> Updated`;
                            participantsElement.after(winnerElement);
                        }
                    }
                }
            }
        });
    }
</script>