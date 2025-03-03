<?php
/**
 * Configuration file for Tournament Bracket System
 */

// Admin credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'password123'); // In production, use a strong password

// Site settings
define('SITE_NAME', 'Tournament Bracket System');
define('SITE_URL', 'http://localhost/TournamentBracket');

// File paths
define('ROOT_PATH', __DIR__);
define('DATA_PATH', ROOT_PATH . '/data');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// JSON file paths
define('TOURNAMENTS_FILE', DATA_PATH . '/tournaments.json');
define('MATCHES_FILE', DATA_PATH . '/matches.json');
define('USERS_FILE', DATA_PATH . '/users.json');
define('PARTICIPANTS_FILE', DATA_PATH . '/participants.json');
define('PREDICTIONS_FILE', DATA_PATH . '/predictions.json');
define('STATISTICS_FILE', DATA_PATH . '/statistics.json');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS

// Error reporting - set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('America/Chicago');

// AJAX polling interval (in milliseconds)
define('POLLING_INTERVAL', 5000);