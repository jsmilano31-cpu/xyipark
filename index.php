<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPark - Smart Parking System</title>
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="styles/index.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/images/logo.png" alt="IPark Logo" class="logo">
                <h1 class="login-title">IPark Parking System</h1>
                <p class="login-subtitle">Welcome back! Please sign in to your account.</p>
            </div>
           
            <form action="login_process.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <span>Sign In</span>
                    </button>
                    <a href="register.php" class="btn btn-outline-primary">
                        <span>Create New Account</span>
                    </a>
                    <a href="admin_login.php" class="btn btn-outline-secondary">
                        <span>Admin Portal</span>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
