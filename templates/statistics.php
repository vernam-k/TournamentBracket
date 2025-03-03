<?php
/**
 * Statistics Page Template
 */

// Get all tournaments
$tournaments = get_all_tournaments();

// Get all participants
$participants = get_all_participants();

// Calculate overall statistics
$total_tournaments = count($tournaments);
$active_tournaments = 0;
$completed_tournaments = 0;
$total_matches = 0;
$completed_matches = 0;
$total_participants = count($participants);

// Participant statistics
$participant_stats = [];
foreach ($participants as $participant) {
    $participant_stats[$participant['id']] = [
        'id' => $participant['id'],
        'name' => $participant['name'],
        'tournaments' => 0,
        'matches' => 0,
        'wins' => 0,
        'losses' => 0,
        'win_percentage' => 0,
        'points_scored' => 0,
        'points_against' => 0
    ];
}

// Process tournaments and matches
foreach ($tournaments as $tournament) {
    if ($tournament['status'] === 'active') {
        $active_tournaments++;
    } elseif ($tournament['status'] === 'completed') {
        $completed_tournaments++;
    }
    
    // Count participants in tournaments
    foreach ($tournament['participants'] as $participant_id) {
        if (isset($participant_stats[$participant_id])) {
            $participant_stats[$participant_id]['tournaments']++;
        }
    }
    
    // Get matches for this tournament
    $matches = get_tournament_matches($tournament['id']);
    $total_matches += count($matches);
    
    // Process match statistics
    foreach ($matches as $match) {
        if ($match['status'] === 'completed') {
            $completed_matches++;
            
            // Update participant stats
            $participant1 = $match['participant1'];
            $participant2 = $match['participant2'];
            $winner = $match['winner'];
            
            if (isset($participant_stats[$participant1])) {
                $participant_stats[$participant1]['matches']++;
                $participant_stats[$participant1]['points_scored'] += $match['score1'];
                $participant_stats[$participant1]['points_against'] += $match['score2'];
                
                if ($winner === $participant1) {
                    $participant_stats[$participant1]['wins']++;
                } else {
                    $participant_stats[$participant1]['losses']++;
                }
            }
            
            if (isset($participant_stats[$participant2])) {
                $participant_stats[$participant2]['matches']++;
                $participant_stats[$participant2]['points_scored'] += $match['score2'];
                $participant_stats[$participant2]['points_against'] += $match['score1'];
                
                if ($winner === $participant2) {
                    $participant_stats[$participant2]['wins']++;
                } else {
                    $participant_stats[$participant2]['losses']++;
                }
            }
        }
    }
}

// Calculate win percentages
foreach ($participant_stats as &$stats) {
    if ($stats['matches'] > 0) {
        $stats['win_percentage'] = ($stats['wins'] / $stats['matches']) * 100;
    }
}

// Sort participants by wins (descending)
usort($participant_stats, function($a, $b) {
    if ($a['wins'] != $b['wins']) {
        return $b['wins'] - $a['wins'];
    }
    return $b['win_percentage'] - $a['win_percentage'];
});

// Get top participants (top 10)
$top_participants = array_slice($participant_stats, 0, 10);

// Get tournament type statistics
$tournament_types = [
    'single_elimination' => 0,
    'double_elimination' => 0,
    'round_robin' => 0
];

foreach ($tournaments as $tournament) {
    if (isset($tournament_types[$tournament['type']])) {
        $tournament_types[$tournament['type']]++;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-chart-bar me-2"></i>Tournament Statistics</h1>
</div>

<!-- Overall Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-primary"><?php echo $total_tournaments; ?></h1>
                <h5>Total Tournaments</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-success"><?php echo $active_tournaments; ?></h1>
                <h5>Active Tournaments</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-info"><?php echo $total_participants; ?></h1>
                <h5>Total Participants</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-warning"><?php echo $total_matches; ?></h1>
                <h5>Total Matches</h5>
            </div>
        </div>
    </div>
</div>

<!-- Tournament Type Distribution -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-pie-chart me-2"></i>Tournament Types</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
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
            <div class="col-md-6">
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
                            $statuses = [
                                'setup' => $total_tournaments - $active_tournaments - $completed_tournaments,
                                'active' => $active_tournaments,
                                'completed' => $completed_tournaments
                            ];
                            
                            foreach ($statuses as $status => $count): 
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

<!-- Top Participants -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-trophy me-2"></i>Top Participants</h5>
    </div>
    <div class="card-body">
        <?php if (empty($top_participants)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No participant statistics available yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Participant</th>
                            <th>Tournaments</th>
                            <th>Matches</th>
                            <th>Wins</th>
                            <th>Losses</th>
                            <th>Win %</th>
                            <th>Points Scored</th>
                            <th>Points Against</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_participants as $index => $stats): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($stats['name']); ?></td>
                                <td><?php echo $stats['tournaments']; ?></td>
                                <td><?php echo $stats['matches']; ?></td>
                                <td><?php echo $stats['wins']; ?></td>
                                <td><?php echo $stats['losses']; ?></td>
                                <td><?php echo round($stats['win_percentage'], 1); ?>%</td>
                                <td><?php echo $stats['points_scored']; ?></td>
                                <td><?php echo $stats['points_against']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Tournaments -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Recent Tournaments</h5>
    </div>
    <div class="card-body">
        <?php if (empty($tournaments)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No tournaments have been created yet.
            </div>
        <?php else: ?>
            <?php
            // Sort tournaments by creation date (newest first)
            usort($tournaments, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Get only the 5 most recent tournaments
            $recent_tournaments = array_slice($tournaments, 0, 5);
            ?>
            
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
                                    <a href="index.php?page=tournament&id=<?php echo $tournament['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <a href="index.php?page=tournaments" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>View All Tournaments
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>