<?php
session_start();
require_once 'db_connect.php';

// Check if default admin exists, if not create it
if ($conn) {
    $check_admin = "SELECT id FROM ipark_admins WHERE username = 'admin'";
    $result = $conn->query($check_admin);
    
    if ($result && $result->num_rows == 0) {
        // Insert default admin user
        $default_password = password_hash('Admin@123', PASSWORD_DEFAULT);
        $username = 'admin';
        $email = 'admin@ipark.com';
        $insert_admin = "INSERT INTO ipark_admins (username, password, email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_admin);
        
        if ($stmt) {
            $stmt->bind_param("sss", $username, $default_password, $email);
            $stmt->execute();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPark Admin - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="styles/admin_login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/images/logo.png" alt="IPark Logo" class="logo">
                <h1 class="login-title">IPark Admin Portal</h1>
                <p class="login-subtitle">Sign in to access the admin dashboard</p>
            </div>
            
            <?php if(isset($_SESSION['admin_error'])): ?>
                <div class="alert">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 1.25rem;">⚠️</span>
                        <?php 
                        echo $_SESSION['admin_error'];
                        unset($_SESSION['admin_error']);
                        ?>
                    </div>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endif; ?>

            <form action="admin_auth.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Enter your username" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter your password" required>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <span>Sign In as Admin</span>
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <span>Back to User Login</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 5000);
        });
    </script>
</body>
</html> 
