<?php
/**
 * User Profile Page Template
 */

// Require user authentication
if (!is_logged_in()) {
    set_flash_message('error', 'You must be logged in to access this page.');
    redirect('index.php?page=login&redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

// Get user data
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$is_admin = is_admin();

// Get user from database
$users = read_json_file(USERS_FILE);
$user = null;

if ($users && isset($users['users'])) {
    foreach ($users['users'] as $u) {
        if ($u['id'] === $user_id) {
            $user = $u;
            break;
        }
    }
}

if (!$user) {
    set_flash_message('error', 'User not found.');
    redirect('index.php');
}

// Get user predictions
$predictions_data = read_json_file(PREDICTIONS_FILE);
$user_predictions = [];

if ($predictions_data && isset($predictions_data['predictions'])) {
    $user_predictions = array_filter($predictions_data['predictions'], function($prediction) use ($user_id) {
        return $prediction['user_id'] === $user_id;
    });
}

// Count correct predictions
$correct_predictions = 0;
$total_points = 0;

foreach ($user_predictions as $prediction) {
    if (isset($prediction['is_correct']) && $prediction['is_correct'] === true) {
        $correct_predictions++;
        $total_points += $prediction['points_earned'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'update_profile') {
        $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        $errors = [];
        
        // Validate email
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }
        
        // Validate password change if requested
        if (!empty($new_password)) {
            // Verify current password
            if (empty($current_password)) {
                $errors[] = 'Current password is required to set a new password.';
            } elseif (!password_verify($current_password, $user['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            }
            
            // Validate new password
            if (strlen($new_password) < 8) {
                $errors[] = 'New password must be at least 8 characters long.';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'New password and confirmation do not match.';
            }
        }
        
        if (empty($errors)) {
            // Update user data
            $user['email'] = $email;
            
            // Update password if provided
            if (!empty($new_password)) {
                $user['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            // Save user data
            foreach ($users['users'] as $key => $u) {
                if ($u['id'] === $user_id) {
                    $users['users'][$key] = $user;
                    break;
                }
            }
            
            if (write_json_file(USERS_FILE, $users)) {
                set_flash_message('success', 'Profile updated successfully.');
                redirect('index.php?page=profile');
            } else {
                set_flash_message('error', 'Failed to update profile.');
            }
        } else {
            // Display errors
            foreach ($errors as $error) {
                set_flash_message('error', $error);
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-user-circle me-2"></i>My Profile</h1>
</div>

<div class="row">
    <!-- Profile Information -->
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-user me-2"></i>Profile Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=profile">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
                        <div class="form-text">Username cannot be changed.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="account_type" class="form-label">Account Type</label>
                        <input type="text" class="form-control" id="account_type" value="<?php echo $is_admin ? 'Administrator' : 'Regular User'; ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="created_at" class="form-label">Account Created</label>
                        <input type="text" class="form-control" id="created_at" value="<?php echo format_date($user['created_at']); ?>" readonly>
                    </div>
                    
                    <hr>
                    
                    <h5>Change Password</h5>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                        <div class="form-text">Leave password fields blank to keep your current password.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Profile Statistics -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i>Your Statistics</h5>
            </div>
            <div class="card-body">
                <div class="mb-3 text-center">
                    <div class="display-1">
                        <i class="fas fa-user-circle text-primary"></i>
                    </div>
                    <h4 class="mt-2"><?php echo htmlspecialchars($username); ?></h4>
                    <?php if ($is_admin): ?>
                        <span class="badge bg-danger">Administrator</span>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <h2 class="text-primary"><?php echo count($user_predictions); ?></h2>
                        <p class="text-muted">Predictions</p>
                    </div>
                    <div class="col-6 mb-3">
                        <h2 class="text-success"><?php echo $correct_predictions; ?></h2>
                        <p class="text-muted">Correct</p>
                    </div>
                    <div class="col-6 mb-3">
                        <h2 class="text-info"><?php echo $total_points; ?></h2>
                        <p class="text-muted">Points</p>
                    </div>
                    <div class="col-6 mb-3">
                        <h2 class="text-warning"><?php echo count($user_predictions) > 0 ? round(($correct_predictions / count($user_predictions)) * 100) : 0; ?>%</h2>
                        <p class="text-muted">Accuracy</p>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <a href="index.php?page=predictions" class="btn btn-outline-primary">
                        <i class="fas fa-chart-line me-2"></i>View My Predictions
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($is_admin): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-shield-alt me-2"></i>Admin Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php?page=admin" class="btn btn-outline-danger">
                            <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                        </a>
                        <a href="index.php?page=admin_tournaments" class="btn btn-outline-danger">
                            <i class="fas fa-trophy me-2"></i>Manage Tournaments
                        </a>
                        <a href="index.php?page=admin_participants" class="btn btn-outline-danger">
                            <i class="fas fa-users me-2"></i>Manage Participants
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>