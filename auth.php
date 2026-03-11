<?php
session_start();

/**
 * Require user authentication
 * Redirects to login page if user is not logged in
 */
function requireUser() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Please login to access this page';
        header('Location: index.php');
        exit();
    }
}

/**
 * Require admin authentication
 * Redirects to admin login page if admin is not logged in
 */
function requireAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        $_SESSION['admin_error'] = 'Please login as admin to access this page';
        header('Location: admin_login.php');
        exit();
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current admin ID
 * @return int|null
 */
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}
?> 