<?php
/**
 * Authentication functions
 */

/**
 * Start a session if not already started
 */
function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if a user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    start_session();
    return isset($_SESSION['user_id']);
}

/**
 * Check if the current user is an admin
 * 
 * @return bool True if admin, false otherwise
 */
function is_admin() {
    start_session();
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Authenticate admin user
 * 
 * @param string $username Username
 * @param string $password Password
 * @return bool True if authentication successful, false otherwise
 */
function authenticate_admin($username, $password) {
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        start_session();
        $_SESSION['user_id'] = 'admin';
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = true;
        return true;
    }
    return false;
}

/**
 * Authenticate regular user
 * 
 * @param string $username Username
 * @param string $password Password
 * @return bool True if authentication successful, false otherwise
 */
function authenticate_user($username, $password) {
    require_once INCLUDES_PATH . '/database.php';
    
    $user = get_user_by_username($username);
    if (!$user) {
        return false;
    }
    
    if (password_verify($password, $user['password_hash'])) {
        start_session();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = false;
        return true;
    }
    
    return false;
}

/**
 * Register a new user
 * 
 * @param string $username Username
 * @param string $password Password
 * @param string $email Email
 * @return bool|string User ID if successful, false otherwise
 */
function register_user($username, $password, $email) {
    require_once INCLUDES_PATH . '/database.php';
    
    // Check if username already exists
    if (get_user_by_username($username)) {
        return false;
    }
    
    $user_id = generate_id('u');
    $user = [
        'id' => $user_id,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'email' => $email,
        'created_at' => date('Y-m-d H:i:s'),
        'is_admin' => false
    ];
    
    if (save_user($user)) {
        return $user_id;
    }
    
    return false;
}

/**
 * Log out the current user
 */
function logout() {
    start_session();
    
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Require admin authentication for a page
 * Redirects to login page if not authenticated
 */
function require_admin() {
    if (!is_admin()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '&required=admin');
        exit;
    }
}

/**
 * Require user authentication for a page
 * Redirects to login page if not authenticated
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Generate a CSRF token
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    start_session();
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token($token) {
    start_session();
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}