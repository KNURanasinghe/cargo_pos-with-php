
<?php
// index.php - Main Entry Point
require_once 'config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect based on user role
    if (isAdmin()) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
} else {
    // Redirect to login page
    header("Location: login.php");
}

exit();
?>