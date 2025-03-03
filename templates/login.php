<?php
/**
 * Login Page Template
 */

// Get redirect URL if set
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
$required = isset($_GET['required']) ? $_GET['required'] : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate required fields
    $missing_fields = validate_required_fields(
        ['username' => $username, 'password' => $password],
        ['username', 'password']
    );
    
    if (empty($missing_fields)) {
        // Check if it's an admin login
        if ($username === ADMIN_USERNAME) {
            if (authenticate_admin($username, $password)) {
                // Set remember me cookie if requested
                if ($remember) {
                    setcookie('remember_user', $username, time() + (86400 * 30), '/'); // 30 days
                }
                
                set_flash_message('success', 'Welcome back, Admin!');
                redirect($redirect);
            } else {
                set_flash_message('error', 'Invalid credentials. Please try again.');
            }
        } else {
            // Regular user login
            if (authenticate_user($username, $password)) {
                // Set remember me cookie if requested
                if ($remember) {
                    setcookie('remember_user', $username, time() + (86400 * 30), '/'); // 30 days
                }
                
                set_flash_message('success', 'Login successful. Welcome back!');
                redirect($redirect);
            } else {
                set_flash_message('error', 'Invalid credentials. Please try again.');
            }
        }
    } else {
        set_flash_message('error', 'Please fill in all required fields.');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0"><i class="fas fa-sign-in-alt me-2"></i>Login</h4>
            </div>
            <div class="card-body">
                <?php if ($required === 'admin'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>You must be logged in as an administrator to access that page.
                    </div>
                <?php endif; ?>
                
                <form method="post" action="index.php?page=login&redirect=<?php echo urlencode($redirect); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-white">
                <div class="text-center">
                    <p class="mb-0">Don't have an account? <a href="index.php?page=register">Register</a></p>
                </div>
            </div>
        </div>
        
        <div class="card mt-4 shadow">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Demo Credentials</h5>
            </div>
            <div class="card-body">
                <p><strong>Admin:</strong></p>
                <ul>
                    <li>Username: <code>admin</code></li>
                    <li>Password: <code>password123</code></li>
                </ul>
                <p><strong>Regular User:</strong></p>
                <p>Register a new account to access user features.</p>
            </div>
        </div>
    </div>
</div>