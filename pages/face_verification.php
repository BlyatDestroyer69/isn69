<?php
/**
 * Face Verification Page
 * Sistem Kehadiran ISN
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Check if user has pending verification
if (!isset($_SESSION['pending_verification']) || 
    (time() - $_SESSION['pending_verification']['timestamp']) > 300) { // 5 minutes timeout
    header('Location: ../index.php');
    exit();
}

$pending_verification = $_SESSION['pending_verification'];

// Verify employee credentials
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM employees WHERE ic_number = ? AND employee_id = ? AND is_active = 1");
$stmt->execute([$pending_verification['ic_number'], $pending_verification['employee_id']]);
$employee = $stmt->fetch();

if (!$employee) {
    unset($_SESSION['pending_verification']);
    header('Location: ../index.php?error=invalid_credentials');
    exit();
}

// Check if employee is already clocked in today
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND DATE(clock_in) = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
$stmt->execute([$employee['id'], $today]);
$current_attendance = $stmt->fetch();

$db->closeConnection();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Verification - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="face-scan-container">
            <div class="logo">
                <i class="fas fa-user-check"></i>
                <h1>Face Verification</h1>
                <p>Pengesahan Wajah</p>
            </div>
            
            <div class="scan-instructions">
                <h3><i class="fas fa-info-circle"></i> Arahan Face Scan</h3>
                <ul>
                    <li>Pastikan wajah anda berada dalam rangka kamera</li>
                    <li>Pastikan pencahayaan mencukupi</li>
                    <li>Jangan bergerak semasa pengimbasan</li>
                    <li>Pastikan tiada objek menghalang wajah</li>
                </ul>
            </div>
            
            <div class="camera-container">
                <video id="video" autoplay muted></video>
                <div class="camera-overlay"></div>
            </div>
            
            <div class="employee-info">
                <h3>Maklumat Pekerja</h3>
                <p><strong>Nama:</strong> <?php echo htmlspecialchars($employee['full_name']); ?></p>
                <p><strong>ID:</strong> <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                <p><strong>Jabatan:</strong> <?php echo htmlspecialchars($employee['department']); ?></p>
            </div>
            
            <div class="verification-controls">
                <button id="startScan" class="btn btn-primary">
                    <i class="fas fa-camera"></i>
                    Mula Face Scan
                </button>
                <button id="retryScan" class="btn btn-warning" style="display: none;">
                    <i class="fas fa-redo"></i>
                    Cuba Lagi
                </button>
            </div>
            
            <div id="scanStatus" class="message" style="display: none;"></div>
            
            <div class="geo-warning" id="geoWarning" style="display: none;">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Lokasi Tidak Sah</h3>
                <p>Anda mesti berada dalam lingkungan 150 meter dari ISN untuk menggunakan sistem ini.</p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/face-verification.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.employeeData = {
            id: <?php echo $employee['id']; ?>,
            ic_number: '<?php echo $employee['ic_number']; ?>',
            employee_id: '<?php echo $employee['employee_id']; ?>',
            full_name: '<?php echo addslashes($employee['full_name']); ?>',
            is_clocked_in: <?php echo $current_attendance ? 'true' : 'false'; ?>,
            current_attendance_id: <?php echo $current_attendance ? $current_attendance['id'] : 'null'; ?>
        };
    </script>
</body>
</html> 