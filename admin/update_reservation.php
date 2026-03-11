<?php
require_once '../auth.php';
require_once '../db_connect.php';

// Require admin authentication
requireAdmin();

// Check if required parameters are present
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['error'] = 'Invalid request parameters';
    header('Location: reservations.php');
    exit();
}

$reservation_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

// Validate action
if (!in_array($action, ['confirm', 'cancel'])) {
    $_SESSION['error'] = 'Invalid action';
    header('Location: reservations.php');
    exit();
}

// Start transaction
$conn->begin_transaction();

// Get current reservation status
$stmt = $conn->prepare("SELECT status, parking_slot_id FROM ipark_reservations WHERE id = ?");
if (!$stmt) {
    $conn->rollback();
    $_SESSION['error'] = 'Error updating reservation';
} else {
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();

    if (!$reservation) {
        $conn->rollback();
        $_SESSION['error'] = 'Reservation not found';
    } elseif ($reservation['status'] !== 'Pending') {
        $conn->rollback();
        $_SESSION['error'] = 'Can only update pending reservations';
    } else {
        // Update reservation status
        $new_status = $action === 'confirm' ? 'Confirmed' : 'Cancelled';
        $stmt = $conn->prepare("UPDATE ipark_reservations SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $reservation_id);
        $stmt->execute();

        // Update parking slot status
        $slot_status = $action === 'confirm' ? 'Reserved' : 'Vacant';
        $stmt = $conn->prepare("UPDATE ipark_parking_slots SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $slot_status, $reservation['parking_slot_id']);
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success'] = 'Reservation ' . strtolower($new_status) . ' successfully';
        } else {
            $conn->rollback();
            $_SESSION['error'] = 'Error updating reservation';
        }
    }
}

header('Location: reservations.php');
exit();
?> 