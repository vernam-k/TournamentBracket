<?php
/**
 * Header Template
 */

// Get the current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Get flash messages
$flash_messages = get_flash_messages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo get_asset_url('css/style.css'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo get_asset_url('images/favicon.ico'); ?>" type="image/x-icon">
    
    <!-- Print CSS -->
    <link rel="stylesheet" href="<?php echo get_asset_url('css/print.css'); ?>" media="print">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-trophy me-2"></i><?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'home' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'tournaments' ? 'active' : ''; ?>" href="index.php?page=tournaments">
                            <i class="fas fa-trophy me-1"></i>Tournaments
                        </a>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'predictions' ? 'active' : ''; ?>" href="index.php?page=predictions">
                                <i class="fas fa-chart-line me-1"></i>Predictions
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'statistics' ? 'active' : ''; ?>" href="index.php?page=statistics">
                            <i class="fas fa-chart-bar me-1"></i>Statistics
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (is_admin()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-shield me-1"></i>Admin
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                                <li>
                                    <a class="dropdown-item" href="index.php?page=admin">
                                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="index.php?page=admin_tournaments">
                                        <i class="fas fa-trophy me-1"></i>Tournaments
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="index.php?page=admin_participants">
                                        <i class="fas fa-users me-1"></i>Participants
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="index.php?page=logout">
                                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php elseif (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i><?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="index.php?page=profile">
                                        <i class="fas fa-user-circle me-1"></i>Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="index.php?page=logout">
                                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'login' ? 'active' : ''; ?>" href="index.php?page=login">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'register' ? 'active' : ''; ?>" href="index.php?page=register">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php if (!empty($flash_messages)): ?>
        <div class="container mt-3">
            <?php foreach ($flash_messages as $type => $messages): ?>
                <?php 
                $alert_class = 'alert-info';
                $icon_class = 'fa-info-circle';
                
                switch ($type) {
                    case 'success':
                        $alert_class = 'alert-success';
                        $icon_class = 'fa-check-circle';
                        break;
                    case 'error':
                        $alert_class = 'alert-danger';
                        $icon_class = 'fa-exclamation-circle';
                        break;
                    case 'warning':
                        $alert_class = 'alert-warning';
                        $icon_class = 'fa-exclamation-triangle';
                        break;
                }
                ?>
                
                <?php foreach ($messages as $message): ?>
                    <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                        <i class="fas <?php echo $icon_class; ?> me-2"></i><?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="container py-4">