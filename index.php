<?php
/**
 * Tournament Bracket System - Main Entry Point
 */

// Start output buffering to allow header modifications after HTML output
ob_start();

// Include configuration
require_once 'config.php';

// Include required files
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/tournament.php';
require_once INCLUDES_PATH . '/utils.php';

// Initialize database if needed
init_database();

// Start session
start_session();

// Get the requested page
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Define allowed pages and their templates
$allowed_pages = [
    'home' => 'home.php',
    'tournaments' => 'tournaments.php',
    'tournament' => 'tournament.php',
    'login' => 'login.php',
    'register' => 'register.php',
    'admin' => 'admin/dashboard.php',
    'admin_tournaments' => 'admin/tournaments.php',
    'admin_tournament' => 'admin/tournament.php',
    'admin_participants' => 'admin/participants.php',
    'logout' => 'logout.php',
    'profile' => 'profile.php',
    'predictions' => 'predictions.php',
    'statistics' => 'statistics.php'
];

// Check if the requested page is allowed
if (!isset($allowed_pages[$page])) {
    $page = 'home';
}

// Check if the page requires authentication
$admin_pages = ['admin', 'admin_tournaments', 'admin_tournament', 'admin_participants'];
$user_pages = ['profile', 'predictions'];

if (in_array($page, $admin_pages) && !is_admin()) {
    set_flash_message('error', 'You must be an administrator to access that page.');
    redirect('index.php?page=login&redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

if (in_array($page, $user_pages) && !is_logged_in()) {
    set_flash_message('info', 'Please log in to access that page.');
    redirect('index.php?page=login&redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

// Handle logout
if ($page === 'logout') {
    logout();
    set_flash_message('success', 'You have been logged out successfully.');
    redirect('index.php');
}

// Include the header
include TEMPLATES_PATH . '/header.php';

// Include the requested page
include TEMPLATES_PATH . '/' . $allowed_pages[$page];

// Include the footer
include TEMPLATES_PATH . '/footer.php';

// Flush the output buffer
ob_end_flush();