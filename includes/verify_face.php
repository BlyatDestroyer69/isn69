<?php
/**
 * Face Verification Handler
 * Sistem Kehadiran ISN
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $employee_id = $input['employee_id'] ?? null;
    $confidence = $input['confidence'] ?? 0;
    $location = $input['location'] ?? null;
    $device_info = $input['device_info'] ?? null;
    
    if (!$employee_id) {
        throw new Exception('Employee ID is required');
    }
    
    // Validate confidence score
    if ($confidence < FACE_CONFIDENCE_THRESHOLD) {
        throw new Exception('Face recognition confidence too low');
    }
    
    // Check geolocation if provided
    if ($location) {
        $distance = calculateDistance(
            $location['latitude'],
            $location['longitude'],
            ISN_LATITUDE,
            ISN_LONGITUDE
        );
        
        if ($distance > ALLOWED_RADIUS_METERS) {
            throw new Exception('Location outside allowed range');
        }
    }
    
    // Get employee information
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ? AND is_active = 1");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        throw new Exception('Employee not found or inactive');
    }
    
    // Check if employee is already logged in today
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND DATE(clock_in) = ? AND clock_out IS NULL");
    $stmt->execute([$employee_id, $today]);
    $existing_attendance = $stmt->fetch();
    
    if ($existing_attendance) {
        throw new Exception('Employee already clocked in today');
    }
    
    // Get device MAC address (simulated)
    $mac_address = getDeviceMacAddress();
    
    // Check MAC address blacklist
    $stmt = $conn->prepare("SELECT * FROM mac_address_blacklist WHERE mac_address = ? AND (is_permanent = 1 OR blocked_until > NOW())");
    $stmt->execute([$mac_address]);
    $blacklisted = $stmt->fetch();
    
    if ($blacklisted) {
        throw new Exception('Device is blacklisted');
    }
    
    // Create attendance record
    $stmt = $conn->prepare("
        INSERT INTO attendance (
            employee_id, 
            clock_in, 
            clock_in_location_lat, 
            clock_in_location_lng,
            device_mac_address,
            device_info,
            ip_address,
            user_agent,
            verification_method,
            face_scan_confidence,
            status
        ) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, 'face_scan', ?, 'clocked_in')
    ");
    
    $stmt->execute([
        $employee_id,
        $location['latitude'] ?? null,
        $location['longitude'] ?? null,
        $mac_address,
        json_encode($device_info),
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        $confidence
    ]);
    
    $attendance_id = $conn->lastInsertId();
    
    // Log successful verification
    $stmt = $conn->prepare("
        INSERT INTO login_attempts (ic_number, ip_address, mac_address, success) 
        VALUES (?, ?, ?, 1)
    ");
    $stmt->execute([$employee['ic_number'], $_SERVER['REMOTE_ADDR'] ?? null, $mac_address]);
    
    // Set session
    $_SESSION['employee_id'] = $employee_id;
    $_SESSION['employee_name'] = $employee['full_name'];
    $_SESSION['employee_ic'] = $employee['ic_number'];
    $_SESSION['login_time'] = time();
    
    // Sync to SPSM if enabled
    if (SPSM_ENABLED) {
        syncToSPSM($attendance_id, $employee, 'clock_in');
    }
    
    $db->closeConnection();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Face verification successful',
        'attendance_id' => $attendance_id,
        'employee' => [
            'id' => $employee['id'],
            'name' => $employee['full_name'],
            'employee_id' => $employee['employee_id']
        ]
    ]);
    
} catch (Exception $e) {
    // Log failed attempt
    if (isset($employee_id)) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO login_attempts (ic_number, ip_address, mac_address, success, failure_reason) 
            VALUES (?, ?, ?, 0, ?)
        ");
        $stmt->execute([
            $employee['ic_number'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            getDeviceMacAddress(),
            $e->getMessage()
        ]);
        
        $db->closeConnection();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Calculate distance between two coordinates using Haversine formula
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // Earth's radius in meters
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $R * $c;
    
    return $distance;
}

/**
 * Get device MAC address (simulated)
 * In production, implement proper MAC address detection
 */
function getDeviceMacAddress() {
    // For now, generate a unique identifier based on user agent and IP
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    return hash('md5', $user_agent . $ip);
}

/**
 * Sync attendance data to SPSM
 */
function syncToSPSM($attendance_id, $employee, $action) {
    try {
        $data = [
            'attendance_id' => $attendance_id,
            'employee_ic' => $employee['ic_number'],
            'employee_id' => $employee['employee_id'],
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'api_key' => SPSM_API_KEY
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, SPSM_API_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SPSM_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log SPSM sync
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO spsm_sync_log (attendance_id, sync_status, response_data) 
            VALUES (?, ?, ?)
        ");
        
        $sync_status = ($http_code >= 200 && $http_code < 300) ? 'success' : 'failed';
        $stmt->execute([$attendance_id, $sync_status, $response]);
        
        $db->closeConnection();
        
    } catch (Exception $e) {
        // Log sync error
        error_log("SPSM sync error: " . $e->getMessage());
    }
}
?> 