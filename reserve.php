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

// Fetch available parking slots
$sql = "SELECT * FROM ipark_parking_slots WHERE status = 'Vacant' ORDER BY floor_number, slot_number";
$result = $conn->query($sql);
$available_slots = $result->fetch_all(MYSQLI_ASSOC);

// Fetch user's active reservations
$sql = "
    SELECT r.*, p.slot_number, p.floor_number 
    FROM ipark_reservations r 
    JOIN ipark_parking_slots p ON r.parking_slot_id = p.id 
    WHERE r.user_id = ? AND r.status IN ('Pending', 'Confirmed')
    AND r.end_time > NOW()
    ORDER BY r.start_time ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$active_reservations = $result->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parking_slot_id = filter_input(INPUT_POST, 'parking_slot_id', FILTER_SANITIZE_NUMBER_INT);
    $car_type = filter_input(INPUT_POST, 'car_type', FILTER_SANITIZE_STRING);
    $plate_number = filter_input(INPUT_POST, 'plate_number', FILTER_SANITIZE_STRING);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Validate time slot availability
    $sql = "
        SELECT COUNT(*) as count FROM ipark_reservations 
        WHERE parking_slot_id = ? 
        AND status IN ('Pending', 'Confirmed')
        AND (
            (start_time <= ? AND end_time > ?) OR
            (start_time < ? AND end_time >= ?) OR
            (start_time >= ? AND end_time <= ?)
        )
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $parking_slot_id, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $_SESSION['error'] = 'Selected time slot is not available';
    } else {
        // Create reservation
        $sql = "
            INSERT INTO ipark_reservations (user_id, parking_slot_id, car_type, plate_number, start_time, end_time)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $_SESSION['user_id'], $parking_slot_id, $car_type, $plate_number, $start_time, $end_time);
        
        if ($stmt->execute()) {
            // Update parking slot status
            $sql = "UPDATE ipark_parking_slots SET status = 'Reserved' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $parking_slot_id);
            $stmt->execute();
            
            $_SESSION['success'] = 'Reservation created successfully';
            header('Location: my_reservations.php');
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Error creating reservation';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve a Parking Slot - IPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="styles/styles.css">
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

        /* Sidebar Styles (reuse from dashboard.php) */
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

        /* Reservation Form */
        .reservation-container {
            display: block;
        }

        @media (max-width: 1024px) {
            .reservation-container {
                grid-template-columns: 1fr;
            }
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card {
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            border: 1px solid var(--gray-200);
        }

        .form-card h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
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

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
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

        /* Active Reservations Card */
        .active-reservations {
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            border: 1px solid var(--gray-200);
            height: fit-content;
        }

        .active-reservations h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .reservation-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .reservation-item {
            background: var(--gray-50);
            border-radius: var(--border-radius);
            padding: 1rem;
            border: 1px solid var(--gray-200);
        }

        .reservation-item h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .reservation-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .reservation-details span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .reservation-status {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-confirmed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
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

        /* Animations */
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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

            .header h1 {
                font-size: 1.5rem;
            }

            .form-card,
            .active-reservations {
                padding: 1.5rem;
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

        /* Flatpickr Customization */
        .flatpickr-calendar {
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
        }

        .flatpickr-day.selected {
            background: var(--primary);
            border-color: var(--primary);
        }

        .flatpickr-day.selected:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .datetime-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
        }

        .datetime-group {
            flex: 1;
        }

        .datetime-label {
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .datetime-inputs {
            display: flex;
            gap: 0.75rem;
        }

        .date-input {
            flex: 2;
            background: var(--light);
            cursor: pointer;
        }

        .time-select {
            flex: 1;
            background: var(--light);
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .datetime-separator {
            color: var(--gray-500);
            font-weight: 500;
            padding-top: 1.75rem;
        }

        .flatpickr-calendar {
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            margin-top: 0.5rem;
        }

        .flatpickr-day.selected {
            background: var(--primary);
            border-color: var(--primary);
        }

        .flatpickr-day.selected:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .flatpickr-day.today {
            border-color: var(--primary);
        }

        .flatpickr-day.disabled {
            color: var(--gray-400);
            background: var(--gray-100);
        }

        @media (max-width: 640px) {
            .datetime-selector {
                flex-direction: column;
                align-items: stretch;
                gap: 1.5rem;
            }

            .datetime-separator {
                text-align: center;
                padding-top: 0;
            }

            .datetime-inputs {
                flex-direction: column;
            }

            .time-select {
                width: 100%;
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
                        <a href="reserve.php" class="nav-link active">
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
                <h1>Reserve a Parking Slot</h1>
                <p>Book your parking space in advance</p>
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

            <!-- Reservation Form -->
            <div class="form-container">
                <div class="form-card">
                    <h2>
                        <span style="font-size: 1.5rem;">🚗</span>
                        New Reservation
                    </h2>
                    <form action="reserve.php" method="POST" id="reservationForm">
                        <div class="form-group">
                            <label for="parking_slot_id" class="form-label">Select Parking Slot</label>
                            <select class="form-control form-select" id="parking_slot_id" name="parking_slot_id" required>
                                <option value="">Choose a parking slot</option>
                                <?php foreach($available_slots as $slot): ?>
                                    <option value="<?php echo $slot['id']; ?>">
                                        Slot <?php echo htmlspecialchars($slot['slot_number']); ?> 
                                        (Floor <?php echo htmlspecialchars($slot['floor_number']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="car_type" class="form-label">Car Type</label>
                            <select class="form-control form-select" id="car_type" name="car_type" required>
                                <option value="">Select car type</option>
                                <option value="Sedan">Sedan</option>
                                <option value="SUV">SUV</option>
                                <option value="Truck">Truck</option>
                                <option value="Van">Van</option>
                                <option value="Motorcycle">Motorcycle</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="plate_number" class="form-label">License Plate Number</label>
                            <input type="text" class="form-control" id="plate_number" name="plate_number" 
                                   required placeholder="Enter your license plate number">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Reservation Period</label>
                            <div class="datetime-selector">
                                <div class="datetime-group">
                                    <div class="datetime-label">Start</div>
                                    <div class="datetime-inputs">
                                        <input type="text" class="form-control date-input" id="start_date" placeholder="Select date" readonly>
                                        <select class="form-control time-select" id="start_time">
                                            <?php
                                            // Generate time options in 30-minute increments
                                            for ($hour = 0; $hour < 24; $hour++) {
                                                for ($minute = 0; $minute < 60; $minute += 30) {
                                                    $time = sprintf("%02d:%02d", $hour, $minute);
                                                    $displayTime = date("g:i A", strtotime($time));
                                                    echo "<option value='$time'>$displayTime</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="datetime-separator">to</div>
                                <div class="datetime-group">
                                    <div class="datetime-label">End</div>
                                    <div class="datetime-inputs">
                                        <input type="text" class="form-control date-input" id="end_date" placeholder="Select date" readonly>
                                        <select class="form-control time-select" id="end_time">
                                            <?php
                                            // Generate time options in 30-minute increments
                                            for ($hour = 0; $hour < 24; $hour++) {
                                                for ($minute = 0; $minute < 60; $minute += 30) {
                                                    $time = sprintf("%02d:%02d", $hour, $minute);
                                                    $displayTime = date("g:i A", strtotime($time));
                                                    echo "<option value='$time'>$displayTime</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="start_time" id="start_datetime">
                            <input type="hidden" name="end_time" id="end_datetime">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <span style="font-size: 1.25rem;">📅</span>
                            Reserve Now
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        let startDatePicker, endDatePicker;
        const startTimeSelect = document.getElementById('start_time');
        const endTimeSelect = document.getElementById('end_time');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const startDateTimeInput = document.getElementById('start_datetime');
        const endDateTimeInput = document.getElementById('end_datetime');

        function initializeDatePickers() {
            // Destroy existing instances if they exist
            if (startDatePicker) startDatePicker.destroy();
            if (endDatePicker) endDatePicker.destroy();

            // Initialize date pickers
            startDatePicker = flatpickr("#start_date", {
                dateFormat: "Y-m-d",
                minDate: "today",
                onChange: function(selectedDates) {
                    if (selectedDates[0]) {
                        // Update end date minimum
                        const minEndDate = new Date(selectedDates[0]);
                        endDatePicker.set("minDate", minEndDate);
                        
                        // If end date is before start date, update it
                        const currentEndDate = endDatePicker.selectedDates[0];
                        if (currentEndDate && currentEndDate < selectedDates[0]) {
                            endDatePicker.setDate(selectedDates[0]);
                        }
                        
                        updateDateTimeValues();
                    }
                }
            });

            endDatePicker = flatpickr("#end_date", {
                dateFormat: "Y-m-d",
                minDate: "today",
                onChange: function() {
                    updateDateTimeValues();
                }
            });

            // Set initial time values
            const now = new Date();
            const roundedMinutes = Math.ceil(now.getMinutes() / 30) * 30;
            const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(roundedMinutes).padStart(2, '0')}`;
            
            // Find and select the closest time option
            const timeOptions = Array.from(startTimeSelect.options);
            const currentTimeIndex = timeOptions.findIndex(option => option.value >= currentTime);
            if (currentTimeIndex !== -1) {
                startTimeSelect.selectedIndex = currentTimeIndex;
                endTimeSelect.selectedIndex = Math.min(currentTimeIndex + 1, timeOptions.length - 1); // Select next time slot for end time
            }

            // Add event listeners for time changes
            startTimeSelect.addEventListener('change', function() {
                updateDateTimeValues();
                validateTimeSelection();
            });
            endTimeSelect.addEventListener('change', function() {
                updateDateTimeValues();
                validateTimeSelection();
            });
        }

        function updateDateTimeValues() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            const startTime = startTimeSelect.value;
            const endTime = endTimeSelect.value;

            if (startDate && startTime) {
                startDateTimeInput.value = `${startDate} ${startTime}:00`;
            }
            if (endDate && endTime) {
                endDateTimeInput.value = `${endDate} ${endTime}:00`;
            }
        }

        function validateTimeSelection() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            const startTime = startTimeSelect.value;
            const endTime = endTimeSelect.value;

            if (startDate && endDate && startTime && endTime) {
                const startDateTime = new Date(`${startDate}T${startTime}`);
                const endDateTime = new Date(`${endDate}T${endTime}`);
                
                // If end time is before or equal to start time on the same day
                if (startDate === endDate && endTime <= startTime) {
                    // Find the next available time slot
                    const timeOptions = Array.from(endTimeSelect.options);
                    const startTimeIndex = Array.from(startTimeSelect.options).findIndex(opt => opt.value === startTime);
                    if (startTimeIndex < timeOptions.length - 1) {
                        endTimeSelect.selectedIndex = startTimeIndex + 1;
                        updateDateTimeValues();
                    }
                }
            }
        }

        // Initialize pickers when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeDatePickers();
        });

        // Enhanced form validation
        document.getElementById('reservationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const startDateTime = new Date(startDateTimeInput.value);
            const endDateTime = new Date(endDateTimeInput.value);
            const now = new Date();
            
            // Validate required fields
            if (!startDateInput.value || !endDateInput.value) {
                alert('Please select both start and end dates');
                return;
            }
            
            // Validate start time is not in the past
            if (startDateTime < now) {
                alert('Start time cannot be in the past');
                return;
            }
            
            // Validate end time is after start time
            if (endDateTime <= startDateTime) {
                alert('End time must be after start time');
                return;
            }
            
            // Validate minimum duration (30 minutes)
            const duration = (endDateTime - startDateTime) / (1000 * 60);
            if (duration < 30) {
                alert('Reservation must be at least 30 minutes long');
                return;
            }
            
            // Validate maximum duration (24 hours)
            if (duration > 24 * 60) {
                alert('Reservation cannot exceed 24 hours');
                return;
            }
            
            // If all validations pass, submit the form
            this.submit();
        });

        // Reinitialize pickers after form submission
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                initializeDatePickers();
            }
        });

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