<?php
require_once '../auth.php';
require_once '../db_connect.php';

// Require admin authentication
requireAdmin();

// Fetch all reservations with user and parking slot details
$result = $conn->query("
    SELECT r.*, 
           u.first_name, u.last_name, u.email, u.phone_number,
           p.slot_number, p.floor_number
    FROM ipark_reservations r
    JOIN ipark_users u ON r.user_id = u.id
    JOIN ipark_parking_slots p ON r.parking_slot_id = p.id
    ORDER BY r.created_at DESC
");

if ($result) {
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
} else {
    $_SESSION['error'] = 'Error fetching reservations';
    $reservations = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations Management - IPark Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reuse existing styles from parking_slots.php */
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

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--light);
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        .btn-danger {
            background: var(--danger);
            color: var(--light);
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .alert-close:hover {
            opacity: 1;
        }

        /* Table Container */
        .table-container {
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--gray-200);
            animation: fadeInUp 0.6s ease;
        }

        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
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

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: var(--gray-50);
            transform: scale(1.01);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Badge Styles */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            min-width: 100px;
            justify-content: center;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .badge-secondary {
            background: var(--gray-200);
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .modal {
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-500);
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: var(--gray-700);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes modalSlideIn {
            from { transform: scale(0.95) translateY(-20px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
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
        }

        @media (max-width: 640px) {
            .main-content {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table-wrapper {
                font-size: 0.875rem;
            }

            .table th,
            .table td {
                padding: 0.75rem 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .modal {
                width: 95%;
                margin: 1rem;
            }
        }

        /* Include all the existing styles from parking_slots.php */
        /* ... (copy all styles from parking_slots.php) ... */

        /* Additional styles specific to reservations */
        .reservation-details {
            background: var(--gray-50);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            display: none;
        }

        .reservation-details.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        .reservation-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            color: var(--gray-600);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .detail-value {
            color: var(--gray-900);
            font-weight: 500;
        }

        .badge-info {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .badge-completed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .badge-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-section {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .filter-select {
            padding: 0.5rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            color: var(--gray-700);
            background: var(--light);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
                        <a href="dashboard.php" class="nav-link">
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
                        <a href="reservations.php" class="nav-link active">
                            <span class="nav-icon">📅</span>
                            Reservations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="messages.php" class="nav-link">
                            <span class="nav-icon">💬</span>
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
                <div class="header-info">
                    <h1>Reservations Management</h1>
                    <p>View and manage all parking reservations</p>
                </div>
            </div>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 1.25rem;">✅</span>
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endif; ?>

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

            <!-- Filters Section -->
            <div class="filter-section">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="filter-select" id="statusFilter" onchange="filterReservations()">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Date Range</label>
                        <select class="filter-select" id="dateFilter" onchange="filterReservations()">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Floor</label>
                        <select class="filter-select" id="floorFilter" onchange="filterReservations()">
                            <option value="">All Floors</option>
                            <?php
                            $floors = array_unique(array_column($reservations, 'floor_number'));
                            sort($floors);
                            foreach($floors as $floor): ?>
                                <option value="<?php echo $floor; ?>">Floor <?php echo $floor; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="table-container">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reservation ID</th>
                                <th>User</th>
                                <th>Parking Slot</th>
                                <th>Car Details</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservations as $reservation): ?>
                                <tr class="reservation-row" 
                                    data-status="<?php echo htmlspecialchars($reservation['status']); ?>"
                                    data-floor="<?php echo htmlspecialchars($reservation['floor_number']); ?>"
                                    data-date="<?php echo strtotime($reservation['start_time']); ?>">
                                    <td>
                                        <strong>#<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($reservation['email']); ?></small>
                                    </td>
                                    <td>
                                        <div>Slot <?php echo htmlspecialchars($reservation['slot_number']); ?></div>
                                        <small class="text-muted">Floor <?php echo htmlspecialchars($reservation['floor_number']); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($reservation['car_type']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($reservation['plate_number']); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo date('M d, Y', strtotime($reservation['start_time'])); ?></div>
                                        <small class="text-muted">
                                            <?php echo date('h:i A', strtotime($reservation['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($reservation['end_time'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $reservation['status'] == 'Completed' ? 'completed' : 
                                                ($reservation['status'] == 'Cancelled' ? 'cancelled' : 
                                                ($reservation['status'] == 'Pending' ? 'pending' : 'info')); 
                                        ?>">
                                            <?php echo htmlspecialchars($reservation['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-primary view-details" 
                                                    data-id="<?php echo $reservation['id']; ?>" 
                                                    title="View Details">
                                                👁️
                                            </button>
                                            <?php if($reservation['status'] == 'Pending'): ?>
                                                <button class="btn btn-sm btn-success confirm-reservation" 
                                                        data-id="<?php echo $reservation['id']; ?>" 
                                                        title="Confirm Reservation">
                                                    ✓
                                                </button>
                                                <button class="btn btn-sm btn-danger cancel-reservation" 
                                                        data-id="<?php echo $reservation['id']; ?>" 
                                                        title="Cancel Reservation">
                                                    ✕
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="details-row" style="display: none;">
                                    <td colspan="7">
                                        <div class="reservation-details" id="details-<?php echo $reservation['id']; ?>">
                                            <div class="reservation-details-grid">
                                                <div class="detail-item">
                                                    <span class="detail-label">User Contact</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($reservation['phone_number']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Reservation Date</span>
                                                    <span class="detail-value"><?php echo date('F d, Y', strtotime($reservation['created_at'])); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Duration</span>
                                                    <span class="detail-value">
                                                        <?php
                                                        $start = new DateTime($reservation['start_time']);
                                                        $end = new DateTime($reservation['end_time']);
                                                        $duration = $start->diff($end);
                                                        echo $duration->format('%h hours %i minutes');
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Last Updated</span>
                                                    <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($reservation['updated_at'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // View details toggle
        document.querySelectorAll('.view-details').forEach(button => {
            button.onclick = function() {
                const reservationId = this.dataset.id;
                const detailsRow = this.closest('tr').nextElementSibling;
                const detailsDiv = document.getElementById(`details-${reservationId}`);
                
                // Toggle details visibility
                if (detailsRow.style.display === 'none') {
                    detailsRow.style.display = 'table-row';
                    detailsDiv.classList.add('active');
                    this.textContent = '👁️‍🗨️';
                } else {
                    detailsRow.style.display = 'none';
                    detailsDiv.classList.remove('active');
                    this.textContent = '👁️';
                }
            };
        });

        // Filter reservations
        function filterReservations() {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const floorFilter = document.getElementById('floorFilter').value;
            
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay());
            const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);

            document.querySelectorAll('.reservation-row').forEach(row => {
                const status = row.dataset.status;
                const floor = row.dataset.floor;
                const date = new Date(parseInt(row.dataset.date) * 1000);
                
                let showStatus = !statusFilter || status === statusFilter;
                let showFloor = !floorFilter || floor === floorFilter;
                let showDate = true;

                if (dateFilter) {
                    if (dateFilter === 'today') {
                        showDate = date >= today;
                    } else if (dateFilter === 'week') {
                        showDate = date >= weekStart;
                    } else if (dateFilter === 'month') {
                        showDate = date >= monthStart;
                    }
                }

                row.style.display = showStatus && showFloor && showDate ? '' : 'none';
                
                // Hide details if row is hidden
                const detailsRow = row.nextElementSibling;
                if (detailsRow && detailsRow.classList.contains('details-row')) {
                    detailsRow.style.display = row.style.display;
                    const detailsDiv = detailsRow.querySelector('.reservation-details');
                    if (detailsDiv) {
                        detailsDiv.classList.remove('active');
                    }
                }
            });
        }

        // Confirm reservation
        document.querySelectorAll('.confirm-reservation').forEach(button => {
            button.onclick = function() {
                if (confirm('Are you sure you want to confirm this reservation?')) {
                    const reservationId = this.dataset.id;
                    window.location.href = `update_reservation.php?id=${reservationId}&action=confirm`;
                }
            };
        });

        // Cancel reservation
        document.querySelectorAll('.cancel-reservation').forEach(button => {
            button.onclick = function() {
                if (confirm('Are you sure you want to cancel this reservation?')) {
                    const reservationId = this.dataset.id;
                    window.location.href = `update_reservation.php?id=${reservationId}&action=cancel`;
                }
            };
        });

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