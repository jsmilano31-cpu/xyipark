<?php
require_once 'auth.php';
require_once 'db_connect.php';
requireUser();

// Fetch user data
$sql = "SELECT * FROM ipark_users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle flash messages
function flash($key) {
    if(isset($_SESSION[$key])) {
        echo '<div class="alert '.($key==='success'?'alert-success':'alert-danger').'">'.$_SESSION[$key].'</div>';
        unset($_SESSION[$key]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - IPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">    
    <style>

.user-section {
            padding: 1rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 2rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gray-700);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--light);
            font-size: 1.25rem;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            color: var(--light);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #22d3ee;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --border-radius: 12px;
            --border-radius-lg: 16px;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            color: var(--gray-800);
            min-height: 100vh;
        }
        .app-container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; max-width: calc(100vw - 280px); }
        .header { margin-bottom: 2rem; }
        .header h1 { font-size: 2rem; font-weight: 700; color: var(--gray-900); }
        .profile-card {
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            max-width: 700px;
            margin: 0 auto;
            border: 1px solid var(--gray-200);
        }
        .profile-avatar {
            width: 96px; height: 96px; border-radius: 50%; background: var(--gray-200);
            display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; color: var(--primary);
            margin: 0 auto 1rem auto; overflow: hidden;
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .profile-form, .password-form { display: flex; flex-direction: column; gap: 1.25rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .form-label { font-weight: 500; color: var(--gray-700); }
        .form-input, .form-select {
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
            font-size: 1rem;
            background: var(--gray-50);
            color: var(--gray-800);
        }
        .form-input:focus, .form-select:focus { outline: 2px solid var(--primary); border-color: var(--primary); }
        .form-actions { display: flex; gap: 1rem; margin-top: 1rem; }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            border: none;
            cursor: pointer;
            background: var(--primary);
            color: var(--light);
            transition: background 0.2s;
        }
        .btn:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--gray-200); color: var(--gray-800); }
        .btn-secondary:hover { background: var(--gray-300); }
        .alert { padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: rgba(16,185,129,0.1); color: var(--success); border: 1px solid var(--success); }
        .alert-danger { background: rgba(239,68,68,0.1); color: var(--danger); border: 1px solid var(--danger); }
        .section-title { font-size: 1.1rem; font-weight: 600; color: var(--gray-700); margin: 2rem 0 1rem 0; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; max-width: 100vw; } }
        @media (max-width: 640px) { .main-content { padding: 1rem; } .profile-card { padding: 1rem; } }
        .profile-sections {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            justify-content: center;
            flex-wrap: wrap;
        }
        .profile-info-card, .password-card {
            flex: 1 1 320px;
            min-width: 320px;
            max-width: 400px;
            margin-bottom: 2rem;
        }
        .profile-info-card {
            margin-right: 0;
        }
        .password-card {
            margin-left: 0;
        }
        @media (max-width: 900px) {
            .profile-sections {
                flex-direction: column;
                gap: 2rem;
                align-items: stretch;
            }
            .profile-info-card, .password-card {
                max-width: 100%;
                margin-right: 0;
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Sidebar (reuse from dashboard/my_reservations.php) -->
    <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
                <img src="assets/images/logo.png" alt="IPark Logo" style="max-width: 80px; display: block; margin: 0 auto 1rem auto;">
                <h1>
                    IPark 
                </h1>
            </div>
        <nav>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><span class="nav-icon">📊</span>Dashboard</a></li>
                <li class="nav-item"><a href="reserve.php" class="nav-link"><span class="nav-icon">🚗</span>Reserve a Slot</a></li>
                <li class="nav-item"><a href="my_reservations.php" class="nav-link"><span class="nav-icon">📅</span>My Reservations</a></li>
                <li class="nav-item"><a href="message.php" class="nav-link"><span class="nav-icon">💬</span>Message Admin</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link active"><span class="nav-icon">👤</span>Profile Settings</a></li>
            </ul>
        </nav>
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar">
                    <?php if($user['profile_picture']): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
            </div>
            <a href="logout.php" class="logout-link"><span class="nav-icon">🚪</span>Logout</a>
        </div>
    </aside>
    <main class="main-content">
        <div class="header">
            <h1>Profile Settings</h1>
        </div>
        <?php flash('success'); flash('error'); ?>
        <div class="profile-sections">
            <div class="profile-card profile-info-card">
                <h2 class="section-title">Personal Information</h2>
                <div class="profile-avatar">
                    <?php if($user['profile_picture']): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <form class="profile-form" action="update_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-input" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-input" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-input" value="<?php echo htmlspecialchars($user['phone_number']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-input" value="<?php echo htmlspecialchars($user['address']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" name="profile_picture" class="form-input" accept="image/*">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn">Save Changes</button>
                    </div>
                </form>
            </div>
            <div class="profile-card password-card">
                <h2 class="section-title">Change Password</h2>
                <form class="password-form" action="change_password.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" required minlength="6">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<script>
// Auto-hide alerts after 5s
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => { a.style.opacity = '0'; setTimeout(()=>a.remove(), 300); });
}, 5000);
</script>
</body>
</html> 