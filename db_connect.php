<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'u847001018_spencer');
define('DB_PASS', 'SpencerMil@no123');
define('DB_NAME', 'u847001018_ipark');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection error");
}

// Check if default admin exists in ipark_admins
$check_admin = "SELECT id FROM ipark_admins WHERE username = 'admin'";
$result = $conn->query($check_admin);

if ($result && $result->num_rows == 0) {
    // Insert default admin user using prepared statement
    $default_password = password_hash('Admin@123', PASSWORD_DEFAULT);
    $username = 'admin';
    $email = 'admin@ipark.com';
    $insert_admin = "INSERT INTO ipark_admins (username, password, email) VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($insert_admin);
    if ($stmt) {
        $stmt->bind_param("sss", $username, $default_password, $email);
        if (!$stmt->execute()) {
            error_log("Error creating default admin: " . $stmt->error);
        }
    }
}
?>
