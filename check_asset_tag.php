<?php
/**
 * AJAX Endpoint to check asset tag availability
 * Location: check_asset_tag.php (root directory)
 * Called via JavaScript when user types in asset tag field
 */

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'models/models.php';

// Ensure user is logged in
requireLogin();

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get asset tag from POST data
$input = json_decode(file_get_contents('php://input'), true);
$assetTag = trim($input['asset_tag'] ?? '');
$excludeId = $input['exclude_id'] ?? null;

if (empty($assetTag)) {
    echo json_encode(['available' => true]);
    exit;
}

try {
    $assetModel = new Asset();
    $isAvailable = $assetModel->isAssetTagAvailable($assetTag, $excludeId);
    
    echo json_encode([
        'available' => $isAvailable,
        'asset_tag' => $assetTag
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to check availability',
        'message' => $e->getMessage()
    ]);
}
?>