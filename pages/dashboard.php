<?php
/**
 * Employee Dashboard
 * Sistem Kehadiran ISN
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get employee information
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$_SESSION['employee_id']]);
$employee = $stmt->fetch();

if (!$employee) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

// Get today's attendance status
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND DATE(clock_in) = ? ORDER BY clock_in DESC LIMIT 1");
$stmt->execute([$employee['id'], $today]);
$todayAttendance = $stmt->fetch();

// Get recent attendance history
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY clock_in DESC LIMIT 10");
$stmt->execute([$employee['id']]);
$recentAttendance = $stmt->fetchAll();

$db->closeConnection();

// Determine current status
$currentStatus = 'clocked_out';
$statusText = 'Belum Clock-in';
$statusClass = 'status-clocked-out';

if ($todayAttendance) {
    if ($todayAttendance['clock_out']) {
        $currentStatus = 'clocked_out';
        $statusText = 'Sudah Clock-out';
        $statusClass = 'status-clocked-out';
    } else {
        $currentStatus = 'clocked_in';
        $statusText = 'Sedang Bekerja';
        $statusClass = 'status-clocked-in';
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="user-info">
                    <div class="employee-name"><?php echo htmlspecialchars($employee['full_name']); ?></div>
                    <div class="employee-details">
                        <?php echo htmlspecialchars($employee['employee_id']); ?> | 
                        <?php echo htmlspecialchars($employee['department']); ?>
                    </div>
                </div>
            </div>
            
            <div class="current-status">
                <h2>Status Semasa</h2>
                <div class="status-indicator <?php echo $statusClass; ?>">
                    <i class="fas fa-circle"></i>
                    <?php echo $statusText; ?>
                </div>
                <p class="status-time">
                    <?php if ($todayAttendance): ?>
                        <?php if ($todayAttendance['clock_in']): ?>
                            Clock-in: <?php echo date('H:i:s', strtotime($todayAttendance['clock_in'])); ?>
                        <?php endif; ?>
                        <?php if ($todayAttendance['clock_out']): ?>
                            | Clock-out: <?php echo date('H:i:s', strtotime($todayAttendance['clock_out'])); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        Belum ada rekod kehadiran hari ini
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="attendance-controls">
                <?php if ($currentStatus === 'clocked_out'): ?>
                    <div class="attendance-btn clock-in-btn" id="clockInBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        <h3>Clock In</h3>
                        <p>Mulakan hari bekerja anda</p>
                    </div>
                <?php else: ?>
                    <div class="attendance-btn clock-out-btn" id="clockOutBtn">
                        <i class="fas fa-sign-out-alt"></i>
                        <h3>Clock Out</h3>
                        <p>Tamatkan hari bekerja anda</p>
                    </div>
                <?php endif; ?>
                
                <div class="attendance-btn" id="locationCheckBtn">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Semak Lokasi</h3>
                    <p>Pastikan anda dalam lingkungan ISN</p>
                </div>
            </div>
            
            <div class="attendance-history">
                <h2>Rekod Kehadiran Terkini</h2>
                <div class="history-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Tarikh</th>
                                <th>Clock In</th>
                                <th>Clock Out</th>
                                <th>Status</th>
                                <th>Lokasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAttendance as $record): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($record['clock_in'])); ?></td>
                                    <td><?php echo date('H:i:s', strtotime($record['clock_in'])); ?></td>
                                    <td>
                                        <?php echo $record['clock_out'] ? date('H:i:s', strtotime($record['clock_out'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <span class="status-indicator <?php echo $record['clock_out'] ? 'status-clocked-out' : 'status-clocked-in'; ?>">
                                            <?php echo $record['clock_out'] ? 'Selesai' : 'Sedang Bekerja'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($record['clock_in_location_lat'] && $record['clock_in_location_lng']): ?>
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo number_format($record['clock_in_location_lat'], 6); ?>, 
                                            <?php echo number_format($record['clock_in_location_lng'], 6); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="logout-section">
                <a href="../includes/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Log Keluar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Clock In/Out Modal -->
    <div id="attendanceModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Clock In/Out</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html> 