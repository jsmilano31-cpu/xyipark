<?php
require_once '../auth.php';
require_once '../db_connect.php';

// Require admin authentication
requireAdmin();

// Handle user status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
    
    if ($action === 'deactivate') {
        $status = 'Inactive';
        $stmt = $conn->prepare("UPDATE ipark_users SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $status, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = 'User deactivated successfully';
            } else {
                $_SESSION['error'] = 'Error updating user status';
            }
        } else {
            $_SESSION['error'] = 'Error updating user status';
        }
    } elseif ($action === 'activate') {
        $status = 'Active';
        $stmt = $conn->prepare("UPDATE ipark_users SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $status, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = 'User activated successfully';
            } else {
                $_SESSION['error'] = 'Error updating user status';
            }
        } else {
            $_SESSION['error'] = 'Error updating user status';
        }
    }
    
    header('Location: users.php');
    exit();
}

// Fetch all users with their reservation count
$result = $conn->query("
    SELECT 
        u.*,
        COUNT(r.id) as total_reservations,
        MAX(r.created_at) as last_reservation_date
    FROM ipark_users u
    LEFT JOIN ipark_reservations r ON u.id = r.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");

if ($result) {
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
} else {
    $_SESSION['error'] = 'Error fetching users';
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - IPark Admin</title>
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

        /* Include all the existing styles from parking_slots.php */
        /* ... (copy all styles from parking_slots.php) ... */

        /* Additional styles specific to users */
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--gray-600);
            font-size: 1rem;
            flex-shrink: 0;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: var(--light);
            font-weight: 600;
        }

        .user-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .user-email {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .phone {
            color: var(--gray-900);
            font-weight: 500;
        }

        .address {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .reservation-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .total-reservations {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: var(--gray-50);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
        }

        .total-reservations .count {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .total-reservations .label {
            font-size: 0.75rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .activity-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .last-activity {
            color: var(--gray-900);
            font-weight: 500;
        }

        .no-activity {
            color: var(--gray-500);
            font-style: italic;
            font-size: 0.875rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }

        .user-details {
            background: var(--gray-50);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 0.5rem;
            border: 1px solid var(--gray-200);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .detail-item label {
            color: var(--gray-600);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .detail-item div {
            color: var(--gray-900);
            font-weight: 500;
        }

        /* Stats Grid Styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--light);
            border: 1px solid var(--primary);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
            color: var(--light);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--light);
            border: 1px solid var(--danger);
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: var(--shadow);
            color: var(--light);
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
                        <a href="reservations.php" class="nav-link">
                            <span class="nav-icon">📅</span>
                            Reservations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link active">
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
                    <h1>User Management</h1>
                    <p>Manage and monitor all registered users</p>
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

            <!-- Stats Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($users); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        echo array_sum(array_column($users, 'total_reservations'));
                        ?>
                    </div>
                    <div class="stat-label">Total Reservations</div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <form class="search-form" onsubmit="return false;">
                    <div class="search-group">
                        <input type="text" class="search-input" id="searchInput" 
                               placeholder="Search by name, email, or phone..." 
                               onkeyup="filterUsers()">
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact Info</th>
                                <th>Reservations</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="avatar">
                                                <?php if($user['profile_picture']): ?>
                                                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder">
                                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div class="phone"><?php echo htmlspecialchars($user['phone_number'] ?? 'Not provided'); ?></div>
                                            <div class="address"><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="reservation-info">
                                            <div class="total-reservations">
                                                <span class="count"><?php echo $user['total_reservations']; ?></span>
                                                <span class="label">Total</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="activity-info">
                                            <?php if($user['last_reservation_date']): ?>
                                                <div class="last-activity">
                                                    <?php echo date('M d, Y', strtotime($user['last_reservation_date'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="no-activity">No activity yet</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="toggleUserDetails(<?php echo $user['id']; ?>)">
                                                <span>👁️ View</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                                <span>✏️ Edit</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')">
                                                <span>🗑️ Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="user-details-<?php echo $user['id']; ?>" class="user-details-row" style="display: none;">
                                    <td colspan="5">
                                        <div class="user-details">
                                            <div class="details-grid">
                                                <div class="detail-item">
                                                    <label>Account Created</label>
                                                    <div><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <label>Last Updated</label>
                                                    <div><?php echo date('M d, Y H:i', strtotime($user['updated_at'])); ?></div>
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
        function toggleUserDetails(userId) {
            const detailsRow = document.getElementById(`user-details-${userId}`);
            if (detailsRow.style.display === 'none') {
                detailsRow.style.display = 'table-row';
            } else {
                detailsRow.style.display = 'none';
            }
        }

        // Add new functions for edit and delete
        function editUser(userId) {
            if (confirm('Do you want to edit this user?')) {
                window.location.href = `edit_user.php?id=${userId}`;
            }
        }

        function deleteUser(userId, userName) {
            if (confirm(`Are you sure you want to delete ${userName}? This action cannot be undone.`)) {
                window.location.href = `delete_user.php?id=${userId}`;
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr:not(.user-details-row)');
            
            rows.forEach(row => {
                const userName = row.querySelector('.user-name').textContent.toLowerCase();
                const userEmail = row.querySelector('.user-email').textContent.toLowerCase();
                const userPhone = row.querySelector('.phone').textContent.toLowerCase();
                const userAddress = row.querySelector('.address').textContent.toLowerCase();
                
                if (userName.includes(searchTerm) || 
                    userEmail.includes(searchTerm) || 
                    userPhone.includes(searchTerm) || 
                    userAddress.includes(searchTerm)) {
                    row.style.display = '';
                    // Show the details row if it was visible
                    const userId = row.querySelector('.action-buttons button').getAttribute('onclick').match(/\d+/)[0];
                    const detailsRow = document.getElementById(`user-details-${userId}`);
                    if (detailsRow.style.display === 'table-row') {
                        detailsRow.style.display = 'table-row';
                    }
                } else {
                    row.style.display = 'none';
                    // Hide the details row
                    const userId = row.querySelector('.action-buttons button').getAttribute('onclick').match(/\d+/)[0];
                    document.getElementById(`user-details-${userId}`).style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 