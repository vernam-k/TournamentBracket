<?php
/**
 * Logout Page Template
 * 
 * Note: This file is not actually used since logout is handled directly in index.php.
 * It exists only to prevent include errors.
 */

// This page should never be displayed, as logout is handled in index.php
// and redirects to the homepage immediately.
?>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>You are being logged out and redirected to the homepage...
</div>

<script>
    // Redirect to homepage after a brief delay
    setTimeout(function() {
        window.location.href = 'index.php';
    }, 2000);
</script>