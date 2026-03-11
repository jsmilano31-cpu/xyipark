<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Require user authentication
requireUser();

// Fetch user data
$sql = "SELECT * FROM ipark_users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #f1f5f9;
            --accent: #22d3ee;
            --success: #10b981;
            --warning: #f59e0b;
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
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --border-radius: 12px;
            --border-radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--dark) 0%, var(--gray-800) 100%);
            color: var(--light);
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .sidebar-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-menu {
            list-style: none;
            padding: 0 1rem;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            transition: width 0.3s ease;
            z-index: -1;
        }

        .nav-link:hover {
            color: var(--light);
            transform: translateX(4px);
        }

        .nav-link:hover::before,
        .nav-link.active::before {
            width: 100%;
        }

        .nav-link.active {
            color: var(--light);
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-icon {
            font-size: 1.25rem;
            opacity: 0.8;
        }

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

        .logout-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-link:hover {
            color: var(--danger);
            border-color: var(--danger);
            background: rgba(239, 68, 68, 0.1);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            max-width: calc(100vw - 280px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-info h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .header-info p {
            color: var(--gray-600);
            font-size: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                max-width: 100vw;
            }

            .mobile-menu-toggle {
                display: block;
            }
        }

        @media (max-width: 640px) {
            .main-content {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary);
            color: var(--light);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        .mobile-menu-toggle:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleSidebar()">
            <span style="font-size: 1.5rem;">☰</span>
        </button>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="assets/images/logo.png" alt="IPark Logo" style="max-width: 80px; display: block; margin: 0 auto 1rem auto;">
                <h1>
                    IPark
                </h1>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link active">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reserve.php" class="nav-link">
                            <span class="nav-icon">🚗</span>
                            Reserve a Slot
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="my_reservations.php" class="nav-link">
                            <span class="nav-icon">📅</span>
                            My Reservations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="message.php" class="nav-link">
                            <span class="nav-icon">💬</span>
                            Message Admin
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">
                            <span class="nav-icon">👤</span>
                            Profile Settings
                        </a>
                    </li>
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
                <a href="logout.php" class="logout-link">
                    <span class="nav-icon">🚪</span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <div class="header-info">
                    <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                    <p>Manage your parking reservations and account settings</p>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="stats-grid">
                <?php
                // Fetch user's reservation statistics
                $stmt = $conn->prepare("
                    SELECT 
                        COUNT(*) as total_reservations,
                        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_reservations,
                        SUM(CASE WHEN status IN ('Pending', 'Confirmed') AND end_time > NOW() THEN 1 ELSE 0 END) as active_reservations
                    FROM reservations 
                    WHERE user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $stats = $stmt->fetch();

                // Fetch user's most recent reservation
                $stmt = $conn->prepare("
                    SELECT r.*, p.slot_number, p.floor_number 
                    FROM reservations r 
                    JOIN parking_slots p ON r.parking_slot_id = p.id 
                    WHERE r.user_id = ? AND r.status IN ('Pending', 'Confirmed')
                    AND r.end_time > NOW()
                    ORDER BY r.start_time ASC
                    LIMIT 1
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $next_reservation = $stmt->fetch();
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value"><?php echo $stats['total_reservations']; ?></div>
                    <div class="stat-label">Total Reservations</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $stats['completed_reservations']; ?></div>
                    <div class="stat-label">Completed Reservations</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🚗</div>
                    <div class="stat-value"><?php echo $stats['active_reservations']; ?></div>
                    <div class="stat-label">Active Reservations</div>
                </div>

                <?php if($next_reservation): ?>
                <div class="stat-card highlight">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-info">
                        <div class="stat-label">Next Reservation</div>
                        <div class="stat-value">Slot <?php echo htmlspecialchars($next_reservation['slot_number']); ?></div>
                        <div class="stat-detail">
                            <?php echo date('M d, Y h:i A', strtotime($next_reservation['start_time'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions and Recent Activity -->
            <div class="dashboard-grid">
                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <h2>
                        <span style="font-size: 1.5rem;">⚡</span>
                        Quick Actions
                    </h2>
                    <div class="quick-actions">
                        <a href="reserve.php" class="action-button">
                            <span class="action-icon">🚗</span>
                            <span class="action-text">Reserve a Slot</span>
                        </a>
                        <a href="my_reservations.php" class="action-button">
                            <span class="action-icon">📅</span>
                            <span class="action-text">View My Reservations</span>
                        </a>
                        <a href="profile.php" class="action-button">
                            <span class="action-icon">👤</span>
                            <span class="action-text">Update Profile</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-card">
                    <h2>
                        <span style="font-size: 1.5rem;">📋</span>
                        Recent Activity
                    </h2>
                    <?php
                    // Fetch recent reservations
                    $stmt = $conn->prepare("
                        SELECT r.*, p.slot_number, p.floor_number 
                        FROM reservations r 
                        JOIN parking_slots p ON r.parking_slot_id = p.id 
                        WHERE r.user_id = ? 
                        ORDER BY r.created_at DESC 
                        LIMIT 5
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $recent_reservations = $stmt->fetchAll();
                    ?>

                    <?php if(empty($recent_reservations)): ?>
                        <p class="no-data">No recent activity</p>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach($recent_reservations as $reservation): ?>
                                <div class="activity-item">
                                    <div class="activity-icon status-<?php echo strtolower($reservation['status']); ?>">
                                        <?php 
                                        echo $reservation['status'] === 'Completed' ? '✅' : 
                                            ($reservation['status'] === 'Pending' ? '⏳' : 
                                            ($reservation['status'] === 'Confirmed' ? '✓' : '❌')); 
                                        ?>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-title">
                                            Slot <?php echo htmlspecialchars($reservation['slot_number']); ?> 
                                            (Floor <?php echo htmlspecialchars($reservation['floor_number']); ?>)
                                        </div>
                                        <div class="activity-info">
                                            <?php echo date('M d, Y h:i A', strtotime($reservation['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($reservation['end_time'])); ?>
                                        </div>
                                    </div>
                                    <div class="activity-status status-<?php echo strtolower($reservation['status']); ?>">
                                        <?php echo htmlspecialchars($reservation['status']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <style>
                /* Dashboard Grid Layout */
                .dashboard-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 2rem;
                    margin-top: 2rem;
                }

                /* Stats Grid */
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 1.5rem;
                    margin-bottom: 2rem;
                }

                .stat-card {
                    background: var(--light);
                    border-radius: var(--border-radius-lg);
                    padding: 1.5rem;
                    box-shadow: var(--shadow);
                    border: 1px solid var(--gray-200);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    transition: transform 0.3s ease;
                }

                .stat-card:hover {
                    transform: translateY(-5px);
                }

                .stat-card.highlight {
                    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
                    color: var(--light);
                }

                .stat-icon {
                    font-size: 2rem;
                    margin-bottom: 1rem;
                }

                .stat-value {
                    font-size: 2rem;
                    font-weight: 700;
                    margin-bottom: 0.5rem;
                    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                }

                .stat-card.highlight .stat-value {
                    background: none;
                    -webkit-text-fill-color: var(--light);
                }

                .stat-label {
                    font-size: 0.875rem;
                    color: var(--gray-600);
                    font-weight: 500;
                }

                .stat-card.highlight .stat-label {
                    color: rgba(255, 255, 255, 0.8);
                }

                .stat-info {
                    text-align: left;
                    width: 100%;
                }

                .stat-detail {
                    font-size: 0.875rem;
                    color: rgba(255, 255, 255, 0.8);
                    margin-top: 0.25rem;
                }

                /* Dashboard Cards */
                .dashboard-card {
                    background: var(--light);
                    border-radius: var(--border-radius-lg);
                    padding: 1.5rem;
                    box-shadow: var(--shadow);
                    border: 1px solid var(--gray-200);
                }

                .dashboard-card h2 {
                    font-size: 1.25rem;
                    font-weight: 600;
                    color: var(--gray-900);
                    margin-bottom: 1.5rem;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                }

                /* Quick Actions */
                .quick-actions {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 1rem;
                }

                .action-button {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 1.5rem;
                    background: var(--gray-50);
                    border-radius: var(--border-radius);
                    text-decoration: none;
                    color: var(--gray-700);
                    transition: all 0.3s ease;
                    border: 1px solid var(--gray-200);
                }

                .action-button:hover {
                    transform: translateY(-2px);
                    background: var(--light);
                    box-shadow: var(--shadow);
                    color: var(--primary);
                }

                .action-icon {
                    font-size: 1.5rem;
                }

                .action-text {
                    font-weight: 500;
                    text-align: center;
                }

                /* Activity List */
                .activity-list {
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                }

                .activity-item {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    padding: 1rem;
                    background: var(--gray-50);
                    border-radius: var(--border-radius);
                    border: 1px solid var(--gray-200);
                }

                .activity-icon {
                    font-size: 1.25rem;
                    width: 2.5rem;
                    height: 2.5rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    background: var(--light);
                }

                .activity-details {
                    flex: 1;
                    min-width: 0;
                }

                .activity-title {
                    font-weight: 500;
                    color: var(--gray-900);
                    margin-bottom: 0.25rem;
                }

                .activity-info {
                    font-size: 0.875rem;
                    color: var(--gray-600);
                }

                .activity-status {
                    font-size: 0.75rem;
                    font-weight: 600;
                    padding: 0.25rem 0.75rem;
                    border-radius: 50px;
                }

                .no-data {
                    color: var(--gray-500);
                    text-align: center;
                    padding: 2rem;
                }

                /* Status Colors */
                .status-completed {
                    background: rgba(16, 185, 129, 0.1);
                    color: var(--success);
                }

                .status-pending {
                    background: rgba(245, 158, 11, 0.1);
                    color: var(--warning);
                }

                .status-confirmed {
                    background: rgba(99, 102, 241, 0.1);
                    color: var(--primary);
                }

                .status-cancelled {
                    background: rgba(239, 68, 68, 0.1);
                    color: var(--danger);
                }

                /* Responsive Adjustments */
                @media (max-width: 640px) {
                    .stats-grid {
                        grid-template-columns: 1fr;
                    }

                    .quick-actions {
                        grid-template-columns: 1fr;
                    }

                    .activity-item {
                        flex-direction: column;
                        text-align: center;
                    }

                    .activity-status {
                        align-self: center;
                    }
                }
            </style>
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 1024 && 
                !sidebar.contains(e.target) && 
                !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('show');
            }
        });
    </script>
</body>
</html> 