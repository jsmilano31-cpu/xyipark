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

// Fetch all user's reservations with parking slot details
$sql = "
    SELECT r.*, p.slot_number, p.floor_number 
    FROM ipark_reservations r 
    JOIN ipark_parking_slots p ON r.parking_slot_id = p.id 
    WHERE r.user_id = ? 
    ORDER BY 
        CASE 
            WHEN r.status IN ('Pending', 'Confirmed') AND r.end_time > NOW() THEN 1
            WHEN r.status = 'Completed' THEN 2
            ELSE 3
        END,
        r.start_time DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$reservations = $result->fetch_all(MYSQLI_ASSOC);

// Count reservations by status
$sql = "
    SELECT 
        status,
        COUNT(*) as count
    FROM ipark_reservations 
    WHERE user_id = ? 
    GROUP BY status
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$status_counts = [];
while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - IPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Include the same root variables and base styles as dashboard.php */
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

        /* Include the same app-container and sidebar styles as dashboard.php */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content Styles */
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
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--gray-600);
            font-size: 1.1rem;
        }

        /* Status Filter Tabs */
        .status-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .status-tab {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--light);
            border: 1px solid var(--gray-200);
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-tab:hover {
            background: var(--gray-50);
            color: var(--gray-900);
        }

        .status-tab.active {
            background: var(--primary);
            color: var(--light);
            border-color: var(--primary);
        }

        .status-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            font-size: 0.875rem;
        }

        /* Reservations Grid */
        .reservations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .reservation-card {
            background: var(--light);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: transform 0.3s ease;
        }

        .reservation-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .reservation-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .reservation-status {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
        }

        .reservation-details {
            display: grid;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .detail-icon {
            font-size: 1.25rem;
            opacity: 0.8;
        }

        .reservation-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }

        .action-button {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-cancel {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .btn-cancel:hover {
            background: var(--danger);
            color: var(--light);
        }

        .btn-view {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }

        .btn-view:hover {
            background: var(--primary);
            color: var(--light);
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

        /* No Reservations Message */
        .no-reservations {
            text-align: center;
            padding: 3rem;
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }

        .no-reservations-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-400);
        }

        .no-reservations h3 {
            font-size: 1.25rem;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .no-reservations p {
            color: var(--gray-500);
            margin-bottom: 1.5rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                max-width: 100vw;
            }
        }

        @media (max-width: 640px) {
            .main-content {
                padding: 1rem;
            }

            .status-tabs {
                flex-direction: column;
            }

            .status-tab {
                width: 100%;
            }

            .reservations-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Add sidebar styles */
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
                        <a href="dashboard.php" class="nav-link">
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
                        <a href="my_reservations.php" class="nav-link active">
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
                <h1>My Reservations</h1>
                <p>View and manage your parking reservations</p>
            </div>

            <!-- Status Filter Tabs -->
            <div class="status-tabs">
                <div class="status-tab active" data-status="all">
                    <span>📋</span>
                    All Reservations
                    <span class="status-count"><?php echo count($reservations); ?></span>
                </div>
                <div class="status-tab" data-status="active">
                    <span>🚗</span>
                    Active
                    <span class="status-count">
                        <?php echo ($status_counts['Pending'] ?? 0) + ($status_counts['Confirmed'] ?? 0); ?>
                    </span>
                </div>
                <div class="status-tab" data-status="completed">
                    <span>✅</span>
                    Completed
                    <span class="status-count"><?php echo $status_counts['Completed'] ?? 0; ?></span>
                </div>
                <div class="status-tab" data-status="cancelled">
                    <span>❌</span>
                    Cancelled
                    <span class="status-count"><?php echo $status_counts['Cancelled'] ?? 0; ?></span>
                </div>
            </div>

            <!-- Reservations Grid -->
            <?php if(empty($reservations)): ?>
                <div class="no-reservations">
                    <div class="no-reservations-icon">🚗</div>
                    <h3>No Reservations Yet</h3>
                    <p>You haven't made any parking reservations yet.</p>
                    <a href="reserve.php" class="action-button btn-view" style="display: inline-flex;">
                        <span>📅</span>
                        Make a Reservation
                    </a>
                </div>
            <?php else: ?>
                <div class="reservations-grid">
                    <?php foreach($reservations as $reservation): ?>
                        <div class="reservation-card" data-status="<?php echo (in_array($reservation['status'], ['Pending', 'Confirmed']) ? 'active' : strtolower($reservation['status'])); ?>">
                            <div class="reservation-header">
                                <div class="reservation-title">
                                    Slot <?php echo htmlspecialchars($reservation['slot_number']); ?>
                                </div>
                                <div class="reservation-status status-<?php echo strtolower($reservation['status']); ?>">
                                    <?php echo htmlspecialchars($reservation['status']); ?>
                                </div>
                            </div>
                            
                            <div class="reservation-details">
                                <div class="detail-item">
                                    <span class="detail-icon">🏢</span>
                                    Floor <?php echo htmlspecialchars($reservation['floor_number']); ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">🚗</span>
                                    <?php echo htmlspecialchars($reservation['car_type']); ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">📝</span>
                                    <?php echo htmlspecialchars($reservation['plate_number']); ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">📅</span>
                                    <?php echo date('M d, Y', strtotime($reservation['start_time'])); ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">⏰</span>
                                    <?php echo date('h:i A', strtotime($reservation['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($reservation['end_time'])); ?>
                                </div>
                            </div>

                            <div class="reservation-actions">
                                <?php if($reservation['status'] === 'Pending'): ?>
                                    <button class="action-button btn-cancel" onclick="cancelReservation(<?php echo $reservation['id']; ?>)">
                                        <span>❌</span>
                                        Cancel
                                    </button>
                                <?php endif; ?>
                                <a href="reservation_details.php?id=<?php echo $reservation['id']; ?>" class="action-button btn-view">
                                    <span>👁️</span>
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Status filter functionality
        document.querySelectorAll('.status-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Update active tab
                document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const status = tab.dataset.status;
                const cards = document.querySelectorAll('.reservation-card');

                cards.forEach(card => {
                    if (status === 'all' || card.dataset.status === status) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Cancel reservation function
        function cancelReservation(reservationId) {
            if (confirm('Are you sure you want to cancel this reservation?')) {
                // Add AJAX call to cancel_reservation.php
                fetch('cancel_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'reservation_id=' + reservationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error cancelling reservation');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error cancelling reservation');
                });
            }
        }

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