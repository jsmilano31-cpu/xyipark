<?php
session_start();
require_once 'db_connect.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header('Location: index.php');
        exit();
    }

    // Check if user exists
    $sql = "SELECT id, first_name, last_name, email, password FROM ipark_users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        
        // Redirect to dashboard
        header('Location: dashboard.php');
        exit();
    } else {
        // Login failed
        $_SESSION['error'] = 'Invalid email or password';
        header('Location: index.php');
        exit();
    }
} else {
    // If not a POST request, redirect to login page
    header('Location: index.php');
    exit();
}
?> 