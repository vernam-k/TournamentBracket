<?php
/**
 * Home Page Template
 */

// Get recent tournaments
$tournaments = get_all_tournaments();

// Sort tournaments by creation date (newest first)
usort($tournaments, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Get only the 5 most recent tournaments
$recent_tournaments = array_slice($tournaments, 0, 5);

// Get active tournaments
$active_tournaments = array_filter($tournaments, function($tournament) {
    return $tournament['status'] === 'active';
});

// Sort active tournaments by creation date (newest first)
usort($active_tournaments, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Get only the 5 most recent active tournaments
$active_tournaments = array_slice($active_tournaments, 0, 5);
?>

<!-- Hero Section -->
<div class="bg-primary text-white py-5 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4 fw-bold">Tournament Bracket System</h1>
                <p class="lead">Create and manage tournament brackets with ease. Track matches, make predictions, and view statistics.</p>
                <div class="mt-4">
                    <a href="index.php?page=tournaments" class="btn btn-light btn-lg me-2">
                        <i class="fas fa-trophy me-2"></i>View Tournaments
                    </a>
                    <?php if (is_admin()): ?>
                        <a href="index.php?page=admin_tournaments" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-plus me-2"></i>Create Tournament
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 d-none d-md-block">
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="container mb-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-trophy fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Multiple Tournament Types</h3>
                    <p class="card-text">Support for single elimination, double elimination, and round robin tournaments.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Predictions & Betting</h3>
                    <p class="card-text">Make predictions on match outcomes and compete with other users.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Detailed Statistics</h3>
                    <p class="card-text">View comprehensive statistics for tournaments, participants, and predictions.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active Tournaments Section -->
<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-play-circle me-2"></i>Active Tournaments</h2>
        <a href="index.php?page=tournaments" class="btn btn-outline-primary">View All</a>
    </div>
    
    <?php if (empty($active_tournaments)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No active tournaments at the moment.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($active_tournaments as $tournament): ?>
                <div class="col-md-6 col-lg-4 mb-4">
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
                                <strong>Current Round:</strong> <?php echo $tournament['current_round']; ?>
                            </p>
                            <p class="card-text">
                                <strong>Participants:</strong> <?php echo count($tournament['participants']); ?>
                            </p>
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
</div>

<!-- Recent Tournaments Section -->
<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-history me-2"></i>Recent Tournaments</h2>
        <a href="index.php?page=tournaments" class="btn btn-outline-primary">View All</a>
    </div>
    
    <?php if (empty($recent_tournaments)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No tournaments have been created yet.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
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
                                <a href="index.php?page=tournament&id=<?php echo $tournament['id']; ?>" class="btn btn-sm btn-primary">
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

<!-- Call to Action Section -->
<div class="bg-light py-5">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-md-8">
                <h2 class="mb-4">Ready to Create Your Tournament?</h2>
                <p class="lead mb-4">Join our platform to create and manage tournaments, make predictions, and track statistics.</p>
                <?php if (is_admin()): ?>
                    <a href="index.php?page=admin_tournaments" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Create Tournament
                    </a>
                <?php elseif (is_logged_in()): ?>
                    <a href="index.php?page=tournaments" class="btn btn-primary btn-lg">
                        <i class="fas fa-trophy me-2"></i>View Tournaments
                    </a>
                <?php else: ?>
                    <a href="index.php?page=register" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-user-plus me-2"></i>Register Now
                    </a>
                    <a href="index.php?page=login" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>