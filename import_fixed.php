<?php
/**
 * Fixed Import Script
 * Location: import_fixed.php (root directory)
 * Purpose: Import CSV with BOM handling and proper date parsing
 */

$pageTitle = 'Fixed CSV Import';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'models/models.php';

requireLogin();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$errors = [];
$imported = 0;
$debug = [];

if ($_POST && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $debug[] = "Processing file: " . $file['name'];
    
    if ($file['error'] == 0) {
        // Read file content
        $content = file_get_contents($file['tmp_name']);
        
        // Remove BOM if present
        $bom = pack('H*','EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
        $debug[] = "Removed BOM from file";
        
        // Split into lines
        $lines = explode("\n", $content);
        $lines = array_filter($lines, 'trim'); // Remove empty lines
        
        if (count($lines) > 0) {
            $assetModel = new Asset();
            $employeeModel = new Employee();
            
            // Get employees for lookup
            $employees = $employeeModel->getAll();
            $employeeLookup = [];
            foreach ($employees as $emp) {
                $employeeLookup[strtolower($emp['name'])] = $emp['id'];
                if ($emp['email']) {
                    $employeeLookup[strtolower($emp['email'])] = $emp['id'];
                }
            }
            $debug[] = "Found " . count($employees) . " employees for lookup";
            
            // Parse header
            $headerLine = array_shift($lines);
            $headers = str_getcsv($headerLine);
            $headers = array_map('trim', $headers);
            $debug[] = "Headers: " . implode(', ', $headers);
            
            $rowNum = 1;
            foreach ($lines as $line) {
                $rowNum++;
                $line = trim($line);
                if (empty($line)) continue;
                
                $data = str_getcsv($line);
                $debug[] = "Row $rowNum: " . count($data) . " columns";
                
                // Pad data to match headers
                while (count($data) < count($headers)) {
                    $data[] = '';
                }
                
                if (count($data) >= count($headers)) {
                    $row = array_combine($headers, array_slice($data, 0, count($headers)));
                    
                    try {
                        // Prepare asset data with flexible column matching
                        $assetData = [
                            'serial_number' => trim($row['Serial Number'] ?? ''),
                            'asset_tag' => trim($row['Asset Tag'] ?? ''),
                            'model' => trim($row['Model'] ?? ''),
                            'device_type' => trim($row['Device Type'] ?? 'Other'),
                            'site' => trim($row['Site'] ?? ''),
                            'purchased_by' => trim($row['Purchased By'] ?? ''),
                            'license' => trim($row['License'] ?? ''),
                            'status' => trim($row['Status'] ?? 'Active'),
                            'ram' => trim($row['RAM'] ?? ''),
                            'os' => trim($row['Operating System'] ?? $row['OS'] ?? ''),
                            'notes' => trim($row['Notes'] ?? ''),
                            'current_user_id' => null,
                            'previous_user_id' => null,
                            'purchase_date' => null,
                            'warranty_expiry' => null
                        ];
                        
                        // Handle current user lookup
                        $currentUser = trim($row['Current User'] ?? '');
                        if ($currentUser) {
                            $userKey = strtolower($currentUser);
                            $assetData['current_user_id'] = $employeeLookup[$userKey] ?? null;
                            if ($assetData['current_user_id']) {
                                $debug[] = "Row $rowNum: Matched user '$currentUser'";
                            } else {
                                $debug[] = "Row $rowNum: User '$currentUser' not found";
                            }
                        }
                        
                        // Handle dates with multiple formats
                        $purchaseDate = trim($row['Purchase Date'] ?? '');
                        if ($purchaseDate) {
                            $date = parseDate($purchaseDate);
                            if ($date) {
                                $assetData['purchase_date'] = $date;
                                $debug[] = "Row $rowNum: Purchase date '$purchaseDate' → '$date'";
                            }
                        }
                        
                        $warrantyExpiry = trim($row['Warranty Expiry'] ?? '');
                        if ($warrantyExpiry) {
                            $date = parseDate($warrantyExpiry);
                            if ($date) {
                                $assetData['warranty_expiry'] = $date;
                                $debug[] = "Row $rowNum: Warranty expiry '$warrantyExpiry' → '$date'";
                            }
                        }
                        
                        // Validate and fix data
                        if (empty($assetData['serial_number'])) {
                            $assetData['serial_number'] = 'AUTO-' . uniqid();
                        }
                        
                        // Validate device type
                        $validTypes = ['Laptop', 'Desktop', 'Monitor', 'Projector', 'Tablet', 'Phone', 'Server', 'Printer', 'Mouse', 'Other'];
                        if (!in_array($assetData['device_type'], $validTypes)) {
                            $assetData['device_type'] = 'Other';
                        }
                        
                        // Validate status
                        $validStatuses = ['Active', 'Spare', 'Retired', 'Maintenance', 'Lost'];
                        if (!in_array($assetData['status'], $validStatuses)) {
                            $assetData['status'] = 'Active';
                        }
                        
                        // Generate asset tag if empty
                        if (empty($assetData['asset_tag'])) {
                            $assetData['asset_tag'] = $assetModel->getNextAssetTag($assetData['device_type']);
                        }
                        
                        $debug[] = "Row $rowNum: Creating asset '{$assetData['serial_number']}' with tag '{$assetData['asset_tag']}'";
                        
                        // Create asset
                        if ($assetModel->create($assetData)) {
                            $imported++;
                            $debug[] = "Row $rowNum: ✅ SUCCESS";
                        } else {
                            $errors[] = "Row $rowNum: Failed to create asset '{$assetData['serial_number']}'";
                            $debug[] = "Row $rowNum: ❌ FAILED";
                        }
                        
                    } catch (Exception $e) {
                        $errors[] = "Row $rowNum: Exception - " . $e->getMessage();
                        $debug[] = "Row $rowNum: ❌ EXCEPTION - " . $e->getMessage();
                    }
                }
            }
            
            if ($imported > 0) {
                $message = "🎉 Import completed! Successfully imported $imported assets out of " . (count($lines)) . " rows.";
            } else {
                $errors[] = "No assets were imported. Please check your CSV format and data.";
            }
            
        } else {
            $errors[] = "CSV file appears to be empty.";
        }
    } else {
        $errors[] = "File upload error: " . $file['error'];
    }
}

/**
 * Parse date in multiple formats
 */
function parseDate($dateString) {
    $formats = [
        'd/m/Y',    // 15/01/2023
        'm/d/Y',    // 01/15/2023  
        'Y-m-d',    // 2023-01-15
        'd-m-Y',    // 15-01-2023
        'm-d-Y',    // 01-15-2023
        'd/m/y',    // 15/01/23
        'm/d/y',    // 01/15/23
    ];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateString);
        if ($date && $date->format($format) === $dateString) {
            return $date->format('Y-m-d');
        }
    }
    
    // Try strtotime as fallback
    $timestamp = strtotime($dateString);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return null;
}

ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-upload me-2"></i>Fixed CSV Import</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="assets.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Assets
            </a>
            <button class="btn btn-sm btn-outline-info" onclick="toggleDebug()">
                <i class="bi bi-bug"></i> Show Debug
            </button>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle"></i> <?php echo $message; ?>
    <br><a href="assets.php" class="btn btn-sm btn-primary mt-2">View Imported Assets</a>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <h6><i class="bi bi-exclamation-triangle"></i> Import Errors:</h6>
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Debug Information -->
<div id="debugInfo" class="alert alert-info" style="display: none;">
    <h6><i class="bi bi-bug"></i> Debug Information:</h6>
    <div style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; background: #f8f9fa; padding: 10px; border-radius: 5px;">
        <?php foreach ($debug as $debugMsg): ?>
        <div style="margin-bottom: 2px;"><?php echo htmlspecialchars($debugMsg); ?></div>
        <?php endforeach; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Upload CSV File</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Select CSV File *</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" 
                               accept=".csv,text/csv" required>
                        <div class="form-text">
                            Supports CSV files with BOM encoding and multiple date formats
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Import Assets
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Sample Data Preview -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-eye"></i> Your Template Preview</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Serial Number</th>
                                <th>Asset Tag</th>
                                <th>Model</th>
                                <th>Device Type</th>
                                <th>Current User</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>LP001</td>
                                <td>LT001</td>
                                <td>Dell Latitude 7420</td>
                                <td>Laptop</td>
                                <td>John Smith</td>
                                <td>Active</td>
                            </tr>
                            <tr>
                                <td>MS001</td>
                                <td>MS001</td>
                                <td>Logitech MX Master 3</td>
                                <td>Mouse</td>
                                <td>Jane Doe</td>
                                <td>Active</td>
                            </tr>
                            <tr>
                                <td>MON001</td>
                                <td>MON001</td>
                                <td>Dell 27" 4K Monitor</td>
                                <td>Monitor</td>
                                <td>(unassigned)</td>
                                <td>Spare</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted">
                    ✅ This script handles your exact CSV format including BOM encoding and DD/MM/YYYY dates
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Import Features</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li>✅ <strong>BOM Support</strong> - Handles Excel UTF-8 BOM</li>
                    <li>✅ <strong>Date Formats</strong> - DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD</li>
                    <li>✅ <strong>User Matching</strong> - Finds employees by name</li>
                    <li>✅ <strong>Auto Asset Tags</strong> - Generates if empty</li>
                    <li>✅ <strong>Data Validation</strong> - Fixes invalid values</li>
                    <li>✅ <strong>Error Handling</strong> - Skips bad rows, continues</li>
                </ul>
                
                <hr>
                
                <h6>Quick Test:</h6>
                <p class="small">
                    Upload your <code>asset_import_template.csv</code> file directly - 
                    it should import all 3 sample assets successfully!
                </p>
                
                <a href="download_template.php?type=csv" class="btn btn-sm btn-success">
                    <i class="bi bi-download"></i> Download Fresh Template
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDebug() {
    const debugDiv = document.getElementById('debugInfo');
    if (debugDiv.style.display === 'none') {
        debugDiv.style.display = 'block';
    } else {
        debugDiv.style.display = 'none';
    }
}
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>