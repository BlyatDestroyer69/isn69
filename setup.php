<?php
/**
 * Setup Script - Sistem Kehadiran ISN
 * Jalankan fail ini untuk memasang sistem secara automatik
 */

// Check if system is already installed
if (file_exists('config/installed.lock')) {
    die('Sistem sudah dipasang. Sila padam fail installed.lock jika anda mahu memasang semula.');
}

// Display setup header
echo "<!DOCTYPE html>
<html lang='ms'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup - Sistem Kehadiran ISN</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .step { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .success { border-color: #28a745; background: #d4edda; }
        .error { border-color: #dc3545; background: #f8d7da; }
        .warning { border-color: #ffc107; background: #fff3cd; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn:disabled { background: #6c757d; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 Setup Sistem Kehadiran ISN</h1>
        <p>Skrip ini akan memasang sistem kehadiran secara automatik.</p>";

// Step 1: Check PHP version
echo "<div class='step'>";
echo "<h3>Langkah 1: Semak Versi PHP</h3>";

if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "<p class='success'>✅ PHP " . PHP_VERSION . " - Memenuhi keperluan</p>";
} else {
    echo "<p class='error'>❌ PHP " . PHP_VERSION . " - Versi PHP mesti 7.4 atau lebih tinggi</p>";
    die();
}
echo "</div>";

// Step 2: Check required extensions
echo "<div class='step'>";
echo "<h3>Langkah 2: Semak Extensions PHP</h3>";

$required_extensions = ['pdo', 'pdo_mysql', 'json', 'curl', 'mbstring', 'openssl'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✅ $ext - Tersedia</p>";
    } else {
        echo "<p class='error'>❌ $ext - Tidak tersedia</p>";
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    echo "<p class='error'>Extensions yang diperlukan tidak tersedia: " . implode(', ', $missing_extensions) . "</p>";
    die();
}
echo "</div>";

// Step 3: Check directory permissions
echo "<div class='step'>";
echo "<h3>Langkah 3: Semak Kebenaran Direktori</h3>";

$directories = ['config', 'includes', 'pages', 'assets', 'uploads'];
$permission_issues = [];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "<p class='success'>✅ Direktori $dir dicipta</p>";
    }
    
    if (is_writable($dir)) {
        echo "<p class='success'>✅ $dir - Boleh ditulis</p>";
    } else {
        echo "<p class='error'>❌ $dir - Tidak boleh ditulis</p>";
        $permission_issues[] = $dir;
    }
}

if (!empty($permission_issues)) {
    echo "<p class='error'>Sila berikan kebenaran menulis kepada direktori: " . implode(', ', $permission_issues) . "</p>";
    die();
}
echo "</div>";

// Step 4: Database setup
echo "<div class='step'>";
echo "<h3>Langkah 4: Setup Database</h3>";

// Check if database config exists
if (file_exists('config/database.php')) {
    echo "<p class='success'>✅ Fail database.php wujud</p>";
    
    // Try to connect to database
    try {
        require_once 'config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        if ($conn) {
            echo "<p class='success'>✅ Berjaya menyambung ke database</p>";
            
            // Check if tables exist
            $stmt = $conn->query("SHOW TABLES LIKE 'employees'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✅ Jadual database sudah wujud</p>";
            } else {
                echo "<p class='warning'>⚠️ Jadual database tidak wujud. Sila import schema.sql</p>";
            }
            
            $db->closeConnection();
        } else {
            echo "<p class='error'>❌ Gagal menyambung ke database</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Ralat database: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>❌ Fail database.php tidak wujud</p>";
}
echo "</div>";

// Step 5: Create uploads directory
echo "<div class='step'>";
echo "<h3>Langkah 5: Setup Direktori Uploads</h3>";

$uploads_dir = 'uploads';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
    echo "<p class='success'>✅ Direktori uploads dicipta</p>";
} else {
    echo "<p class='success'>✅ Direktori uploads sudah wujud</p>";
}

// Create .htaccess in uploads to prevent direct access to uploaded files
$uploads_htaccess = $uploads_dir . '/.htaccess';
if (!file_exists($uploads_htaccess)) {
    $htaccess_content = "Order Deny,Allow\nDeny from all\n";
    file_put_contents($uploads_htaccess, $htaccess_content);
    echo "<p class='success'>✅ Fail .htaccess dalam uploads dicipta</p>";
}
echo "</div>";

// Step 6: Create logs directory
echo "<div class='step'>";
echo "<h3>Langkah 6: Setup Direktori Logs</h3>";

$logs_dir = 'logs';
if (!is_dir($logs_dir)) {
    mkdir($logs_dir, 0755, true);
    echo "<p class='success'>✅ Direktori logs dicipta</p>";
} else {
    echo "<p class='success'>✅ Direktori logs sudah wujud</p>";
}

// Create .htaccess in logs to prevent direct access
$logs_htaccess = $logs_dir . '/.htaccess';
if (!file_exists($logs_htaccess)) {
    $htaccess_content = "Order Deny,Allow\nDeny from all\n";
    file_put_contents($logs_htaccess, $htaccess_content);
    echo "<p class='success'>✅ Fail .htaccess dalam logs dicipta</p>";
}
echo "</div>";

// Step 7: Final setup
echo "<div class='step'>";
echo "<h3>Langkah 7: Setup Selesai</h3>";

// Create installed lock file
$lock_content = "Sistem Kehadiran ISN telah dipasang pada " . date('Y-m-d H:i:s') . "\nJangan padam fail ini melainkan anda mahu memasang semula sistem.";
file_put_contents('config/installed.lock', $lock_content);

echo "<p class='success'>✅ Fail installed.lock dicipta</p>";
echo "<p class='success'>🎉 Setup sistem selesai!</p>";
echo "</div>";

// Final instructions
echo "<div class='step'>
    <h3>📋 Langkah Seterusnya</h3>
    <ol>
        <li>Import database schema dari <code>database/schema.sql</code></li>
        <li>Konfigurasi koordinat ISN dalam <code>config/config.php</code></li>
        <li>Konfigurasi API key SPSM dalam <code>config/config.php</code></li>
        <li>Akses sistem melalui <code>index.php</code></li>
        <li>Login dengan akaun sample: IC: 800101-01-1234, ID: EMP001</li>
    </ol>
    
    <p><strong>⚠️ Penting:</strong> Padam fail <code>setup.php</code> selepas setup selesai untuk keselamatan.</p>
    
    <a href='index.php' class='btn'>🚀 Mulakan Sistem</a>
</div>";

echo "</div></body></html>";
?> 