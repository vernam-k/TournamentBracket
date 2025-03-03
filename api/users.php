<?php
/**
 * Users API Endpoint
 */

// Check if this file is included from the API entry point
if (!defined('ROOT_PATH')) {
    exit('Direct access not allowed');
}

// Get the action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Define allowed actions
$allowed_actions = [
    'login',
    'register',
    'check_auth',
    'logout'
];

// Check if the action is allowed
if (!in_array($action, $allowed_actions)) {
    send_json_response(['error' => 'Invalid action'], 400);
}

// Handle the action
switch ($action) {
    case 'login':
        // Login a user
        
        // Get POST data
        $username = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['username' => $username, 'password' => $password],
            ['username', 'password']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Check if it's an admin login
        if ($username === ADMIN_USERNAME) {
            if (authenticate_admin($username, $password)) {
                send_json_response([
                    'success' => true,
                    'message' => 'Admin login successful',
                    'user' => [
                        'username' => $username,
                        'is_admin' => true
                    ]
                ]);
            } else {
                send_json_response(['error' => 'Invalid credentials'], 401);
            }
        } else {
            // Regular user login
            if (authenticate_user($username, $password)) {
                $user = get_user_by_username($username);
                
                send_json_response([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $username,
                        'is_admin' => false
                    ]
                ]);
            } else {
                send_json_response(['error' => 'Invalid credentials'], 401);
            }
        }
        break;
        
    case 'register':
        // Register a new user
        
        // Get POST data
        $username = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
        
        // Validate required fields
        $missing_fields = validate_required_fields(
            ['username' => $username, 'password' => $password, 'email' => $email],
            ['username', 'password', 'email']
        );
        
        if (!empty($missing_fields)) {
            send_json_response([
                'error' => 'Missing required fields',
                'missing_fields' => $missing_fields
            ], 400);
        }
        
        // Validate username (alphanumeric, 3-20 characters)
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            send_json_response([
                'error' => 'Invalid username. Username must be 3-20 characters and contain only letters, numbers, and underscores.'
            ], 400);
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            send_json_response(['error' => 'Invalid email address'], 400);
        }
        
        // Validate password (at least 8 characters)
        if (strlen($password) < 8) {
            send_json_response(['error' => 'Password must be at least 8 characters long'], 400);
        }
        
        // Check if username is admin
        if (strtolower($username) === strtolower(ADMIN_USERNAME)) {
            send_json_response(['error' => 'Username not available'], 400);
        }
        
        // Check if username already exists
        if (get_user_by_username($username)) {
            send_json_response(['error' => 'Username already exists'], 400);
        }
        
        // Register the user
        $user_id = register_user($username, $password, $email);
        
        if (!$user_id) {
            send_json_response(['error' => 'Failed to register user'], 500);
        }
        
        // Automatically log in the user
        authenticate_user($username, $password);
        
        send_json_response([
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $user_id
        ]);
        break;
        
    case 'check_auth':
        // Check if user is authenticated
        if (is_logged_in()) {
            $is_admin = is_admin();
            
            send_json_response([
                'authenticated' => true,
                'is_admin' => $is_admin,
                'username' => $_SESSION['username']
            ]);
        } else {
            send_json_response(['authenticated' => false]);
        }
        break;
        
    case 'logout':
        // Logout the user
        logout();
        
        send_json_response([
            'success' => true,
            'message' => 'Logout successful'
        ]);
        break;
        
    default:
        send_json_response(['error' => 'Invalid action'], 400);
}