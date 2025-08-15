<?php
/**
 * Admin Dashboard
 * Sistem Kehadiran ISN
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../../index.php');
    exit();
}

// Check if user is admin (you can implement proper admin role checking)
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ? AND is_active = 1");
$stmt->execute([$_SESSION['employee_id']]);
$employee = $stmt->fetch();

if (!$employee || $employee['department'] !== 'IT') {
    header('Location: ../dashboard.php');
    exit();
}

// Get system statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total_employees FROM employees WHERE is_active = 1");
$stmt->execute();
$total_employees = $stmt->fetch()['total_employees'];

$stmt = $conn->prepare("SELECT COUNT(*) as today_attendance FROM attendance WHERE DATE(clock_in) = CURDATE()");
$stmt->execute();
$today_attendance = $stmt->fetch()['today_attendance'];

$stmt = $conn->prepare("SELECT COUNT(*) as pending_sync FROM attendance WHERE spsm_sync_status = 'pending'");
$stmt->execute();
$pending_sync = $stmt->fetch()['pending_sync'];

$stmt = $conn->prepare("SELECT COUNT(*) as failed_sync FROM attendance WHERE spsm_sync_status = 'failed'");
$stmt->execute();
$failed_sync = $stmt->fetch()['failed_sync'];

$db->closeConnection();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <h1><i class="fas fa-cogs"></i> Admin Dashboard</h1>
                <div class="user-info">
                    <div class="employee-name"><?php echo htmlspecialchars($employee['full_name']); ?></div>
                    <div class="employee-details">
                        <?php echo htmlspecialchars($employee['employee_id']); ?> | Admin
                    </div>
                </div>
            </div>
            
            <div class="admin-stats">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3>Total Pekerja</h3>
                    <p><?php echo $total_employees; ?></p>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3>Kehadiran Hari Ini</h3>
                    <p><?php echo $today_attendance; ?></p>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-sync"></i>
                    <h3>Pending SPSM</h3>
                    <p><?php echo $pending_sync; ?></p>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Sync Gagal</h3>
                    <p><?php echo $failed_sync; ?></p>
                </div>
            </div>
            
            <div class="admin-controls">
                <h2>Pengurusan Sistem</h2>
                
                <div class="control-buttons">
                    <a href="employees.php" class="btn btn-primary">
                        <i class="fas fa-users"></i>
                        Pengurusan Pekerja
                    </a>
                    
                    <a href="attendance.php" class="btn btn-success">
                        <i class="fas fa-clock"></i>
                        Rekod Kehadiran
                    </a>
                    
                    <a href="spsm_sync.php" class="btn btn-warning">
                        <i class="fas fa-sync"></i>
                        SPSM Sync
                    </a>
                    
                    <a href="reports.php" class="btn btn-secondary">
                        <i class="fas fa-chart-bar"></i>
                        Laporan
                    </a>
                    
                    <a href="settings.php" class="btn btn-info">
                        <i class="fas fa-cog"></i>
                        Tetapan Sistem
                    </a>
                </div>
            </div>
            
            <div class="quick-actions">
                <h2>Tindakan Pantas</h2>
                
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="forceSPSMSync()">
                        <i class="fas fa-sync"></i>
                        Force SPSM Sync
                    </button>
                    
                    <button class="btn btn-warning" onclick="checkSystemHealth()">
                        <i class="fas fa-heartbeat"></i>
                        Semak Kesihatan Sistem
                    </button>
                    
                    <button class="btn btn-info" onclick="backupDatabase()">
                        <i class="fas fa-download"></i>
                        Backup Database
                    </button>
                </div>
            </div>
            
            <div class="navigation-links">
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Dashboard
                </a>
                
                <a href="../../includes/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Log Keluar
                </a>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/admin.js"></script>
</body>
</html> 