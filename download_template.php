<?php
/**
 * Simple CSV Sample Generator
 * Location: create_sample_csv.php (root directory)
 */

require_once 'includes/auth.php';
requireLogin();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sample_assets.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Clean any output buffer
if (ob_get_level()) {
    ob_end_clean();
}

$output = fopen('php://output', 'w');

// Write BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = [
    'Serial Number',
    'Asset Tag',
    'Model', 
    'Device Type',
    'Site',
    'Purchased By',
    'Current User',
    'Status',
    'RAM',
    'Operating System',
    'License',
    'Purchase Date',
    'Warranty Expiry',
    'Notes'
];

fputcsv($output, $headers);

// Sample data
$sampleData = [
    ['LP001', 'LT001', 'Dell Latitude 7420', 'Laptop', 'HQ', 'IT Department', 'John Smith', 'Active', '16GB', 'Windows 11', 'Windows Pro', '2023-01-15', '2026-01-15', 'Development laptop'],
    ['MS001', 'MS001', 'Logitech MX Master 3', 'Mouse', 'HQ', 'IT Department', 'Jane Doe', 'Active', '', '', '', '2023-02-10', '2024-02-10', 'Wireless mouse'],
    ['MON001', 'MON001', 'Dell 27" 4K Monitor', 'Monitor', 'HQ2', 'IT Department', '', 'Spare', '', '', '', '2022-12-01', '2025-12-01', 'Conference room monitor'],
    ['DT001', 'DT001', 'HP EliteDesk 800', 'Desktop', 'HQ', 'IT Department', 'Mike Johnson', 'Active', '32GB', 'Windows 11', 'Windows Pro', '2023-03-05', '2026-03-05', 'Workstation for design'],
    ['PH001', 'PH001', 'iPhone 14', 'Phone', 'HQ', 'IT Department', 'Sarah Wilson', 'Active', '128GB', 'iOS 16', '', '2023-04-10', '2024-04-10', 'Company phone']
];

foreach ($sampleData as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>