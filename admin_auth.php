<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    $sql = "SELECT id, username, password FROM ipark_admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['is_admin'] = true;
        header('Location: admin/dashboard.php');
        exit();
    } else {
        $_SESSION['admin_error'] = 'Invalid username or password';
        header('Location: admin_login.php');
        exit();
    }
} else {
    header('Location: admin_login.php');
    exit();
}
?> 