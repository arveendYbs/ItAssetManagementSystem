<?php
$pageTitle = 'Dashboard';
require_once 'config/database.php';
require_once 'models/models.php';

$assetModel = new Asset();
$stats = $assetModel->getStats();

ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="assets.php?action=create" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-plus-circle"></i> Add Asset
            </a>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 card-stats">
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
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Active Assets</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?php 
                            $activeCount = 0;
                            foreach($stats['by_status'] as $status) {
                                if($status['status'] == 'Active') {
                                    $activeCount = $status['count'];
                                    break;
                                }
                            }
                            echo number_format($activeCount);
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle text-white-50" style="font-size: 2rem;"></i>
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
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Spare Assets</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?php 
                            $spareCount = 0;
                            foreach($stats['by_status'] as $status) {
                                if($status['status'] == 'Spare') {
                                    $spareCount = $status['count'];
                                    break;
                                }
                            }
                            echo number_format($spareCount);
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-archive text-white-50" style="font-size: 2rem;"></i>
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
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Retired Assets</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?php 
                            $retiredCount = 0;
                            foreach($stats['by_status'] as $status) {
                                if($status['status'] == 'Retired') {
                                    $retiredCount = $status['count'];
                                    break;
                                }
                            }
                            echo number_format($retiredCount);
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-x-circle text-white-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <!-- Device Type Chart -->
    <div class="col-xl-6 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Assets by Device Type</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 300px;">
                    <canvas id="deviceTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status Chart -->
    <div class="col-xl-6 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Assets by Status</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 300px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Assets Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Recent Assets</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Serial Number</th>
                        <th>Model</th>
                        <th>Device Type</th>
                        <th>Current User</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recentAssets = $assetModel->getAll('', []);
                    $recentAssets = array_slice($recentAssets, 0, 10); // Show only last 10
                    foreach($recentAssets as $asset):
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($asset['serial_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($asset['model']); ?></td>
                        <td>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($asset['device_type']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($asset['current_user_name'] ?: 'Unassigned'); ?></td>
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
                            <span class="badge bg-<?php echo $color; ?> status-badge">
                                <?php echo htmlspecialchars($asset['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($asset['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($recentAssets)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No assets found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-center mt-3">
            <a href="assets.php" class="btn btn-primary">View All Assets</a>
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
                '#4e73df',
                '#1cc88a', 
                '#36b9cc',
                '#f6c23e',
                '#e74a3b',
                '#858796',
                '#5a5c69',
                '#6f42c1',
                '#e83e8c'
            ],
            hoverBackgroundColor: [
                '#2e59d9',
                '#17a673',
                '#2c9faf',
                '#f4b619',
                '#e02d1b',
                '#6c757d',
                '#484848',
                '#5a32a3',
                '#d91a72'
            ],
            hoverBorderColor: 'rgba(234, 236, 244, 1)',
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
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
            backgroundColor: [
                '#1cc88a',
                '#36b9cc', 
                '#f6c23e',
                '#e74a3b',
                '#858796'
            ],
            hoverBackgroundColor: [
                '#17a673',
                '#2c9faf',
                '#f4b619', 
                '#e02d1b',
                '#6c757d'
            ],
            hoverBorderColor: 'rgba(234, 236, 244, 1)',
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});
</script>
";

require_once 'includes/layout.php';
?>