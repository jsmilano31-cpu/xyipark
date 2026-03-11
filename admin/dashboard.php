<?php
require_once '../auth.php';
require_once '../db_connect.php';

// Require admin authentication
requireAdmin();

// Fetch summary data
$totalSlots = 0;
$availableSlots = 0;
$totalUsers = 0;
$activeReservations = 0;

// Total parking slots
$result = $conn->query("SELECT COUNT(*) as total_slots FROM ipark_parking_slots");
if ($result) {
    $row = $result->fetch_assoc();
    $totalSlots = $row['total_slots'];
}

// Available slots
$result = $conn->query("SELECT COUNT(*) as available_slots FROM ipark_parking_slots WHERE status = 'Vacant'");
if ($result) {
    $row = $result->fetch_assoc();
    $availableSlots = $row['available_slots'];
}

// Total users
$result = $conn->query("SELECT COUNT(*) as total_users FROM ipark_users");
if ($result) {
    $row = $result->fetch_assoc();
    $totalUsers = $row['total_users'];
}

// Active reservations
$result = $conn->query("SELECT COUNT(*) as active_reservations FROM ipark_reservations WHERE status = 'Confirmed'");
if ($result) {
    $row = $result->fetch_assoc();
    $activeReservations = $row['active_reservations'];
}

// Fetch unread message count
$unreadMessages = 0;
$recentConversations = [];

$sql = "
    SELECT COUNT(*) as unread_count 
    FROM ipark_messages 
    WHERE is_from_user = TRUE AND is_read = FALSE
";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $unreadMessages = $row['unread_count'];
}

// Fetch recent conversations
$sql = "
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.profile_picture,
           (SELECT COUNT(*) FROM ipark_messages m2 
            WHERE m2.user_id = u.id AND m2.is_from_user = TRUE AND m2.is_read = FALSE) as unread_count,
           (SELECT message FROM ipark_messages m3 
            WHERE m3.user_id = u.id 
            ORDER BY m3.created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM ipark_messages m4 
            WHERE m4.user_id = u.id 
            ORDER BY m4.created_at DESC LIMIT 1) as last_message_time
    FROM ipark_messages m
    JOIN ipark_users u ON m.user_id = u.id
    ORDER BY last_message_time DESC
    LIMIT 5
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentConversations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPark Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/lucide@0.263.1/dist/umd/lucide.js" rel="stylesheet">
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

        .sidebar-header img {
            max-width: 80px;
            display: block;
            margin: 0 auto 1rem auto;
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

        .logout-section {
            position: absolute;
            bottom: 2rem;
            left: 1rem;
            right: 1rem;
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
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .header p {
            color: var(--gray-600);
            font-size: 1rem;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--light);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stat-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .stat-info p {
            color: var(--gray-600);
            font-weight: 500;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(34, 211, 238, 0.1) 100%);
        }

        /* Table Styles */
        .table-container {
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .table-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: var(--gray-50);
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-800);
        }

        .table tbody tr:hover {
            background: var(--gray-50);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Badge Styles */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .badge-secondary {
            background: var(--gray-200);
            color: var(--gray-600);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .main-content {
                margin-left: 0;
                max-width: 100vw;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 640px) {
            .main-content {
                padding: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .table-wrapper {
                font-size: 0.875rem;
            }

            .table th,
            .table td {
                padding: 0.75rem 1rem;
            }
        }

        /* Loading animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        .messages-widget {
            grid-column: span 2;
        }

        .conversations-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .conversation-item {
            display: flex;
            gap: 1rem;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            background: var(--gray-50);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }

        .conversation-item:hover {
            background: var(--gray-100);
            transform: translateY(-2px);
        }

        .conversation-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--gray-700);
            flex-shrink: 0;
        }

        .conversation-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 0.25rem;
        }

        .conversation-name {
            font-weight: 600;
            color: var(--gray-900);
        }

        .conversation-time {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .conversation-preview {
            font-size: 0.875rem;
            color: var(--gray-600);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .unread-badge {
            display: inline-block;
            background: var(--primary);
            color: var(--light);
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .badge {
            display: inline-block;
            background: var(--primary);
            color: var(--light);
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        @media (max-width: 1024px) {
            .messages-widget {
                grid-column: span 1;
            }
        }

        /* Add these styles in the style section */
        .dashboard-widget {
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            margin-top: 2rem;
        }

        .widget-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--gray-50);
        }

        .widget-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .view-all:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .widget-content {
            padding: 1.5rem 2rem;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray-500);
        }

        .empty-state p {
            margin-top: 0.5rem;
        }

        .conversations-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .conversation-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: var(--border-radius);
            background: var(--gray-50);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
        }

        .conversation-item:hover {
            background: var(--gray-100);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--gray-300);
        }

        .conversation-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--gray-700);
            flex-shrink: 0;
            border: 2px solid var(--light);
            box-shadow: var(--shadow);
        }

        .conversation-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 0.5rem;
        }

        .conversation-name {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 1rem;
        }

        .conversation-time {
            font-size: 0.75rem;
            color: var(--gray-500);
            white-space: nowrap;
            margin-left: 1rem;
        }

        .conversation-preview {
            font-size: 0.875rem;
            color: var(--gray-600);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .unread-badge {
            display: inline-flex;
            align-items: center;
            background: var(--primary);
            color: var(--light);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            gap: 0.25rem;
        }

        .unread-badge::before {
            content: '•';
            font-size: 1.25rem;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="IPark Logo" style="max-width: 80px; display: block; margin: 0 auto 1rem auto;">
                <h1>
                    IPark Admin
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
                        <a href="parking_slots.php" class="nav-link">
                            <span class="nav-icon">🚗</span>
                            Parking Slots
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reservations.php" class="nav-link">
                            <span class="nav-icon">📅</span>
                            Reservations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="messages.php" class="nav-link">
                            <span class="nav-icon">📅</span>
                            Messages
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="users.php" class="nav-link">
                            <span class="nav-icon">👥</span>
                            User Management
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="logout-section">
                <a href="../logout.php" class="logout-link">
                    <span class="nav-icon">🚪</span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Dashboard Overview</h1>
                <p>Monitor your parking system performance and activity</p>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $totalSlots ?? 0; ?></h3>
                            <p>Total Parking Slots</p>
                        </div>
                        <div class="stat-icon">🏢</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $availableSlots ?? 0; ?></h3>
                            <p>Available Slots</p>
                        </div>
                        <div class="stat-icon">✅</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $totalUsers ?? 0; ?></h3>
                            <p>Registered Users</p>
                        </div>
                        <div class="stat-icon">👤</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $activeReservations ?? 0; ?></h3>
                            <p>Active Reservations</p>
                        </div>
                        <div class="stat-icon">📋</div>
                    </div>
                </div>
            </div>

            <!-- Recent Reservations Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Recent Reservations</h2>
                </div>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Slot</th>
                                <th>Car Type</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "
                                SELECT r.*, u.first_name, u.last_name, p.slot_number 
                                FROM ipark_reservations r 
                                JOIN ipark_users u ON r.user_id = u.id 
                                JOIN ipark_parking_slots p ON r.parking_slot_id = p.id 
                                ORDER BY r.created_at DESC 
                                LIMIT 5
                            ";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>{$row['first_name']} {$row['last_name']}</td>";
                                    echo "<td>{$row['slot_number']}</td>";
                                    echo "<td>{$row['car_type']}</td>";
                                    echo "<td>" . date('M d, Y H:i', strtotime($row['start_time'])) . "</td>";
                                    echo "<td>" . date('M d, Y H:i', strtotime($row['end_time'])) . "</td>";
                                    echo "<td><span class='badge badge-" . 
                                        ($row['status'] == 'Confirmed' ? 'success' : 
                                        ($row['status'] == 'Pending' ? 'warning' : 
                                        ($row['status'] == 'Cancelled' ? 'danger' : 'secondary'))) . 
                                        "'>{$row['status']}</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align: center; color: var(--gray-500); padding: 2rem;'>No recent reservations</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add this in the dashboard widgets section -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>
                        <span style="font-size: 1.25rem;">💬</span>
                        Recent Messages
                        <?php if($unreadMessages > 0): ?>
                            <span class="badge"><?php echo $unreadMessages; ?> unread</span>
                        <?php endif; ?>
                    </h3>
                    <a href="messages.php" class="view-all">View All Messages</a>
                </div>
                <div class="widget-content">
                    <?php if(empty($recentConversations)): ?>
                        <div class="empty-state">
                            <div style="font-size: 2rem;">💬</div>
                            <p>No messages yet</p>
                        </div>
                    <?php else: ?>
                        <div class="conversations-list">
                            <?php foreach($recentConversations as $conversation): ?>
                                <a href="messages.php?user_id=<?php echo $conversation['id']; ?>" class="conversation-item">
                                    <div class="conversation-avatar">
                                        <?php if($conversation['profile_picture']): ?>
                                            <img src="<?php echo htmlspecialchars($conversation['profile_picture']); ?>" alt="Avatar">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($conversation['first_name'], 0, 1) . substr($conversation['last_name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-header">
                                            <div class="conversation-name">
                                                <?php echo htmlspecialchars($conversation['first_name'] . ' ' . $conversation['last_name']); ?>
                                            </div>
                                            <div class="conversation-time">
                                                <?php echo date('M d, h:i A', strtotime($conversation['last_message_time'])); ?>
                                            </div>
                                        </div>
                                        <div class="conversation-preview">
                                            <?php echo htmlspecialchars(substr($conversation['last_message'], 0, 50)) . (strlen($conversation['last_message']) > 50 ? '...' : ''); ?>
                                        </div>
                                        <?php if($conversation['unread_count'] > 0): ?>
                                            <div class="unread-badge">
                                                <?php echo $conversation['unread_count']; ?> unread
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>