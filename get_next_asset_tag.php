<?php
/**
 * AJAX Endpoint to get next asset tag suggestion
 * Location: get_next_asset_tag.php (root directory)
 * Called via JavaScript when device type changes
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

// Get device type from POST data
$input = json_decode(file_get_contents('php://input'), true);
$deviceType = $input['device_type'] ?? '';

if (empty($deviceType)) {
    http_response_code(400);
    echo json_encode(['error' => 'Device type is required']);
    exit;
}

try {
    $assetModel = new Asset();
    $suggestedTag = $assetModel->getNextAssetTag($deviceType);
    
    // Check if the suggested tag is available
    $isAvailable = $assetModel->isAssetTagAvailable($suggestedTag);
    
    echo json_encode([
        'success' => true,
        'suggested_tag' => $suggestedTag,
        'is_available' => $isAvailable
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to generate asset tag suggestion',
        'message' => $e->getMessage()
    ]);
}
?>