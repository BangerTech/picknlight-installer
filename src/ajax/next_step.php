<?php
session_start();
if (isset($_SESSION['setup_step'])) {
    $_SESSION['setup_step']++;
}
echo json_encode(['success' => true]); 