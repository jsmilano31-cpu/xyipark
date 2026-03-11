<?php
require_once 'auth.php';
require_once 'db_connect.php';
requireUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validate
if ($first_name === '' || $last_name === '') {
    $_SESSION['error'] = 'First and last name are required.';
    header('Location: profile.php');
    exit;
}

// Handle profile picture upload
$profile_picture_path = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_picture'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($ext, $allowed)) {
        $_SESSION['error'] = 'Invalid image format. Allowed: jpg, jpeg, png, gif.';
        header('Location: profile.php');
        exit;
    }
    if ($file['size'] > 2 * 1024 * 1024) { // 2MB
        $_SESSION['error'] = 'Image size must be less than 2MB.';
        header('Location: profile.php');
        exit;
    }
    $upload_dir = 'uploads/profile_pics/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
    $target = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $profile_picture_path = $target;
    } else {
        $_SESSION['error'] = 'Failed to upload image.';
        header('Location: profile.php');
        exit;
    }
}

try {
    $sql = "UPDATE ipark_users SET first_name=?, last_name=?, phone_number=?, address=?";
    $params = [$first_name, $last_name, $phone_number, $address];
    $types = "ssss";
    
    if ($profile_picture_path) {
        $sql .= ", profile_picture=?";
        $params[] = $profile_picture_path;
        $types .= "s";
    }
    $sql .= " WHERE id=?";
    $params[] = $user_id;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $_SESSION['success'] = 'Profile updated successfully!';
} catch(Exception $e) {
    $_SESSION['error'] = 'Error updating profile.';
}
header('Location: profile.php');
exit; 