<?php
require_once 'connect.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        sendXKCDUpdatesToSubscribers();
        echo json_encode(['success' => true, 'message' => 'Comics sent successfully to all verified subscribers!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error sending comics: ' . $e->getMessage()]);
    }
    exit;
}
?> 