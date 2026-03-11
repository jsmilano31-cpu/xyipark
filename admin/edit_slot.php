<?php
require_once '../auth.php';
require_once '../db_connect.php';

// Require admin authentication
requireAdmin();

// Handle GET request for AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    $slot_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    $stmt = $conn->prepare("SELECT * FROM ipark_parking_slots WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $slot = $result->fetch_assoc();
        
        if ($slot) {
            echo json_encode($slot);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Parking slot not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error fetching slot data']);
    }
    exit();
}

// Handle POST request for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slot_id'])) {
    $slot_id = filter_input(INPUT_POST, 'slot_id', FILTER_SANITIZE_NUMBER_INT);
    $slot_number = filter_input(INPUT_POST, 'slot_number', FILTER_SANITIZE_STRING);
    $floor_number = filter_input(INPUT_POST, 'floor_number', FILTER_SANITIZE_NUMBER_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    // Check if slot exists
    $stmt = $conn->prepare("SELECT id FROM ipark_parking_slots WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            $_SESSION['error'] = 'Parking slot not found';
            header('Location: parking_slots.php');
            exit();
        }
    }

    // Check if the new slot number already exists for other slots
    $stmt = $conn->prepare("SELECT id FROM ipark_parking_slots WHERE slot_number = ? AND id != ?");
    if ($stmt) {
        $stmt->bind_param("si", $slot_number, $slot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_assoc()) {
            $_SESSION['error'] = 'Slot number already exists';
            header('Location: parking_slots.php');
            exit();
        }
    }

    // Update the parking slot
    $stmt = $conn->prepare("UPDATE ipark_parking_slots SET slot_number = ?, floor_number = ?, status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sdsi", $slot_number, $floor_number, $status, $slot_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Parking slot updated successfully';
        } else {
            $_SESSION['error'] = 'Error updating parking slot';
        }
    } else {
        $_SESSION['error'] = 'Error updating parking slot';
    }
    
    header('Location: parking_slots.php');
    exit();
}

// If no valid request method, redirect to parking slots page
header('Location: parking_slots.php');
exit();
?> 