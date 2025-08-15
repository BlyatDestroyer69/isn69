<?php
/**
 * Main Entry Point - Sistem Kehadiran ISN
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Check if user is already logged in
if (isset($_SESSION['employee_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ic_number = trim($_POST['ic_number'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    
    if (!empty($ic_number) && !empty($employee_id)) {
        // Redirect to face scan verification
        $_SESSION['pending_verification'] = [
            'ic_number' => $ic_number,
            'employee_id' => $employee_id,
            'timestamp' => time()
        ];
        header('Location: pages/face_verification.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <i class="fas fa-building"></i>
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            
            <div class="login-form">
                <h2>Log Masuk Sistem</h2>
                <p class="subtitle">Sila masukkan maklumat pengesahan anda</p>
                
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="ic_number">
                            <i class="fas fa-id-card"></i>
                            Nombor IC
                        </label>
                        <input type="text" 
                               id="ic_number" 
                               name="ic_number" 
                               placeholder="Contoh: 800101-01-1234"
                               pattern="[0-9]{6}-[0-9]{2}-[0-9]{4}"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="employee_id">
                            <i class="fas fa-user-tie"></i>
                            ID Pekerja
                        </label>
                        <input type="text" 
                               id="employee_id" 
                               name="employee_id" 
                               placeholder="Contoh: EMP001"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-camera"></i>
                            Teruskan ke Face Scan
                        </button>
                    </div>
                </form>
                
                <div class="info-box">
                    <h3><i class="fas fa-info-circle"></i> Maklumat Sistem</h3>
                    <ul>
                        <li>Pengesahan 3 lapisan: IC, ID, dan Face Scan</li>
                        <li>Hanya boleh clock-in/out dalam lingkungan 150m dari ISN</li>
                        <li>Data terus dihantar ke SPSM secara automatik</li>
                        <li>MAC Address direkodkan untuk keselamatan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/login.js"></script>
</body>
</html> 