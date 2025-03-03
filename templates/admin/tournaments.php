<?php
/**
 * Admin Tournaments Management Template
 */

// Require admin authentication
require_admin();

// Get action
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create tournament
    if ($action === 'create') {
        $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
        $type = isset($_POST['type']) ? sanitize_input($_POST['type']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['name' => $name, 'type' => $type],
            ['name', 'type']
        );
        
        if (empty($missing_fields)) {
            // Create the tournament
            $tournament_id = create_tournament($name, $type, $_SESSION['username']);
            
            if ($tournament_id) {
                set_flash_message('success', 'Tournament created successfully.');
                redirect('index.php?page=admin_tournament&id=' . $tournament_id);
            } else {
                set_flash_message('error', 'Failed to create tournament.');
            }
        } else {
            set_flash_message('error', 'Please fill in all required fields.');
        }
    }
    
    // Delete tournament
    if ($action === 'delete') {
        $tournament_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        
        if (!empty($tournament_id)) {
            if (delete_tournament($tournament_id)) {
                set_flash_message('success', 'Tournament deleted successfully.');
                redirect('index.php?page=admin_tournaments');
            } else {
                set_flash_message('error', 'Failed to delete tournament.');
            }
        } else {
            set_flash_message('error', 'Tournament ID is required.');
        }
    }
}

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

<?php if ($action === 'list'): ?>
    <!-- Tournaments List -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-trophy me-2"></i>Manage Tournaments</h1>
        <a href="index.php?page=admin_tournaments&action=create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Tournament
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="get" action="index.php" id="filter-form">
                <input type="hidden" name="page" value="admin_tournaments">
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
                    <a href="index.php?page=admin_tournaments" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tournaments Table -->
    <?php if (empty($tournaments)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No tournaments found matching your criteria.
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Participants</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tournaments as $tournament): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tournament['name']); ?></td>
                                    <td>
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
                                    </td>
                                    <td>
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
                                    </td>
                                    <td><?php echo count($tournament['participants']); ?></td>
                                    <td><?php echo format_date($tournament['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=tournament&id=<?php echo $tournament['id']; ?>" class="btn btn-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="index.php?page=admin_tournament&id=<?php echo $tournament['id']; ?>" class="btn btn-info" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" title="Delete" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                    data-tournament-id="<?php echo $tournament['id']; ?>" 
                                                    data-tournament-name="<?php echo htmlspecialchars($tournament['name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the tournament "<span id="tournament-name"></span>"?</p>
                        <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. All tournament data, including matches and results, will be permanently deleted.</p>
                    </div>
                    <div class="modal-footer">
                        <form method="post" action="index.php?page=admin_tournaments&action=delete">
                            <input type="hidden" name="id" id="tournament-id">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Tournament</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            // Set tournament ID and name in delete modal
            document.addEventListener('DOMContentLoaded', function() {
                const deleteModal = document.getElementById('deleteModal');
                if (deleteModal) {
                    deleteModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget;
                        const tournamentId = button.getAttribute('data-tournament-id');
                        const tournamentName = button.getAttribute('data-tournament-name');
                        
                        document.getElementById('tournament-id').value = tournamentId;
                        document.getElementById('tournament-name').textContent = tournamentName;
                    });
                }
            });
        </script>
    <?php endif; ?>
    
<?php elseif ($action === 'create'): ?>
    <!-- Create Tournament Form -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-plus me-2"></i>Create Tournament</h1>
        <a href="index.php?page=admin_tournaments" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Tournaments
        </a>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Tournament Details</h5>
        </div>
        <div class="card-body">
            <form method="post" action="index.php?page=admin_tournaments&action=create">
                <div class="mb-3">
                    <label for="name" class="form-label">Tournament Name</label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="type" class="form-label">Tournament Type</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="">Select a type</option>
                        <?php foreach ($tournament_types as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo isset($_POST['type']) && $_POST['type'] === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-text">
                        <strong>Note:</strong> After creating the tournament, you will be able to add participants and configure additional settings.
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Tournament
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tournament Types Information -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Tournament Types Information</h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="tournamentTypesAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="singleEliminationHeading">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#singleEliminationCollapse" aria-expanded="true" aria-controls="singleEliminationCollapse">
                            Single Elimination
                        </button>
                    </h2>
                    <div id="singleEliminationCollapse" class="accordion-collapse collapse show" aria-labelledby="singleEliminationHeading" data-bs-parent="#tournamentTypesAccordion">
                        <div class="accordion-body">
                            <p>In a single elimination tournament, participants who lose a match are immediately eliminated from the tournament. Each round reduces the number of participants by half, until only one participant remains as the winner.</p>
                            <p><strong>Key features:</strong></p>
                            <ul>
                                <li>Fastest tournament format to complete</li>
                                <li>Each participant plays at least one match</li>
                                <li>Best for large numbers of participants</li>
                                <li>Simple bracket structure</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="doubleEliminationHeading">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#doubleEliminationCollapse" aria-expanded="false" aria-controls="doubleEliminationCollapse">
                            Double Elimination
                        </button>
                    </h2>
                    <div id="doubleEliminationCollapse" class="accordion-collapse collapse" aria-labelledby="doubleEliminationHeading" data-bs-parent="#tournamentTypesAccordion">
                        <div class="accordion-body">
                            <p>In a double elimination tournament, participants must lose two matches before being eliminated. The tournament consists of a winners bracket and a losers bracket. Participants who lose in the winners bracket move to the losers bracket for a second chance.</p>
                            <p><strong>Key features:</strong></p>
                            <ul>
                                <li>More matches per participant</li>
                                <li>Second chance for participants who lose once</li>
                                <li>More accurate ranking of top participants</li>
                                <li>More complex bracket structure</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="roundRobinHeading">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roundRobinCollapse" aria-expanded="false" aria-controls="roundRobinCollapse">
                            Round Robin
                        </button>
                    </h2>
                    <div id="roundRobinCollapse" class="accordion-collapse collapse" aria-labelledby="roundRobinHeading" data-bs-parent="#tournamentTypesAccordion">
                        <div class="accordion-body">
                            <p>In a round robin tournament, each participant plays against every other participant once. The winner is determined by the participant with the best overall record (most wins, or highest point total).</p>
                            <p><strong>Key features:</strong></p>
                            <ul>
                                <li>Maximum number of matches for all participants</li>
                                <li>Most fair format for determining overall skill</li>
                                <li>No early eliminations</li>
                                <li>Best for small to medium number of participants</li>
                                <li>Includes detailed standings and statistics</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>