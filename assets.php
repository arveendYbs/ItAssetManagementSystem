<?php
$pageTitle = 'Assets Management';
require_once 'config/database.php';
require_once 'models/models.php';

$assetModel = new Asset();
$employeeModel = new Employee();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_POST) {
    $data = [
        'serial_number' => trim($_POST['serial_number']),
        'model' => trim($_POST['model']),
        'device_type' => $_POST['device_type'],
        'site' => trim($_POST['site']),
        'purchased_by' => trim($_POST['purchased_by']),
        'current_user_id' => !empty($_POST['current_user_id']) ? $_POST['current_user_id'] : null,
        'previous_user_id' => !empty($_POST['previous_user_id']) ? $_POST['previous_user_id'] : null,
        'license' => trim($_POST['license']),
        'status' => $_POST['status'],
        'ram' => trim($_POST['ram']),
        'os' => trim($_POST['os']),
        'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
        'warranty_expiry' => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
        'notes' => trim($_POST['notes'])
    ];
    
    if ($action == 'create') {
        if ($assetModel->create($data)) {
            header('Location: assets.php?success=created');
            exit;
        } else {
            $error = 'Failed to create asset.';
        }
    } elseif ($action == 'edit' && $id) {
        if ($assetModel->update($id, $data)) {
            header('Location: assets.php?success=updated');
            exit;
        } else {
            $error = 'Failed to update asset.';
        }
    }
}

// Handle delete
if ($action == 'delete' && $id && $_POST) {
    if ($assetModel->delete($id)) {
        header('Location: assets.php?success=deleted');
        exit;
    } else {
        header('Location: assets.php?error=delete_failed');
        exit;
    }
}

// Get data for forms and display
$employees = $employeeModel->getAll();
$asset = null;
if (($action == 'edit' || $action == 'view') && $id) {
    $asset = $assetModel->getById($id);
    if (!$asset) {
        header('Location: assets.php?error=not_found');
        exit;
    }
}

ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-laptop me-2"></i>
        <?php 
        switch($action) {
            case 'create': echo 'Add New Asset'; break;
            case 'edit': echo 'Edit Asset'; break;
            case 'view': echo 'Asset Details'; break;
            default: echo 'Assets Management';
        }
        ?>
    </h1>
    <?php if ($action == 'list'): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="assets.php?action=create" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle"></i> Add Asset
            </a>
            <a href="reports.php?export=assets" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Serial number, model, user..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="device_type" class="form-label">Device Type</label>
                    <select class="form-select" id="device_type" name="device_type">
                        <option value="">All Types</option>
                        <option value="Laptop" <?php echo ($_GET['device_type'] ?? '') == 'Laptop' ? 'selected' : ''; ?>>Laptop</option>
                        <option value="Desktop" <?php echo ($_GET['device_type'] ?? '') == 'Desktop' ? 'selected' : ''; ?>>Desktop</option>
                        <option value="Monitor" <?php echo ($_GET['device_type'] ?? '') == 'Monitor' ? 'selected' : ''; ?>>Monitor</option>
                        <option value="Projector" <?php echo ($_GET['device_type'] ?? '') == 'Projector' ? 'selected' : ''; ?>>Projector</option>
                        <option value="Tablet" <?php echo ($_GET['device_type'] ?? '') == 'Tablet' ? 'selected' : ''; ?>>Tablet</option>
                        <option value="Phone" <?php echo ($_GET['device_type'] ?? '') == 'Phone' ? 'selected' : ''; ?>>Phone</option>
                        <option value="Server" <?php echo ($_GET['device_type'] ?? '') == 'Server' ? 'selected' : ''; ?>>Server</option>
                        <option value="Printer" <?php echo ($_GET['device_type'] ?? '') == 'Printer' ? 'selected' : ''; ?>>Printer</option>
                        <option value="Other" <?php echo ($_GET['device_type'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo ($_GET['status'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Spare" <?php echo ($_GET['status'] ?? '') == 'Spare' ? 'selected' : ''; ?>>Spare</option>
                        <option value="Retired" <?php echo ($_GET['status'] ?? '') == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                        <option value="Maintenance" <?php echo ($_GET['status'] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="Lost" <?php echo ($_GET['status'] ?? '') == 'Lost' ? 'selected' : ''; ?>>Lost</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assets List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Serial Number</th>
                            <th>Model</th>
                            <th>Device Type</th>
                            <th>Site</th>
                            <th>Current User</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $searchTerm = $_GET['search'] ?? '';
                        $filters = [
                            'device_type' => $_GET['device_type'] ?? '',
                            'status' => $_GET['status'] ?? ''
                        ];
                        $assets = $assetModel->getAll($searchTerm, $filters);
                        
                        foreach($assets as $asset_item):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($asset_item['serial_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($asset_item['model']); ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($asset_item['device_type']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($asset_item['site']); ?></td>
                            <td><?php echo htmlspecialchars($asset_item['current_user_name'] ?: 'Unassigned'); ?></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'Active' => 'success',
                                    'Spare' => 'info', 
                                    'Retired' => 'warning',
                                    'Maintenance' => 'danger',
                                    'Lost' => 'dark'
                                ];
                                $color = $statusColors[$asset_item['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?> status-badge">
                                    <?php echo htmlspecialchars($asset_item['status']); ?>
                                </span>
                            </td>
                            <td class="table-actions">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="assets.php?action=view&id=<?php echo $asset_item['id']; ?>" 
                                       class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="assets.php?action=edit&id=<?php echo $asset_item['id']; ?>" 
                                       class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteAsset(<?php echo $asset_item['id']; ?>)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($assets)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-1"></i><br>
                                No assets found
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action == 'create' || $action == 'edit'): ?>
    <!-- Asset Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="serial_number" class="form-label">Serial Number *</label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                   value="<?php echo htmlspecialchars($asset['serial_number'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model" 
                                   value="<?php echo htmlspecialchars($asset['model'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="device_type" class="form-label">Device Type *</label>
                            <select class="form-select" id="device_type" name="device_type" required>
                                <option value="">Select Device Type</option>
                                <option value="Laptop" <?php echo ($asset['device_type'] ?? '') == 'Laptop' ? 'selected' : ''; ?>>Laptop</option>
                                <option value="Desktop" <?php echo ($asset['device_type'] ?? '') == 'Desktop' ? 'selected' : ''; ?>>Desktop</option>
                                <option value="Monitor" <?php echo ($asset['device_type'] ?? '') == 'Monitor' ? 'selected' : ''; ?>>Monitor</option>
                                <option value="Projector" <?php echo ($asset['device_type'] ?? '') == 'Projector' ? 'selected' : ''; ?>>Projector</option>
                                <option value="Tablet" <?php echo ($asset['device_type'] ?? '') == 'Tablet' ? 'selected' : ''; ?>>Tablet</option>
                                <option value="Phone" <?php echo ($asset['device_type'] ?? '') == 'Phone' ? 'selected' : ''; ?>>Phone</option>
                                <option value="Server" <?php echo ($asset['device_type'] ?? '') == 'Server' ? 'selected' : ''; ?>>Server</option>
                                <option value="Printer" <?php echo ($asset['device_type'] ?? '') == 'Printer' ? 'selected' : ''; ?>>Printer</option>
                                <option value="Other" <?php echo ($asset['device_type'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site" class="form-label">Site</label>
                            <input type="text" class="form-control" id="site" name="site" 
                                   value="<?php echo htmlspecialchars($asset['site'] ?? ''); ?>" 
                                   placeholder="e.g., HQ, HQ2, Branch Office">
                        </div>
                        
                        <div class="mb-3">
                            <label for="purchased_by" class="form-label">Purchased By</label>
                            <input type="text" class="form-control" id="purchased_by" name="purchased_by" 
                                   value="<?php echo htmlspecialchars($asset['purchased_by'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Active" <?php echo ($asset['status'] ?? 'Active') == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Spare" <?php echo ($asset['status'] ?? '') == 'Spare' ? 'selected' : ''; ?>>Spare</option>
                                <option value="Retired" <?php echo ($asset['status'] ?? '') == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                <option value="Maintenance" <?php echo ($asset['status'] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="Lost" <?php echo ($asset['status'] ?? '') == 'Lost' ? 'selected' : ''; ?>>Lost</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="purchase_date" class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                   value="<?php echo htmlspecialchars($asset['purchase_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="current_user_id" class="form-label">Current User</label>
                            <select class="form-select" id="current_user_id" name="current_user_id">
                                <option value="">Unassigned</option>
                                <?php foreach($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>" 
                                        <?php echo ($asset['current_user_id'] ?? '') == $employee['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employee['name'] . ' (' . $employee['department'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="previous_user_id" class="form-label">Previous User</label>
                            <select class="form-select" id="previous_user_id" name="previous_user_id">
                                <option value="">None</option>
                                <?php foreach($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>" 
                                        <?php echo ($asset['previous_user_id'] ?? '') == $employee['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employee['name'] . ' (' . $employee['department'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="license" class="form-label">License</label>
                            <input type="text" class="form-control" id="license" name="license" 
                                   value="<?php echo htmlspecialchars($asset['license'] ?? ''); ?>" 
                                   placeholder="Software license key">
                        </div>
                        
                        <div class="mb-3">
                            <label for="ram" class="form-label">RAM</label>
                            <input type="text" class="form-control" id="ram" name="ram" 
                                   value="<?php echo htmlspecialchars($asset['ram'] ?? ''); ?>" 
                                   placeholder="e.g., 16GB, 32GB">
                        </div>
                        
                        <div class="mb-3">
                            <label for="os" class="form-label">Operating System</label>
                            <input type="text" class="form-control" id="os" name="os" 
                                   value="<?php echo htmlspecialchars($asset['os'] ?? ''); ?>" 
                                   placeholder="e.g., Windows 11, macOS Monterey">
                        </div>
                        
                        <div class="mb-3">
                            <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                            <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" 
                                   value="<?php echo htmlspecialchars($asset['warranty_expiry'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Additional notes about this asset"><?php echo htmlspecialchars($asset['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="assets.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> <?php echo $action == 'create' ? 'Create Asset' : 'Update Asset'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($action == 'view'): ?>
    <!-- Asset View -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Asset Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Serial Number:</td>
                            <td><?php echo htmlspecialchars($asset['serial_number']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Model:</td>
                            <td><?php echo htmlspecialchars($asset['model']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Device Type:</td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($asset['device_type']); ?></span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Site:</td>
                            <td><?php echo htmlspecialchars($asset['site']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Purchased By:</td>
                            <td><?php echo htmlspecialchars($asset['purchased_by']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Status:</td>
                            <td>
                                <?php
                                $statusColors = [
                                    'Active' => 'success',
                                    'Spare' => 'info', 
                                    'Retired' => 'warning',
                                    'Maintenance' => 'danger',
                                    'Lost' => 'dark'
                                ];
                                $color = $statusColors[$asset['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?>"><?php echo htmlspecialchars($asset['status']); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Purchase Date:</td>
                            <td><?php echo $asset['purchase_date'] ? date('M j, Y', strtotime($asset['purchase_date'])) : 'Not specified'; ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Current User:</td>
                            <td><?php echo htmlspecialchars($asset['current_user_name'] ?: 'Unassigned'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Previous User:</td>
                            <td><?php echo htmlspecialchars($asset['previous_user_name'] ?: 'None'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">License:</td>
                            <td><?php echo htmlspecialchars($asset['license'] ?: 'Not specified'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">RAM:</td>
                            <td><?php echo htmlspecialchars($asset['ram'] ?: 'Not specified'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Operating System:</td>
                            <td><?php echo htmlspecialchars($asset['os'] ?: 'Not specified'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Warranty Expiry:</td>
                            <td><?php echo $asset['warranty_expiry'] ? date('M j, Y', strtotime($asset['warranty_expiry'])) : 'Not specified'; ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Created:</td>
                            <td><?php echo date('M j, Y g:i A', strtotime($asset['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if (!empty($asset['notes'])): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Notes:</h6>
                    <div class="bg-light p-3 rounded">
                        <?php echo nl2br(htmlspecialchars($asset['notes'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="assets.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
                <div>
                    <a href="assets.php?action=edit&id=<?php echo $asset['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit Asset
                    </a>
                    <button type="button" class="btn btn-danger" onclick="deleteAsset(<?php echo $asset['id']; ?>)">
                        <i class="bi bi-trash"></i> Delete Asset
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this asset? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalJs = "
<script>
function deleteAsset(id) {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('deleteForm').action = 'assets.php?action=delete&id=' + id;
    deleteModal.show();
}
</script>
";

require_once 'includes/layout.php';
?>