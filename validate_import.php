<?php
/**
 * AJAX Import Validation
 * Location: validate_import.php (root directory)
 * Purpose: Validate import data before actual import
 */

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'models/models.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$importData = $input['data'] ?? [];

if (empty($importData)) {
    echo json_encode(['error' => 'No data provided']);
    exit;
}

$assetModel = new Asset();
$employeeModel = new Employee();

// Get existing data for validation
$existingAssets = $assetModel->getAll();
$existingSerialNumbers = array_column($existingAssets, 'serial_number');
$existingAssetTags = array_column($existingAssets, 'asset_tag');

$employees = $employeeModel->getAll();
$employeeNames = array_map('strtolower', array_column($employees, 'name'));
$employeeEmails = array_map('strtolower', array_filter(array_column($employees, 'email')));

$validationResults = [
    'valid_rows' => 0,
    'invalid_rows' => 0,
    'warnings' => [],
    'errors' => [],
    'duplicates' => [],
    'missing_users' => [],
    'invalid_device_types' => [],
    'invalid_statuses' => []
];

$validDeviceTypes = ['Laptop', 'Desktop', 'Monitor', 'Projector', 'Tablet', 'Phone', 'Server', 'Printer', 'Mouse', 'Other'];
$validStatuses = ['Active', 'Spare', 'Retired', 'Maintenance', 'Lost'];

foreach ($importData as $rowIndex => $row) {
    $rowNumber = $rowIndex + 1;
    $rowValid = true;
    
    // Check for required fields
    if (empty($row['serial_number']) && empty($row['asset_tag'])) {
        $validationResults['errors'][] = "Row $rowNumber: Missing both Serial Number and Asset Tag";
        $rowValid = false;
    }
    
    // Check for duplicate serial numbers
    if (!empty($row['serial_number'])) {
        if (in_array($row['serial_number'], $existingSerialNumbers)) {
            $validationResults['duplicates'][] = "Row $rowNumber: Serial Number '{$row['serial_number']}' already exists";
            $rowValid = false;
        }
    }
    
    // Check for duplicate asset tags
    if (!empty($row['asset_tag'])) {
        if (in_array($row['asset_tag'], $existingAssetTags)) {
            $validationResults['duplicates'][] = "Row $rowNumber: Asset Tag '{$row['asset_tag']}' already exists";
            $rowValid = false;
        }
    }
    
    // Check device type
    if (!empty($row['device_type']) && !in_array($row['device_type'], $validDeviceTypes)) {
        $validationResults['invalid_device_types'][] = "Row $rowNumber: Invalid Device Type '{$row['device_type']}'";
        $validationResults['warnings'][] = "Row $rowNumber: Device Type will default to 'Other'";
    }
    
    // Check status
    if (!empty($row['status']) && !in_array($row['status'], $validStatuses)) {
        $validationResults['invalid_statuses'][] = "Row $rowNumber: Invalid Status '{$row['status']}'";
        $validationResults['warnings'][] = "Row $rowNumber: Status will default to 'Active'";
    }
    
    // Check current user
    if (!empty($row['current_user'])) {
        $userFound = false;
        $userName = strtolower(trim($row['current_user']));
        
        if (in_array($userName, $employeeNames) || in_array($userName, $employeeEmails)) {
            $userFound = true;
        }
        
        if (!$userFound) {
            $validationResults['missing_users'][] = "Row $rowNumber: User '{$row['current_user']}' not found";
            $validationResults['warnings'][] = "Row $rowNumber: Asset will be unassigned";
        }
    }
    
    // Validate dates
    if (!empty($row['purchase_date'])) {
        $date = strtotime($row['purchase_date']);
        if (!$date) {
            $validationResults['warnings'][] = "Row $rowNumber: Invalid Purchase Date format";
        }
    }
    
    if (!empty($row['warranty_expiry'])) {
        $date = strtotime($row['warranty_expiry']);
        if (!$date) {
            $validationResults['warnings'][] = "Row $rowNumber: Invalid Warranty Expiry format";
        }
    }
    
    if ($rowValid) {
        $validationResults['valid_rows']++;
    } else {
        $validationResults['invalid_rows']++;
    }
}

$validationResults['total_rows'] = count($importData);
$validationResults['success_rate'] = $validationResults['total_rows'] > 0 
    ? round(($validationResults['valid_rows'] / $validationResults['total_rows']) * 100, 1) 
    : 0;

echo json_encode($validationResults);
?>