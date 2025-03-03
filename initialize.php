<?php
/**
 * Tournament Bracket System - Initialization Script
 * 
 * This script initializes the application by creating the necessary JSON files
 * with default data. Run this script once after installation.
 */

// Include configuration
require_once 'config.php';

// Include required files
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/tournament.php';
require_once INCLUDES_PATH . '/utils.php';

// Check if script is being run from the command line or browser
$is_cli = (php_sapi_name() === 'cli');

// Output function that works in both CLI and browser
function output($message, $is_error = false) {
    global $is_cli;
    
    if ($is_cli) {
        if ($is_error) {
            fwrite(STDERR, $message . PHP_EOL);
        } else {
            echo $message . PHP_EOL;
        }
    } else {
        echo ($is_error ? '<div style="color: red;">' : '<div>') . $message . '</div>';
    }
}

// Initialize database
output('Initializing database...');

// Create data directory if it doesn't exist
if (!is_dir(DATA_PATH)) {
    if (mkdir(DATA_PATH, 0755, true)) {
        output('Created data directory: ' . DATA_PATH);
    } else {
        output('Failed to create data directory: ' . DATA_PATH, true);
        exit(1);
    }
}

// Initialize all JSON files
init_database();

// Check if all files were created successfully
$required_files = [
    TOURNAMENTS_FILE,
    MATCHES_FILE,
    USERS_FILE,
    PARTICIPANTS_FILE,
    PREDICTIONS_FILE,
    STATISTICS_FILE
];

$all_files_exist = true;
foreach ($required_files as $file) {
    if (file_exists($file)) {
        output('Created file: ' . basename($file));
    } else {
        output('Failed to create file: ' . basename($file), true);
        $all_files_exist = false;
    }
}

if (!$all_files_exist) {
    output('Some files could not be created. Check permissions and try again.', true);
    exit(1);
}

// Create sample data
output('Creating sample data...');

// Create sample participants
$participants = [
    [
        'id' => 'p1',
        'name' => 'Team Alpha',
        'description' => 'A strong team with a history of success.',
        'created_at' => date('Y-m-d\TH:i:s\Z')
    ],
    [
        'id' => 'p2',
        'name' => 'Team Beta',
        'description' => 'New team with promising talent.',
        'created_at' => date('Y-m-d\TH:i:s\Z')
    ],
    [
        'id' => 'p3',
        'name' => 'Team Gamma',
        'description' => 'Experienced veterans looking for a comeback.',
        'created_at' => date('Y-m-d\TH:i:s\Z')
    ],
    [
        'id' => 'p4',
        'name' => 'Team Delta',
        'description' => 'Young and aggressive team.',
        'created_at' => date('Y-m-d\TH:i:s\Z')
    ]
];

$participants_data = ['participants' => $participants];
if (write_json_file(PARTICIPANTS_FILE, $participants_data)) {
    output('Created sample participants');
} else {
    output('Failed to create sample participants', true);
}

// Create sample user
$user = [
    'id' => 'u1',
    'username' => 'user',
    'password_hash' => password_hash('password', PASSWORD_DEFAULT),
    'email' => 'user@example.com',
    'created_at' => date('Y-m-d H:i:s'),
    'is_admin' => false
];

$users_data = ['users' => [$user]];
if (write_json_file(USERS_FILE, $users_data)) {
    output('Created sample user (username: user, password: password)');
} else {
    output('Failed to create sample user', true);
}

// Create sample tournament
$tournament_id = create_tournament('Sample Tournament', 'single_elimination', 'admin');
if ($tournament_id) {
    output('Created sample tournament: Sample Tournament');
    
    // Add participants to tournament
    foreach ($participants as $participant) {
        if (add_tournament_participant($tournament_id, $participant['id'])) {
            output('Added participant to tournament: ' . $participant['name']);
        } else {
            output('Failed to add participant to tournament: ' . $participant['name'], true);
        }
    }
} else {
    output('Failed to create sample tournament', true);
}

// Final message
if ($all_files_exist) {
    output('');
    output('Initialization completed successfully!');
    output('');
    output('You can now access the application at: ' . SITE_URL);
    output('Admin login: username = ' . ADMIN_USERNAME . ', password = ' . ADMIN_PASSWORD);
    output('User login: username = user, password = password');
    output('');
    output('Important: Change the admin password in config.php before deploying to production.');
} else {
    output('');
    output('Initialization completed with errors. Please check the messages above.', true);
}