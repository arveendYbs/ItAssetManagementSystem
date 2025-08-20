<?php
$pageTitle = 'Reports & Analytics';
require_once 'config/database.php';
require_once 'models/models.php';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'assets') {
    $assetModel = new Asset();
    $assets = $assetModel->getAll();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="assets_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Serial Number', 'Model', 'Device Type', 'Site', 'Purchased By',
        'Current User', 'Previous User', 'License', 'Status', 'RAM', 'OS',
        'Purchase Date', 'Warranty Expiry', 'Notes', 'Created At'
    ]);
    
    // CSV Data
    foreach ($assets as $asset) {
        fputcsv($output, [
            $asset['serial_number'],
            $asset['model'],
            $asset['device_type'],
            $asset['site'],
            $asset['purchased_by'],
            $asset['current_user_name'],
            $asset['previous_user_name'],
            $asset['license'],
            $asset['status'],
            $asset['ram'],
            $asset['os'],
            $asset['purchase_date'],
            $asset['warranty_expiry'],
            $asset['notes'],
            $asset['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}

$assetModel = new Asset();
$employeeModel = new Employee();
$stats = $assetModel->getStats();

// Get additional statistics
$db = getDB();

// Assets by site
$stmt = $db->prepare("SELECT site, COUNT(*) as count FROM assets WHERE site IS NOT NULL AND site != '' GROUP BY site ORDER BY count DESC");
$stmt->execute();
$assetsBySite = $stmt->fetchAll();

// Warranty expiry (next 90 days)
$stmt = $db->prepare("SELECT * FROM assets WHERE warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) ORDER BY warranty_expiry ASC");
$stmt->execute();
$expiringWarranties = $stmt->fetchAll();

// Top employees by asset count
$stmt = $db->prepare("
    SELECT e.name, e.department, COUNT(a.id) as asset_count 
    FROM employees e 
    LEFT JOIN assets a ON e.id = a.current_user_id 
    GROUP BY e.id 
    HAVING asset_count > 0 
    ORDER BY asset_count DESC 
    LIMIT 10
");
$stmt->execute();
$topEmployees = $stmt->fetchAll();

// Assets added in last 30 days
$stmt = $db->prepare("SELECT COUNT(*) as count FROM assets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();
$recentAssets = $stmt->fetch()['count'];

ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-bar-chart me-2"></i>Reports & Analytics</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="reports.php?export=assets" class="btn btn-sm btn-success">
                <i class="bi bi-download"></i> Export Assets CSV
            </a>
        </div>
    </div>
</div>

<!-- Quick Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: white;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total Assets</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['total']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-laptop text-white-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); color: white;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Recent Assets (30 days)</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($recentAssets); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-graph-up text-white-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2" style="background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); color: white;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Expiring Warranties</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo count($expiringWarranties); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle text-white-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2" style="background: linear-gradient(135deg, #36b9cc 0%, #258391 100%); color: white;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total Employees</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?php 
                            $allEmployees = $employeeModel->getAll();
                            echo count($allEmployees);
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people text-white-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Device Type Chart -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Assets by Device Type</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 250px;">
                    <canvas id="deviceTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status Chart -->
    <div class="col-xl-4 col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Assets by Status</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 250px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Site Chart -->
    <div class="col-xl-4 col-lg-3">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Assets by Site</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 250px;">
                    <canvas id="siteChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Tables Row -->
<div class="row">
    <!-- Expiring Warranties -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-warning">Warranties Expiring Soon (90 days)</h6>
            </div>
            <div class="card-body">
                <?php if ($expiringWarranties): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Serial Number</th>
                                <th>Model</th>
                                <th>Expiry Date</th>
                                <th>Days Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiringWarranties as $asset): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($asset['serial_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($asset['model']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($asset['warranty_expiry'])); ?></td>
                                <td>
                                    <?php 
                                    $daysLeft = ceil((strtotime($asset['warranty_expiry']) - time()) / (60 * 60 * 24));
                                    $colorClass = $daysLeft <= 30 ? 'text-danger' : ($daysLeft <= 60 ? 'text-warning' : 'text-info');
                                    ?>
                                    <span class="<?php echo $colorClass; ?>"><?php echo $daysLeft; ?> days</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-3">
                    <i class="bi bi-check-circle display-4"></i><br>
                    No warranties expiring in the next 90 days
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top Employees by Asset Count -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">Top Employees by Asset Count</h6>
            </div>
            <div class="card-body">
                <?php if ($topEmployees): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Assets</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topEmployees as $employee): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($employee['name']); ?></strong></td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($employee['department']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $employee['asset_count']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-3">
                    <i class="bi bi-person-x display-4"></i><br>
                    No employees with assigned assets
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Asset Status Breakdown -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Detailed Asset Status Breakdown</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($stats['by_status'] as $status): ?>
                    <div class="col-md-2 col-sm-4 mb-3">
                        <div class="text-center">
                            <div class="h4 mb-1">
                                <?php
                                $statusColors = [
                                    'Active' => 'success',
                                    'Spare' => 'info', 
                                    'Retired' => 'warning',
                                    'Maintenance' => 'danger',
                                    'Lost' => 'dark'
                                ];
                                $color = $statusColors[$status['status']] ?? 'secondary';
                                ?>
                                <span class="text-<?php echo $color; ?>"><?php echo $status['count']; ?></span>
                            </div>
                            <div class="small text-muted text-uppercase"><?php echo htmlspecialchars($status['status']); ?></div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-<?php echo $color; ?>" 
                                     style="width: <?php echo ($status['count'] / $stats['total']) * 100; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Device Type Breakdown -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Device Type Breakdown</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($stats['by_type'] as $type): ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="h3 mb-2 text-primary"><?php echo $type['count']; ?></div>
                                <div class="h6 mb-0"><?php echo htmlspecialchars($type['device_type']); ?></div>
                                <div class="small text-muted">
                                    <?php echo number_format(($type['count'] / $stats['total']) * 100, 1); ?>% of total
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// JavaScript for charts
$additionalJs = "
<script>
// Device Type Chart
const deviceTypeCtx = document.getElementById('deviceTypeChart').getContext('2d');
const deviceTypeChart = new Chart(deviceTypeCtx, {
    type: 'doughnut',
    data: {
        labels: [" . implode(',', array_map(function($item) { return "'" . addslashes($item['device_type']) . "'"; }, $stats['by_type'])) . "],
        datasets: [{
            data: [" . implode(',', array_column($stats['by_type'], 'count')) . "],
            backgroundColor: [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#5a5c69', '#6f42c1', '#e83e8c'
            ],
            hoverBackgroundColor: [
                '#2e59d9', '#17a673', '#2c9faf', '#f4b619', '#e02d1b',
                '#6c757d', '#484848', '#5a32a3', '#d91a72'
            ],
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                }
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: [" . implode(',', array_map(function($item) { return "'" . addslashes($item['status']) . "'"; }, $stats['by_status'])) . "],
        datasets: [{
            data: [" . implode(',', array_column($stats['by_status'], 'count')) . "],
            backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
            hoverBackgroundColor: ['#17a673', '#2c9faf', '#f4b619', '#e02d1b', '#6c757d'],
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                }
            }
        }
    }
});

// Site Chart
const siteCtx = document.getElementById('siteChart').getContext('2d');
const siteChart = new Chart(siteCtx, {
    type: 'bar',
    data: {
        labels: [" . implode(',', array_map(function($item) { return "'" . addslashes($item['site']) . "'"; }, $assetsBySite)) . "],
        datasets: [{
            label: 'Assets',
            data: [" . implode(',', array_column($assetsBySite, 'count')) . "],
            backgroundColor: '#4e73df',
            hoverBackgroundColor: '#2e59d9',
            borderColor: '#4e73df',
            borderWidth: 1
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
";

require_once 'includes/layout.php';
?>