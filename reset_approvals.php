<?php
session_start();

header('Content-Type: application/json');

try {
    // Clear the approved records from session
    $_SESSION['approved_records'] = [];
    
    echo json_encode([
        'success' => true,
        'message' => 'All approvals have been reset successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
