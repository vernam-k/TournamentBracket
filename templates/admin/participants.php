<?php
/**
 * Admin Participants Management Template
 */

// Require admin authentication
require_admin();

// Get action
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Get participant ID if editing
$participant_id = isset($_GET['id']) ? $_GET['id'] : '';

// Get participant data if editing
$participant = null;
if ($action === 'edit' && !empty($participant_id)) {
    $participant = get_participant($participant_id);
    
    if (!$participant) {
        set_flash_message('error', 'Participant not found.');
        redirect('index.php?page=admin_participants');
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create participant
    if ($action === 'create') {
        $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['name' => $name],
            ['name']
        );
        
        if (empty($missing_fields)) {
            // Create the participant
            $participant_id = generate_id('p');
            $participant = [
                'id' => $participant_id,
                'name' => $name,
                'description' => $description,
                'created_at' => date('Y-m-d\TH:i:s\Z')
            ];
            
            if (save_participant($participant)) {
                set_flash_message('success', 'Participant created successfully.');
                redirect('index.php?page=admin_participants');
            } else {
                set_flash_message('error', 'Failed to create participant.');
            }
        } else {
            set_flash_message('error', 'Please fill in all required fields.');
        }
    }
    
    // Update participant
    if ($action === 'edit') {
        $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['name' => $name],
            ['name']
        );
        
        if (empty($missing_fields)) {
            // Update the participant
            $participant['name'] = $name;
            $participant['description'] = $description;
            
            if (save_participant($participant)) {
                set_flash_message('success', 'Participant updated successfully.');
                redirect('index.php?page=admin_participants');
            } else {
                set_flash_message('error', 'Failed to update participant.');
            }
        } else {
            set_flash_message('error', 'Please fill in all required fields.');
        }
    }
    
    // Delete participant
    if ($action === 'delete') {
        $participant_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        
        if (!empty($participant_id)) {
            // Check if participant is in any tournaments
            $tournaments = get_all_tournaments();
            $in_tournament = false;
            
            foreach ($tournaments as $tournament) {
                if (in_array($participant_id, $tournament['participants'])) {
                    $in_tournament = true;
                    break;
                }
            }
            
            if ($in_tournament) {
                set_flash_message('error', 'Cannot delete participant because they are in one or more tournaments.');
            } else {
                if (delete_participant($participant_id)) {
                    set_flash_message('success', 'Participant deleted successfully.');
                    redirect('index.php?page=admin_participants');
                } else {
                    set_flash_message('error', 'Failed to delete participant.');
                }
            }
        } else {
            set_flash_message('error', 'Participant ID is required.');
        }
    }
}

// Get all participants for list view
$participants = [];
if ($action === 'list') {
    $participants = get_all_participants();
    
    // Sort participants by name
    usort($participants, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
}
?>

<?php if ($action === 'list'): ?>
    <!-- Participants List -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-users me-2"></i>Manage Participants</h1>
        <a href="index.php?page=admin_participants&action=create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Participant
        </a>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($participants)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No participants have been created yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $p): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['description']); ?></td>
                                    <td><?php echo format_date($p['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=admin_participants&action=edit&id=<?php echo $p['id']; ?>" class="btn btn-info" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" title="Delete" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                    data-participant-id="<?php echo $p['id']; ?>" 
                                                    data-participant-name="<?php echo htmlspecialchars($p['name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                                <p>Are you sure you want to delete the participant "<span id="participant-name"></span>"?</p>
                                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <form method="post" action="index.php?page=admin_participants&action=delete">
                                    <input type="hidden" name="id" id="participant-id">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Delete Participant</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                    // Set participant ID and name in delete modal
                    document.addEventListener('DOMContentLoaded', function() {
                        const deleteModal = document.getElementById('deleteModal');
                        if (deleteModal) {
                            deleteModal.addEventListener('show.bs.modal', function(event) {
                                const button = event.relatedTarget;
                                const participantId = button.getAttribute('data-participant-id');
                                const participantName = button.getAttribute('data-participant-name');
                                
                                document.getElementById('participant-id').value = participantId;
                                document.getElementById('participant-name').textContent = participantName;
                            });
                        }
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
    
<?php elseif ($action === 'create'): ?>
    <!-- Create Participant Form -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-plus me-2"></i>Add Participant</h1>
        <a href="index.php?page=admin_participants" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Participants
        </a>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Participant Details</h5>
        </div>
        <div class="card-body">
            <form method="post" action="index.php?page=admin_participants&action=create">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    <div class="form-text">Enter the participant's name (individual or team).</div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <div class="form-text">Optional description, such as team members, background information, etc.</div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Participant
                    </button>
                </div>
            </form>
        </div>
    </div>
    
<?php elseif ($action === 'edit'): ?>
    <!-- Edit Participant Form -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-edit me-2"></i>Edit Participant</h1>
        <a href="index.php?page=admin_participants" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Participants
        </a>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Participant Details</h5>
        </div>
        <div class="card-body">
            <form method="post" action="index.php?page=admin_participants&action=edit&id=<?php echo $participant_id; ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?php echo htmlspecialchars($participant['name']); ?>">
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($participant['description']); ?></textarea>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Participant
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Participant Tournament History -->
    <?php
    // Get tournaments this participant is in
    $tournaments = get_all_tournaments();
    $participant_tournaments = [];
    
    foreach ($tournaments as $tournament) {
        if (in_array($participant_id, $tournament['participants'])) {
            $participant_tournaments[] = $tournament;
        }
    }
    
    // Sort tournaments by creation date (newest first)
    usort($participant_tournaments, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    ?>
    
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0"><i class="fas fa-trophy me-2"></i>Tournament History</h5>
        </div>
        <div class="card-body">
            <?php if (empty($participant_tournaments)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>This participant has not been added to any tournaments yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tournament</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Result</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participant_tournaments as $tournament): ?>
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
                                    <td><?php echo format_date($tournament['created_at']); ?></td>
                                    <td>
                                        <?php if ($tournament['status'] === 'completed'): ?>
                                            <?php if ($tournament['winner'] === $participant_id): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-trophy me-1"></i>Winner
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Participated</span>
                                            <?php endif; ?>
                                        <?php elseif ($tournament['status'] === 'active'): ?>
                                            <span class="text-muted">In Progress</span>
                                        <?php else: ?>
                                            <span class="text-muted">Not Started</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="index.php?page=tournament&id=<?php echo $tournament['id']; ?>" class="btn btn-sm btn-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>