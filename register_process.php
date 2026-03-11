<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);

    // Check if email already exists
    $sql = "SELECT id FROM ipark_users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Email already registered';
        header('Location: register.php');
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $sql = "INSERT INTO ipark_users (first_name, last_name, email, password, phone_number, address) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $first_name, $last_name, $email, $hashed_password, $phone_number, $address);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Registration successful! Please login.';
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['error'] = 'Registration failed. Please try again.';
        header('Location: register.php');
        exit();
    }
} else {
    header('Location: register.php');
    exit();
}
?> 