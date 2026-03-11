<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Require user authentication
requireUser();

// Fetch user data for sidebar
$stmt = $conn->prepare("SELECT * FROM ipark_users WHERE id = ?");
$user = null;
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}

// Get and validate reservation ID
$reservation_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$reservation_id) {
    $_SESSION['error'] = 'Invalid reservation ID';
    header('Location: my_reservations.php');
    exit;
}

// Fetch reservation details with parking slot and user information
$stmt = $conn->prepare("
    SELECT 
        r.*,
        p.slot_number,
        p.floor_number,
        p.status as slot_status,
        u.first_name,
        u.last_name,
        u.email,
        u.phone_number
    FROM ipark_reservations r
    JOIN ipark_parking_slots p ON r.parking_slot_id = p.id
    JOIN ipark_users u ON r.user_id = u.id
    WHERE r.id = ? AND r.user_id = ?
");

if ($stmt) {
    $stmt->bind_param("ii", $reservation_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();

    if (!$reservation) {
        $_SESSION['error'] = 'Reservation not found';
        header('Location: my_reservations.php');
        exit;
    }
} else {
    $_SESSION['error'] = 'Error fetching reservation details';
    header('Location: my_reservations.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details - IPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Include the same root variables and base styles as my_reservations.php */
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

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            max-width: calc(100vw - 280px);
        }

        .header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--light);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            color: var(--gray-700);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: var(--gray-50);
            transform: translateX(-4px);
        }

        /* Details Card */
        .details-card {
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }

        .details-header {
            padding: 2rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .details-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .reservation-status {
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .details-content {
            padding: 2rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        .details-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .detail-value {
            font-weight: 500;
            color: var(--gray-900);
        }

        .actions-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            gap: 1rem;
        }

        .action-button {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            border: none;
        }

        .btn-cancel {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .btn-cancel:hover {
            background: var(--danger);
            color: var(--light);
        }

        .btn-back {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .btn-back:hover {
            background: var(--gray-200);
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

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                max-width: 100vw;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }
        }

        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .back-button {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 640px) {
            .main-content {
                padding: 1rem;
            }

            .details-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .actions-section {
                flex-direction: column;
            }

            .action-button {
                width: 100%;
                justify-content: center;
            }
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
                <div>
                    <h1>Reservation Details</h1>
                    <p>View detailed information about your reservation</p>
                </div>
                <a href="my_reservations.php" class="back-button">
                    <span>←</span>
                    Back to Reservations
                </a>
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

            <div class="details-card">
                <div class="details-header">
                    <div class="details-title">
                        <span>🚗</span>
                        Slot <?php echo htmlspecialchars($reservation['slot_number']); ?>
                        (Floor <?php echo htmlspecialchars($reservation['floor_number']); ?>)
                    </div>
                    <div class="reservation-status status-<?php echo strtolower($reservation['status']); ?>">
                        <?php echo htmlspecialchars($reservation['status']); ?>
                    </div>
                </div>

                <div class="details-content">
                    <div class="details-grid">
                        <div class="details-section">
                            <div>
                                <h3 class="section-title">Reservation Information</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Reservation ID</span>
                                    <span class="detail-value">#<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date</span>
                                    <span class="detail-value"><?php echo date('F d, Y', strtotime($reservation['start_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Time</span>
                                    <span class="detail-value">
                                        <?php echo date('h:i A', strtotime($reservation['start_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($reservation['end_time'])); ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Duration</span>
                                    <span class="detail-value">
                                        <?php 
                                        $duration = (strtotime($reservation['end_time']) - strtotime($reservation['start_time'])) / 3600;
                                        echo number_format($duration, 1) . ' hours';
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <div>
                                <h3 class="section-title">Vehicle Information</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Car Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($reservation['car_type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">License Plate</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($reservation['plate_number']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="details-section">
                            <div>
                                <h3 class="section-title">Parking Slot Information</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Slot Number</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($reservation['slot_number']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Floor</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($reservation['floor_number']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Slot Status</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($reservation['slot_status']); ?></span>
                                </div>
                            </div>

                            <div>
                                <h3 class="section-title">Reservation Status</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($reservation['status']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Created At</span>
                                    <span class="detail-value"><?php echo date('F d, Y h:i A', strtotime($reservation['created_at'])); ?></span>
                                </div>
                                <?php if($reservation['status'] === 'Cancelled'): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Cancelled At</span>
                                        <span class="detail-value"><?php echo date('F d, Y h:i A', strtotime($reservation['updated_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="actions-section">
                        <?php if($reservation['status'] === 'Pending'): ?>
                            <button class="action-button btn-cancel" onclick="cancelReservation(<?php echo $reservation['id']; ?>)">
                                <span>❌</span>
                                Cancel Reservation
                            </button>
                        <?php endif; ?>
                        <a href="my_reservations.php" class="action-button btn-back">
                            <span>←</span>
                            Back to Reservations
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Cancel reservation function
        function cancelReservation(reservationId) {
            if (confirm('Are you sure you want to cancel this reservation?')) {
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

        // Add mobile sidebar functionality
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