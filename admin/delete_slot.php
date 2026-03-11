<?php
session_start();
require_once '../auth.php';
require_once '../db_connect.php';

// Require admin authentication
requireAdmin();

if (isset($_GET['id'])) {
    $slot_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    // Check if the slot exists
    $stmt = $conn->prepare("SELECT status FROM ipark_parking_slots WHERE id = ?");
    if (!$stmt) {
        $_SESSION['error'] = 'Error deleting parking slot. Please try again.';
    } else {
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $slot = $result->fetch_assoc();

        if (!$slot) {
            $_SESSION['error'] = 'Parking slot not found.';
            header('Location: parking_slots.php');
            exit();
        }

        // Check if the slot has any active reservations
        $stmt = $conn->prepare("
            SELECT COUNT(*) as active_count 
            FROM ipark_reservations 
            WHERE parking_slot_id = ? 
            AND status IN ('Confirmed', 'Pending')
        ");
        if ($stmt) {
            $stmt->bind_param("i", $slot_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['active_count'] > 0) {
                $_SESSION['error'] = 'Cannot delete: This slot has active or pending reservations.';
            } else {
                // Begin transaction
                $conn->begin_transaction();

                // Delete any past reservations associated with this slot
                $stmt = $conn->prepare("DELETE FROM ipark_reservations WHERE parking_slot_id = ?");
                $stmt->bind_param("i", $slot_id);
                $stmt->execute();

                // Delete the parking slot
                $stmt = $conn->prepare("DELETE FROM ipark_parking_slots WHERE id = ?");
                $stmt->bind_param("i", $slot_id);
                if ($stmt->execute()) {
                    $conn->commit();
                    $_SESSION['success'] = 'Parking slot deleted successfully.';
                } else {
                    $conn->rollback();
                    $_SESSION['error'] = 'Error deleting parking slot. Please try again.';
                }
            }
        } else {
            $_SESSION['error'] = 'Error deleting parking slot. Please try again.';
        }
    }
} else {
    $_SESSION['error'] = 'Invalid request.';
}

// Redirect back to parking slots page
header('Location: parking_slots.php');
exit();
?> 