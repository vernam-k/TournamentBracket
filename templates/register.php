<?php
/**
 * Register Page Template
 */

// Get redirect URL if set
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    
    // Validate required fields
    $missing_fields = validate_required_fields(
        [
            'username' => $username,
            'password' => $password,
            'confirm_password' => $confirm_password,
            'email' => $email
        ],
        ['username', 'password', 'confirm_password', 'email']
    );
    
    $errors = [];
    
    if (!empty($missing_fields)) {
        $errors[] = 'Please fill in all required fields.';
    } else {
        // Validate username (alphanumeric, 3-20 characters)
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $errors[] = 'Username must be 3-20 characters and contain only letters, numbers, and underscores.';
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        // Validate password (at least 8 characters)
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        // Check if passwords match
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        // Check if username is admin
        if (strtolower($username) === strtolower(ADMIN_USERNAME)) {
            $errors[] = 'This username is not available.';
        }
        
        // Check if username already exists
        if (get_user_by_username($username)) {
            $errors[] = 'Username already exists. Please choose a different one.';
        }
    }
    
    if (empty($errors)) {
        // Register the user
        $user_id = register_user($username, $password, $email);
        
        if ($user_id) {
            // Automatically log in the user
            authenticate_user($username, $password);
            
            set_flash_message('success', 'Registration successful! Welcome to ' . SITE_NAME . '.');
            redirect($redirect);
        } else {
            set_flash_message('error', 'Failed to register user. Please try again.');
        }
    } else {
        foreach ($errors as $error) {
            set_flash_message('error', $error);
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0"><i class="fas fa-user-plus me-2"></i>Register</h4>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=register&redirect=<?php echo urlencode($redirect); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required
                                   pattern="[a-zA-Z0-9_]{3,20}" 
                                   title="Username must be 3-20 characters and contain only letters, numbers, and underscores."
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                        <small class="form-text text-muted">3-20 characters, letters, numbers, and underscores only.</small>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required
                                   minlength="8" title="Password must be at least 8 characters long.">
                        </div>
                        <small class="form-text text-muted">At least 8 characters.</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-white">
                <div class="text-center">
                    <p class="mb-0">Already have an account? <a href="index.php?page=login">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>