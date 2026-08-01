<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

defined('ACCESS') or die('Accès interdit');

require_once __DIR__ . '/../../../../src/config/app.php';
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'AbsenceController.php';

// Vérifier et forcer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'marquerAbsence') {
        $controller = new AbsenceController();
        $controller->handleAjax();
        exit;
    }
}

// Fallback
echo json_encode(['success' => false, 'message' => 'Action non trouvée: ' . ($_POST['action'] ?? 'aucune')]);