<?php
$pageTitle = 'Employees Management';
require_once 'config/database.php';
require_once 'models/models.php';

$employeeModel = new Employee();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_POST) {
    $data = [
        'name' => trim($_POST['name']),
        'department' => trim($_POST['department']),
        'company' => trim($_POST['company']),
        'email' => trim($_POST['email'])
    ];
    
    if ($action == 'create') {
        if ($employeeModel->create($data)) {
            header('Location: employees.php?success=created');
            exit;
        } else {
            $error = 'Failed to create employee.';
        }
    } elseif ($action == 'edit' && $id) {
        if ($employeeModel->update($id, $data)) {
            header('Location: employees.php?success=updated');
            exit;
        } else {
            $error = 'Failed to update employee.';
        }
    }
}

// Handle delete
if ($action == 'delete' && $id && $_POST) {
    if ($employeeModel->delete($id)) {
        header('Location: employees.php?success=deleted');
        exit;
    } else {
        header('Location: employees.php?error=delete_failed');
        exit;
    }
}

// Get data for forms and display
$employee = null;
if (($action == 'edit' || $action == 'view') && $id) {
    $employee = $employeeModel->getById($id);
    if (!$employee) {
        header('Location: employees.php?error=not_found');
        exit;
    }
}

ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-people me-2"></i>
        <?php 
        switch($action) {
            case 'create': echo 'Add New Employee'; break;
            case 'edit': echo 'Edit Employee'; break;
            case 'view': echo 'Employee Details'; break;
            default: echo 'Employees Management';
        }
        ?>
    </h1>
    <?php if ($action == 'list'): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="employees.php?action=create" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle"></i> Add Employee
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
    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <label for="search" class="form-label">Search Employees</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Name, email, or department..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
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

    <!-- Employees List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Company</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $searchTerm = $_GET['search'] ?? '';
                        $employees = $employeeModel->getAll($searchTerm);
                        
                        foreach($employees as $emp):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($emp['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($emp['department']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($emp['company']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($emp['created_at'])); ?></td>
                            <td class="table-actions">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="employees.php?action=view&id=<?php echo $emp['id']; ?>" 
                                       class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="employees.php?action=edit&id=<?php echo $emp['id']; ?>" 
                                       class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteEmployee(<?php echo $emp['id']; ?>)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($employees)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-person-x display-1"></i><br>
                                No employees found
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action == 'create' || $action == 'edit'): ?>
    <!-- Employee Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($employee['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" 
                                   value="<?php echo htmlspecialchars($employee['department'] ?? ''); ?>" 
                                   placeholder="e.g., IT, Marketing, Finance">
                        </div>
                        
                        <div class="mb-3">
                            <label for="company" class="form-label">Company</label>
                            <input type="text" class="form-control" id="company" name="company" 
                                   value="<?php echo htmlspecialchars($employee['company'] ?? ''); ?>" 
                                   placeholder="e.g., TechCorp">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="employees.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> <?php echo $action == 'create' ? 'Create Employee' : 'Update Employee'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($action == 'view'): ?>
    <!-- Employee View -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Employee Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Full Name:</td>
                            <td><?php echo htmlspecialchars($employee['name']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Email:</td>
                            <td><?php echo htmlspecialchars($employee['email'] ?: 'Not provided'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Department:</td>
                            <td>
                                <?php if ($employee['department']): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($employee['department']); ?></span>
                                <?php else: ?>
                                    Not specified
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Company:</td>
                            <td><?php echo htmlspecialchars($employee['company'] ?: 'Not specified'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Created:</td>
                            <td><?php echo date('M j, Y g:i A', strtotime($employee['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Last Updated:</td>
                            <td><?php echo date('M j, Y g:i A', strtotime($employee['updated_at'])); ?></td>
                        </tr>
                    </table>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="employees.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                        <div>
                            <a href="employees.php?action=edit&id=<?php echo $employee['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Edit Employee
                            </a>
                            <button type="button" class="btn btn-danger" onclick="deleteEmployee(<?php echo $employee['id']; ?>)">
                                <i class="bi bi-trash"></i> Delete Employee
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Employee's Assets -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Assigned Assets</h6>
                </div>
                <div class="card-body">
                    <?php
                    require_once 'models/models.php';
                    $assetModel = new Asset();
                    $db = getDB();
                    
                    // Get current assets
                    $stmt = $db->prepare("SELECT * FROM assets WHERE current_user_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$employee['id']]);
                    $currentAssets = $stmt->fetchAll();
                    
                    // Get previous assets
                    $stmt = $db->prepare("SELECT * FROM assets WHERE previous_user_id = ? ORDER BY created_at DESC LIMIT 5");
                    $stmt->execute([$employee['id']]);
                    $previousAssets = $stmt->fetchAll();
                    ?>
                    
                    <h6 class="text-success">Current Assets (<?php echo count($currentAssets); ?>)</h6>
                    <?php if ($currentAssets): ?>
                        <div class="list-group mb-3">
                            <?php foreach ($currentAssets as $asset): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($asset['serial_number']); ?></h6>
                                    <small class="text-success">Active</small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($asset['model']); ?></p>
                                <small><?php echo htmlspecialchars($asset['device_type']); ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No current assets assigned.</p>
                    <?php endif; ?>
                    
                    <?php if ($previousAssets): ?>
                        <h6 class="text-warning">Previous Assets</h6>
                        <div class="list-group">
                            <?php foreach ($previousAssets as $asset): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($asset['serial_number']); ?></h6>
                                    <small class="text-muted">Previous</small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($asset['model']); ?></p>
                                <small><?php echo htmlspecialchars($asset['device_type']); ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
                Are you sure you want to delete this employee? This action cannot be undone.
                <br><br>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Note:</strong> You cannot delete an employee who is currently assigned to assets or has been assigned to assets in the past.
                </div>
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
function deleteEmployee(id) {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('deleteForm').action = 'employees.php?action=delete&id=' + id;
    deleteModal.show();
}
</script>
";

require_once 'includes/layout.php';
?>