<?php
session_start();
require_once 'db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    $step = (int)$_POST['step'];
    
    // Store form data in session
    if ($step === 1) {
        $_SESSION['reg_data']['first_name'] = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
        $_SESSION['reg_data']['last_name'] = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
        $_SESSION['reg_data']['email'] = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        // Redirect to step 2
        header('Location: register.php?step=2');
        exit();
    } elseif ($step === 2) {
        $_SESSION['reg_data']['phone_number'] = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
        $_SESSION['reg_data']['address'] = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        
        // Redirect to step 3
        header('Location: register.php?step=3');
        exit();
    } elseif ($step === 3) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($password !== $confirm_password) {
            $_SESSION['error'] = 'Passwords do not match';
            header('Location: register.php?step=3');
            exit();
        }
        
        try {
            // Check if email already exists
            $sql = "SELECT id FROM ipark_users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $_SESSION['reg_data']['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['error'] = 'Email already registered';
                header('Location: register.php?step=1');
                exit();
            }
            
            // Insert new user
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO ipark_users (first_name, last_name, email, password, phone_number, address) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssss",
                $_SESSION['reg_data']['first_name'],
                $_SESSION['reg_data']['last_name'],
                $_SESSION['reg_data']['email'],
                $hashed_pw,
                $_SESSION['reg_data']['phone_number'],
                $_SESSION['reg_data']['address']
            );
            $stmt->execute();
            
            // Clear registration data
            unset($_SESSION['reg_data']);
            
            $_SESSION['success'] = 'Registration successful! Please login.';
            header('Location: index.php');
            exit();
            
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Registration failed. Please try again.';
            header('Location: register.php?step=3');
            exit();
        }
    }
}

// Get current step
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$total_steps = 3;

// Validate step number
if ($current_step < 1 || $current_step > $total_steps) {
    $current_step = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - IPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="styles/register.css">
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <img src="assets/images/logo.png" alt="IPark Logo" class="logo">
                <h1 class="register-title">Create Your Account</h1>
                <p class="register-subtitle">Join IPark for smart parking solutions</p>
            </div>

            <!-- Progress Steps -->
            <div class="steps">
                <div class="step <?php echo $current_step >= 1 ? 'active' : ''; ?> <?php echo $current_step > 1 ? 'completed' : ''; ?>">
                    1
                    <span class="step-label">Personal Info</span>
                </div>
                <div class="step <?php echo $current_step >= 2 ? 'active' : ''; ?> <?php echo $current_step > 2 ? 'completed' : ''; ?>">
                    2
                    <span class="step-label">Contact Details</span>
                </div>
                <div class="step <?php echo $current_step >= 3 ? 'active' : ''; ?>">
                    3
                    <span class="step-label">Security</span>
                </div>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 1.25rem;">⚠️</span>
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="register-form">
                <input type="hidden" name="step" value="<?php echo $current_step; ?>">
                
                <?php if($current_step === 1): ?>
                    <!-- Step 1: Personal Information -->
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo $_SESSION['reg_data']['first_name'] ?? ''; ?>"
                               placeholder="Enter your first name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo $_SESSION['reg_data']['last_name'] ?? ''; ?>"
                               placeholder="Enter your last name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo $_SESSION['reg_data']['email'] ?? ''; ?>"
                               placeholder="Enter your email address" required>
                    </div>

                <?php elseif($current_step === 2): ?>
                    <!-- Step 2: Contact Information -->
                    <div class="form-group">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                               value="<?php echo $_SESSION['reg_data']['phone_number'] ?? ''; ?>"
                               placeholder="Enter your phone number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" 
                                  placeholder="Enter your address" rows="3" required><?php echo $_SESSION['reg_data']['address'] ?? ''; ?></textarea>
                    </div>

                <?php elseif($current_step === 3): ?>
                    <!-- Step 3: Security -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Create a password" required 
                               minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                               title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                        <small style="color: var(--gray-500); margin-top: 0.5rem; display: block;">
                            Password must be at least 8 characters long and include uppercase, lowercase, and numbers
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm your password" required>
                    </div>
                <?php endif; ?>

                <div class="btn-group">
                    <?php if($current_step > 1): ?>
                        <a href="?step=<?php echo $current_step - 1; ?>" class="btn btn-outline-secondary">
                            <span>← Back</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if($current_step < $total_steps): ?>
                        <button type="submit" class="btn btn-primary">
                            <span>Continue →</span>
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary">
                            <span>Create Account</span>
                        </button>
                    <?php endif; ?>
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

            // Password validation
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password && confirmPassword) {
                function validatePassword() {
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }

                password.addEventListener('change', validatePassword);
                confirmPassword.addEventListener('keyup', validatePassword);
            }
        });
    </script>
</body>
</html> 
