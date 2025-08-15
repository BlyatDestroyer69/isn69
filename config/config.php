<?php
/**
 * Main Configuration File
 * Sistem Kehadiran ISN
 */

// ISN Location Coordinates (Latitude, Longitude)
// Ganti dengan koordinat sebenar ISN
define('ISN_LATITUDE', 3.1390);  // Contoh: Kuala Lumpur
define('ISN_LONGITUDE', 101.6869);
define('ALLOWED_RADIUS_METERS', 150); // 150 meter dari ISN

// SPSM Integration Settings
define('SPSM_API_URL', 'https://spsm.gov.my/api/attendance');
define('SPSM_API_KEY', 'your_spsm_api_key_here');
define('SPSM_ENABLED', true);

// System Settings
define('SITE_NAME', 'Sistem Kehadiran ISN');
define('SITE_URL', 'http://localhost/isn');
define('TIMEZONE', 'Asia/Kuala_Lumpur');

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_TIME', 900); // 15 minutes

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png']);
define('UPLOAD_PATH', 'uploads/');

// Face Recognition Settings
define('FACE_RECOGNITION_ENABLED', true);
define('FACE_CONFIDENCE_THRESHOLD', 0.8);
define('FACE_SCAN_TIMEOUT', 30); // seconds

// MAC Address Validation
define('MAC_ADDRESS_VALIDATION', true);
define('ALLOW_MULTIPLE_DEVICES', false);

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?> 