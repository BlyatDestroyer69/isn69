<?php
/**
 * Clock Out Handler
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
    
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    $accuracy = $input['accuracy'] ?? null;
    $device_info = $input['device_info'] ?? null;
    
    $employee_id = $_SESSION['employee_id'];
    
    // Validate location
    if (!$latitude || !$longitude) {
        throw new Exception('Location coordinates are required');
    }
    
    // Check if location is within allowed range
    $distance = calculateDistance(
        $latitude,
        $longitude,
        ISN_LATITUDE,
        ISN_LONGITUDE
    );
    
    if ($distance > ALLOWED_RADIUS_METERS) {
        throw new Exception('Location outside allowed range. You must be within 150 meters of ISN.');
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
    
    // Check if employee is clocked in today
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND DATE(clock_in) = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
    $stmt->execute([$employee_id, $today]);
    $current_attendance = $stmt->fetch();
    
    if (!$current_attendance) {
        throw new Exception('You are not currently clocked in');
    }
    
    // Get device MAC address
    $mac_address = getDeviceMacAddress();
    
    // Check MAC address blacklist
    $stmt = $conn->prepare("SELECT * FROM mac_address_blacklist WHERE mac_address = ? AND (is_permanent = 1 OR blocked_until > NOW())");
    $stmt->execute([$mac_address]);
    $blacklisted = $stmt->fetch();
    
    if ($blacklisted) {
        throw new Exception('This device is blacklisted');
    }
    
    // Verify device MAC address matches the one used for clock in
    if (MAC_ADDRESS_VALIDATION) {
        if ($current_attendance['device_mac_address'] !== $mac_address) {
            throw new Exception('Clock out must be done from the same device used for clock in');
        }
    }
    
    // Calculate working hours
    $clock_in_time = new DateTime($current_attendance['clock_in']);
    $clock_out_time = new DateTime();
    $working_hours = $clock_in_time->diff($clock_out_time);
    
    // Update attendance record
    $stmt = $conn->prepare("
        UPDATE attendance SET 
            clock_out = NOW(),
            clock_out_location_lat = ?,
            clock_out_location_lng = ?,
            status = 'clocked_out',
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $latitude,
        $longitude,
        $current_attendance['id']
    ]);
    
    // Log successful clock out
    $stmt = $conn->prepare("
        INSERT INTO login_attempts (ic_number, ip_address, mac_address, success) 
        VALUES (?, ?, ?, 1)
    ");
    $stmt->execute([$employee['ic_number'], $_SERVER['REMOTE_ADDR'] ?? null, $mac_address]);
    
    // Sync to SPSM if enabled
    if (SPSM_ENABLED) {
        syncToSPSM($current_attendance['id'], $employee, 'clock_out');
    }
    
    $db->closeConnection();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Clock Out successful',
        'attendance_id' => $current_attendance['id'],
        'clock_in_time' => $current_attendance['clock_in'],
        'clock_out_time' => date('Y-m-d H:i:s'),
        'working_hours' => [
            'hours' => $working_hours->h,
            'minutes' => $working_hours->i,
            'total_minutes' => ($working_hours->h * 60) + $working_hours->i
        ],
        'location' => [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_from_isn' => round($distance, 2)
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
 