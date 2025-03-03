<?php
/**
 * Admin Dashboard Template
 */

// Require admin authentication
require_admin();

// Get statistics
$tournaments = get_all_tournaments();
$participants = get_all_participants();
$users = get_all_users();

// Count tournaments by type and status
$tournament_types = [
    'single_elimination' => 0,
    'double_elimination' => 0,
    'round_robin' => 0
];

$tournament_statuses = [
    'setup' => 0,
    'active' => 0,
    'completed' => 0
];

foreach ($tournaments as $tournament) {
    if (isset($tournament_types[$tournament['type']])) {
        $tournament_types[$tournament['type']]++;
    }
    
    if (isset($tournament_statuses[$tournament['status']])) {
        $tournament_statuses[$tournament['status']]++;
    }
}

// Get recent tournaments
usort($tournaments, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$recent_tournaments = array_slice($tournaments, 0, 5);

// Get active tournaments
$active_tournaments = array_filter($tournaments, function($tournament) {
    return $tournament['status'] === 'active';
});

// Get recent participants
usort($participants, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$recent_participants = array_slice($participants, 0, 5);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
    <div>
        <a href="index.php?page=admin_tournaments" class="btn btn-primary me-2">
            <i class="fas fa-trophy me-2"></i>Manage Tournaments
        </a>
        <a href="index.php?page=admin_participants" class="btn btn-primary">
            <i class="fas fa-users me-2"></i>Manage Participants
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-primary"><?php echo count($tournaments); ?></h1>
                <h5>Total Tournaments</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-success"><?php echo count($active_tournaments); ?></h1>
                <h5>Active Tournaments</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-info"><?php echo count($participants); ?></h1>
                <h5>Total Participants</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-warning"><?php echo count($users); ?></h1>
                <h5>Registered Users</h5>
            </div>
        </div>
    </div>
</div>

<!-- Tournament Statistics -->
<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Tournaments by Type</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_tournaments = count($tournaments);
                            foreach ($tournament_types as $type => $count): 
                                $percentage = $total_tournaments > 0 ? ($count / $total_tournaments) * 100 : 0;
                                $type_label = '';
                                switch ($type) {
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
                            ?>
                                <tr>
                                    <td><?php echo $type_label; ?></td>
                                    <td><?php echo $count; ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo round($percentage, 1); ?>%</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Tournaments by Status</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($tournament_statuses as $status => $count): 
                                $percentage = $total_tournaments > 0 ? ($count / $total_tournaments) * 100 : 0;
                                $status_class = '';
                                $status_label = '';
                                
                                switch ($status) {
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
                                <tr>
                                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                    <td><?php echo $count; ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar <?php echo $status_class; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo round($percentage, 1); ?>%</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tournaments -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Recent Tournaments</h5>
    </div>
    <div class="card-body">
        <?php if (empty($recent_tournaments)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No tournaments have been created yet.
            </div>
        <?php else: ?>
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
                        <?php foreach ($recent_tournaments as $tournament): ?>
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
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <a href="index.php?page=admin_tournaments" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>View All Tournaments
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Participants -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Recent Participants</h5>
    </div>
    <div class="card-body">
        <?php if (empty($recent_participants)): ?>
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
                        <?php foreach ($recent_participants as $participant): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($participant['name']); ?></td>
                                <td><?php echo htmlspecialchars(truncate_string($participant['description'], 50)); ?></td>
                                <td><?php echo format_date($participant['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?page=admin_participants&action=edit&id=<?php echo $participant['id']; ?>" class="btn btn-info" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <a href="index.php?page=admin_participants" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>View All Participants
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <a href="index.php?page=admin_tournaments&action=create" class="btn btn-primary w-100">
                    <i class="fas fa-plus me-2"></i>Create Tournament
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="index.php?page=admin_participants&action=create" class="btn btn-success w-100">
                    <i class="fas fa-user-plus me-2"></i>Add Participant
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="index.php?page=statistics" class="btn btn-info w-100">
                    <i class="fas fa-chart-bar me-2"></i>View Statistics
                </a>
            </div>
        </div>
    </div>
</div>