<?php
/**
 * Asset Import System
 * Location: import_assets.php (root directory)
 * Purpose: Import assets from Excel/CSV files with field mapping
 */

session_start();


$pageTitle = 'Import Assets';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'models/models.php';


// Require login
requireLogin();


$assetModel = new Asset();
$employeeModel = new Employee();


// Fix step detection - prioritize POST step value first
$step = $_POST['step'] ?? ($_GET['step'] ?? 'upload');
$importData = [];
$errors = [];
$success = [];


// Debug: check what step and request method is being processed
// error_log("Step: $step | Method: " . $_SERVER['REQUEST_METHOD']);


// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'upload') {
if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == 0) {
$file = $_FILES['import_file'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($fileExtension, ['csv', 'xlsx', 'xls'])) {
$errors[] = 'Please upload a CSV, XLSX, or XLS file.';
} else {
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
mkdir($uploadDir, 0777, true);
}
$fileName = uniqid() . '.' . $fileExtension;
$filePath = $uploadDir . $fileName;
if (move_uploaded_file($file['tmp_name'], $filePath)) {
$importData = parseImportFile($filePath, $fileExtension);
if (!empty($importData) && !isset($importData['error'])) {
$step = 'mapping';
$_SESSION['import_data'] = $importData;
$_SESSION['import_file'] = $filePath;
} else {
$errors[] = $importData['error'] ?? 'Could not read the file or file is empty.';
}
} else {
$errors[] = 'Failed to upload file.';
}
}
} else {
$errors[] = 'Please select a file to upload.';
}
}


// Handle field mapping and import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'import') {
$importData = $_SESSION['import_data'] ?? [];
$fieldMapping = $_POST['field_mapping'] ?? [];
$defaultValues = $_POST['default_values'] ?? [];
if (!empty($importData) && !empty($fieldMapping)) {
$importResults = importAssets($importData, $fieldMapping, $defaultValues, $assetModel, $employeeModel);
$success = $importResults['success'];
$errors = $importResults['errors'];
$step = 'results';
} else {
$errors[] = 'No data or mapping found. Please try again.';
}
}

/**
 * Parse CSV/Excel file
 */
function parseImportFile($filePath, $extension) {
    $data = [];
    
    if ($extension == 'csv') {
        // Set auto-detect line endings for better CSV parsing
        ini_set('auto_detect_line_endings', true);
        
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            // Try to detect delimiter
            $firstLine = fgets($handle);
            rewind($handle);
            
            $delimiter = ',';
            if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
                $delimiter = ';';
            }
            
            $headers = fgetcsv($handle, 0, $delimiter);
            if ($headers) {
                // Clean headers - remove BOM and trim
                $headers = array_map(function($header) {
                    return trim(str_replace("\xEF\xBB\xBF", '', $header));
                }, $headers);
                
                $rowCount = 0;
                while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE && $rowCount < 1000) {
                    // Skip empty rows
                    if (count(array_filter($row)) == 0) {
                        continue;
                    }
                    
                    // Pad row to match headers count
                    while (count($row) < count($headers)) {
                        $row[] = '';
                    }
                    
                    if (count($row) >= count($headers)) {
                        $data[] = array_combine($headers, array_slice($row, 0, count($headers)));
                        $rowCount++;
                    }
                }
            }
            fclose($handle);
        } else {
            return ['error' => 'Cannot open CSV file for reading.'];
        }
    } else {
        // For Excel files without PhpSpreadsheet
        return ['error' => 'Excel files are not fully supported yet. Please save your file as CSV format and try again.'];
    }
    
    if (empty($data)) {
        return ['error' => 'No valid data found in file. Make sure the first row contains headers and there is data below.'];
    }
    
    return $data;
}

/**
 * Import assets with field mapping
 */
function importAssets($data, $fieldMapping, $defaultValues, $assetModel, $employeeModel) {
    $success = [];
    $errors = [];
    $employees = $employeeModel->getAll();
    
    // Create employee lookup by name/email
    $employeeLookup = [];
    foreach ($employees as $emp) {
        $employeeLookup[strtolower($emp['name'])] = $emp['id'];
        if ($emp['email']) {
            $employeeLookup[strtolower($emp['email'])] = $emp['id'];
        }
    }
    
    foreach ($data as $rowIndex => $row) {
        $rowNumber = $rowIndex + 2; // Account for header row
        
        try {
            $assetData = [];
            
            // Map fields from CSV to database fields
            foreach ($fieldMapping as $dbField => $csvField) {
                if ($csvField && isset($row[$csvField])) {
                    $value = trim($row[$csvField]);
                    
                    // Handle special field types
                    switch ($dbField) {
                        case 'current_user_id':
                        case 'previous_user_id':
                            if ($value) {
                                $lookupKey = strtolower($value);
                                $assetData[$dbField] = $employeeLookup[$lookupKey] ?? null;
                            } else {
                                $assetData[$dbField] = null;
                            }
                            break;
                            
                        case 'purchase_date':
                        case 'warranty_expiry':
                            if ($value) {
                                $date = date('Y-m-d', strtotime($value));
                                $assetData[$dbField] = ($date !== '1970-01-01') ? $date : null;
                            } else {
                                $assetData[$dbField] = null;
                            }
                            break;
                            
                        case 'device_type':
                            // Validate device type
                            $validTypes = ['Laptop', 'Desktop', 'Monitor', 'Projector', 'Tablet', 'Phone', 'Server', 'Printer', 'Mouse', 'Other'];
                            if (in_array($value, $validTypes)) {
                                $assetData[$dbField] = $value;
                            } else {
                                $assetData[$dbField] = 'Other';
                            }
                            break;
                            
                        case 'status':
                            // Validate status
                            $validStatuses = ['Active', 'Spare', 'Retired', 'Maintenance', 'Lost'];
                            if (in_array($value, $validStatuses)) {
                                $assetData[$dbField] = $value;
                            } else {
                                $assetData[$dbField] = 'Active';
                            }
                            break;
                            
                        default:
                            $assetData[$dbField] = $value;
                    }
                }
            }
            
            // Apply default values for unmapped fields
            foreach ($defaultValues as $field => $value) {
                if (!isset($assetData[$field]) && $value !== '') {
                    $assetData[$field] = $value;
                }
            }
            
            // Ensure required fields
            if (empty($assetData['serial_number'])) {
                $assetData['serial_number'] = 'AUTO-' . uniqid();
            }
            
            if (empty($assetData['device_type'])) {
                $assetData['device_type'] = 'Other';
            }
            
            if (empty($assetData['status'])) {
                $assetData['status'] = 'Active';
            }
            
            // Generate asset tag if not provided
            if (empty($assetData['asset_tag'])) {
                $assetData['asset_tag'] = $assetModel->getNextAssetTag($assetData['device_type']);
            }
            
            // Attempt to create asset
            if ($assetModel->create($assetData)) {
                $success[] = "Row $rowNumber: Successfully imported asset '{$assetData['serial_number']}'";
            } else {
                $errors[] = "Row $rowNumber: Failed to import asset '{$assetData['serial_number']}'";
            }
            
        } catch (Exception $e) {
            $errors[] = "Row $rowNumber: Error - " . $e->getMessage();
        }
    }
    
    return ['success' => $success, 'errors' => $errors];
}

ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-upload me-2"></i>Import Assets</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="assets.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Assets
            </a>
        </div>
    </div>
</div>

<!-- Progress Steps -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="step <?php echo $step == 'upload' ? 'active' : ($step != 'upload' ? 'completed' : ''); ?>">
                        <span class="step-number">1</span>
                        <span class="step-title">Upload File</span>
                    </div>
                    <div class="step <?php echo $step == 'mapping' ? 'active' : ($step == 'results' ? 'completed' : ''); ?>">
                        <span class="step-number">2</span>
                        <span class="step-title">Map Fields</span>
                    </div>
                    <div class="step <?php echo $step == 'results' ? 'active' : ''; ?>">
                        <span class="step-number">3</span>
                        <span class="step-title">Results</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <h6><i class="bi bi-exclamation-triangle"></i> Errors:</h6>
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success">
    <h6><i class="bi bi-check-circle"></i> Success:</h6>
    <ul class="mb-0">
        <?php foreach ($success as $msg): ?>
        <li><?php echo htmlspecialchars($msg); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($step == 'upload'): ?>
<!-- Step 1: File Upload -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Step 1: Upload Your File</h5>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="import_file" class="form-label">Select File *</label>
                        <input type="file" class="form-control" id="import_file" name="import_file" 
                               accept=".csv,.xlsx,.xls" required>
                        <div class="form-text">Supported formats: CSV, Excel (.xlsx, .xls)</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Upload & Continue
                        </button>
                    </div>
                </div>
            </div>
        </form>
        
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <h6><i class="bi bi-info-circle text-info"></i> File Requirements:</h6>
                <ul>
                    <li>First row should contain column headers</li>
                    <li>Required: Serial Number or Asset Tag</li>
                    <li>Recommended: Device Type, Model, Status</li>
                    <li>Maximum 1000 rows per import</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6><i class="bi bi-download text-success"></i> Sample Templates:</h6>
                <a href="download_template.php?type=csv" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Download CSV Template
                </a>
                <a href="download_template.php?type=excel" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel"></i> Download Excel Template
                </a>
            </div>
        </div>
    </div>
</div>

<?php elseif ($step == 'mapping'): ?>
<!-- Step 2: Field Mapping -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> Step 2: Map Your Fields</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="step" value="import">
            
            <div class="row">
                <div class="col-md-6">
                    <h6>Your File Columns:</h6>
                    <div class="list-group mb-3">
                        <?php 
                        if (!empty($importData)) {
                            $fileColumns = array_keys($importData[0]);
                            foreach ($fileColumns as $column): 
                        ?>
                        <div class="list-group-item">
                            <strong><?php echo htmlspecialchars($column); ?></strong>
                            <br><small class="text-muted">Sample: <?php echo htmlspecialchars($importData[0][$column]); ?></small>
                        </div>
                        <?php endforeach; } ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h6>Map to Database Fields:</h6>
                    
                    <?php
                    $dbFields = [
                        'serial_number' => 'Serial Number *',
                        'asset_tag' => 'Asset Tag',
                        'model' => 'Model',
                        'device_type' => 'Device Type',
                        'site' => 'Site',
                        'purchased_by' => 'Purchased By',
                        'current_user_id' => 'Current User (Name/Email)',
                        'previous_user_id' => 'Previous User (Name/Email)',
                        'license' => 'License',
                        'status' => 'Status',
                        'ram' => 'RAM',
                        'os' => 'Operating System',
                        'purchase_date' => 'Purchase Date',
                        'warranty_expiry' => 'Warranty Expiry',
                        'notes' => 'Notes'
                    ];
                    
                    foreach ($dbFields as $dbField => $label):
                    ?>
                    <div class="mb-2">
                        <label class="form-label small"><?php echo $label; ?></label>
                        <select class="form-select form-select-sm" name="field_mapping[<?php echo $dbField; ?>]">
                            <option value="">-- Skip This Field --</option>
                            <?php 
                            if (!empty($importData)) {
                                foreach (array_keys($importData[0]) as $column): 
                            ?>
                            <option value="<?php echo htmlspecialchars($column); ?>">
                                <?php echo htmlspecialchars($column); ?>
                            </option>
                            <?php endforeach; } ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <hr>
            
            <h6><i class="bi bi-gear"></i> Default Values (for unmapped fields):</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Default Device Type</label>
                        <select class="form-select" name="default_values[device_type]">
                            <option value="">None</option>
                            <option value="Laptop">Laptop</option>
                            <option value="Desktop">Desktop</option>
                            <option value="Monitor">Monitor</option>
                            <option value="Other" selected>Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Default Status</label>
                        <select class="form-select" name="default_values[status]">
                            <option value="">None</option>
                            <option value="Active" selected>Active</option>
                            <option value="Spare">Spare</option>
                            <option value="Retired">Retired</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Default Site</label>
                        <input type="text" class="form-control" name="default_values[site]" placeholder="e.g., HQ">
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="import_assets.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-download"></i> Start Import
                </button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($step == 'results'): ?>
<!-- Step 3: Results -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-check-circle"></i> Step 3: Import Results</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h2><?php echo count($success); ?></h2>
                        <p>Successfully Imported</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h2><?php echo count($errors); ?></h2>
                        <p>Failed Imports</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-center mt-4">
            <a href="assets.php" class="btn btn-primary me-2">
                <i class="bi bi-list"></i> View All Assets
            </a>
            <a href="import_assets.php" class="btn btn-outline-secondary">
                <i class="bi bi-upload"></i> Import More Assets
            </a>
        </div>
    </div>
</div>

<?php 
// Clean up uploaded file
if (isset($_SESSION['import_file']) && file_exists($_SESSION['import_file'])) {
    unlink($_SESSION['import_file']);
    unset($_SESSION['import_file']);
    unset($_SESSION['import_data']);
}
?>

<?php endif; ?>

<?php
$content = ob_get_clean();

$additionalJs = "
<style>
.step {
    text-align: center;
    position: relative;
}

.step-number {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #6c757d;
    color: white;
    line-height: 30px;
    margin-bottom: 10px;
}

.step.active .step-number {
    background-color: #0d6efd;
}

.step.completed .step-number {
    background-color: #198754;
}

.step-title {
    display: block;
    font-size: 14px;
    color: #6c757d;
}

.step.active .step-title {
    color: #0d6efd;
    font-weight: bold;
}
</style>
";

require_once 'includes/layout.php';
?>