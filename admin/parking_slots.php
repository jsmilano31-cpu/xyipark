<?php
require_once '../auth.php';
require_once '../db_connect.php';

// Require admin authentication
requireAdmin();

// Handle form submission for adding new parking slot
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slot_number = filter_input(INPUT_POST, 'slot_number', FILTER_SANITIZE_STRING);
    $floor_number = filter_input(INPUT_POST, 'floor_number', FILTER_SANITIZE_NUMBER_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    // Check if slot number already exists
    $stmt = $conn->prepare("SELECT id FROM ipark_parking_slots WHERE slot_number = ?");
    $stmt->bind_param("s", $slot_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->fetch_assoc()) {
        $_SESSION['error'] = 'Parking slot number already exists';
    } else {
        // Insert new parking slot
        $stmt = $conn->prepare("INSERT INTO ipark_parking_slots (slot_number, floor_number, status) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sds", $slot_number, $floor_number, $status);
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Parking slot added successfully';
            } else {
                $_SESSION['error'] = 'Error adding parking slot';
            }
        } else {
            $_SESSION['error'] = 'Error adding parking slot';
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: parking_slots.php');
    exit();
}

// Fetch all parking slots
$result = $conn->query("SELECT * FROM ipark_parking_slots ORDER BY floor_number, slot_number");
if ($result) {
    $parking_slots = [];
    while ($row = $result->fetch_assoc()) {
        $parking_slots[] = $row;
    }
} else {
    $_SESSION['error'] = 'Error fetching parking slots';
    $parking_slots = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Slots Management - IPark Admin</title>
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
                        <a href="parking_slots.php" class="nav-link active">
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
                <div class="header-info">
                    <h1>Parking Slots Management</h1>
                    <p>Manage and monitor all parking slots in your facility</p>
                </div>
                <button type="button" class="btn btn-primary" onclick="openModal()">
                    <span style="font-size: 1.25rem;">➕</span>
                    Add New Slot
                </button>
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

            <!-- Parking Slots Table -->
            <div class="table-container">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Slot Number</th>
                                <th>Floor</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($parking_slots as $slot): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($slot['slot_number']); ?></strong>
                                    </td>
                                    <td>
                                        Floor <?php echo htmlspecialchars($slot['floor_number']); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $slot['status'] == 'Vacant' ? 'success' : 
                                                ($slot['status'] == 'Occupied' ? 'danger' : 
                                                ($slot['status'] == 'Reserved' ? 'warning' : 'secondary')); 
                                        ?>">
                                            <?php echo htmlspecialchars($slot['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($slot['updated_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-primary edit-slot" data-id="<?php echo $slot['id']; ?>" title="Edit Slot">
                                                ✏️
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-slot" data-id="<?php echo $slot['id']; ?>" title="Delete Slot" onclick="confirmDelete(<?php echo $slot['id']; ?>)">
                                                🗑️
                                            </button>
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

    <!-- Add/Edit Slot Modal -->
    <div class="modal-overlay" id="slotModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add New Parking Slot</h2>
                <button type="button" class="modal-close" onclick="closeModal()">×</button>
            </div>
            <form id="slotForm" action="parking_slots.php" method="POST">
                <input type="hidden" id="slot_id" name="slot_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="slot_number" class="form-label">Slot Number</label>
                        <input type="text" class="form-control" id="slot_number" name="slot_number" required placeholder="e.g., 1">
                    </div>
                    <div class="form-group">
                        <label for="floor_number" class="form-label">Floor Number</label>
                        <input type="number" class="form-control" id="floor_number" name="floor_number" required placeholder="e.g., 1" min="1">
                    </div>
                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control form-select" id="status" name="status" required>
                            <option value="">Select status</option>
                            <option value="Vacant">Vacant</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Out of Service">Out of Service</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitButton">
                        <span style="font-size: 1rem;">💾</span>
                        <span id="submitButtonText">Add Slot</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(isEdit = false) {
            document.getElementById('slotModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            
            if (!isEdit) {
                // Reset form for new slot
                document.getElementById('slotForm').reset();
                document.getElementById('slot_id').value = '';
                document.getElementById('modalTitle').textContent = 'Add New Parking Slot';
                document.getElementById('submitButtonText').textContent = 'Add Slot';
                document.getElementById('slotForm').action = 'parking_slots.php';
            }
        }

        function closeModal() {
            document.getElementById('slotModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Edit slot function
        function editSlot(slotId) {
            // Show loading state
            const loadingAlert = showAlert('Loading slot data...', 'info');
            
            // Fetch slot data
            fetch(`edit_slot.php?id=${slotId}`)
                .then(response => {
                    // Remove loading alert
                    loadingAlert.remove();
                    
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || `HTTP error! status: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // Populate form with slot data
                    document.getElementById('slot_id').value = data.id;
                    document.getElementById('slot_number').value = data.slot_number;
                    document.getElementById('floor_number').value = data.floor_number;
                    document.getElementById('status').value = data.status;
                    
                    // Update modal UI
                    document.getElementById('modalTitle').textContent = 'Edit Parking Slot';
                    document.getElementById('submitButtonText').textContent = 'Update Slot';
                    document.getElementById('slotForm').action = 'edit_slot.php';
                    
                    // Open modal
                    openModal(true);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert(error.message, 'danger');
                });
        }

        // Helper function to show alerts
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.25rem;">${type === 'success' ? '✅' : type === 'danger' ? '⚠️' : 'ℹ️'}</span>
                    ${message}
                </div>
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
            `;
            
            // Insert alert at the top of the main content
            const mainContent = document.querySelector('.main-content');
            const header = mainContent.querySelector('.header');
            mainContent.insertBefore(alertDiv, header.nextSibling);
            
            // Auto-hide alert after 5 seconds
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                alertDiv.style.transform = 'translateY(-20px)';
                setTimeout(() => alertDiv.remove(), 300);
            }, 5000);

            return alertDiv;
        }

        // Close modal when clicking outside
        document.getElementById('slotModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Delete confirmation
        function confirmDelete(slotId) {
            if (confirm('Are you sure you want to delete this parking slot?')) {
                window.location.href = `delete_slot.php?id=${slotId}`;
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

        // Update click handlers for edit buttons
        document.querySelectorAll('.edit-slot').forEach(button => {
            button.onclick = function() {
                editSlot(this.dataset.id);
            };
        });
    </script>
</body>
</html>