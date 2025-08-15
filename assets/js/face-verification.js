/**
 * Face Verification JavaScript
 * Sistem Kehadiran ISN
 */

class FaceVerification {
    constructor() {
        this.video = document.getElementById('video');
        this.startScanBtn = document.getElementById('startScan');
        this.retryScanBtn = document.getElementById('retryScan');
        this.scanStatus = document.getElementById('scanStatus');
        this.geoWarning = document.getElementById('geoWarning');
        
        this.stream = null;
        this.isScanning = false;
        this.scanAttempts = 0;
        this.maxScanAttempts = 3;
        
        this.init();
    }
    
    init() {
        this.startScanBtn.addEventListener('click', () => this.startFaceScan());
        this.retryScanBtn.addEventListener('click', () => this.startFaceScan());
        
        // Check geolocation first
        this.checkGeolocation();
    }
    
    async startFaceScan() {
        if (this.isScanning) return;
        
        try {
            // Check geolocation again before starting scan
            if (!await this.checkGeolocation()) {
                this.showGeoWarning();
                return;
            }
            
            this.isScanning = true;
            this.startScanBtn.style.display = 'none';
            this.retryScanBtn.style.display = 'none';
            
            // Start camera
            await this.startCamera();
            
            // Show scanning status
            this.showStatus('Memulakan kamera...', 'info');
            
            // Simulate face recognition process
            setTimeout(() => {
                this.processFaceRecognition();
            }, 2000);
            
        } catch (error) {
            console.error('Error starting face scan:', error);
            this.showStatus('Ralat: Tidak dapat mengakses kamera', 'error');
            this.showRetryButton();
        }
    }
    
    async startCamera() {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                }
            });
            
            this.video.srcObject = this.stream;
            
        } catch (error) {
            throw new Error('Camera access denied: ' + error.message);
        }
    }
    
    async checkGeolocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve(false);
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const distance = this.calculateDistance(
                        position.coords.latitude,
                        position.coords.longitude,
                        3.1390, // ISN Latitude
                        101.6869 // ISN Longitude
                    );
                    
                    const isWithinRange = distance <= 150; // 150 meters
                    resolve(isWithinRange);
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    resolve(false);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        });
    }
    
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Earth's radius in meters
        const dLat = this.toRadians(lat2 - lat1);
        const dLon = this.toRadians(lon2 - lon1);
        
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distance = R * c;
        
        return distance;
    }
    
    toRadians(degrees) {
        return degrees * (Math.PI/180);
    }
    
    showGeoWarning() {
        this.geoWarning.style.display = 'block';
        this.showStatus('Anda mesti berada dalam lingkungan 150 meter dari ISN', 'error');
    }
    
    processFaceRecognition() {
        this.showStatus('Mengimbas wajah...', 'info');
        
        // Simulate face recognition process
        setTimeout(() => {
            this.showStatus('Mengesahkan identiti...', 'info');
            
            setTimeout(() => {
                // Simulate face recognition result
                const confidence = Math.random() * 0.3 + 0.7; // 70-100% confidence
                
                if (confidence >= 0.8) {
                    this.faceRecognitionSuccess(confidence);
                } else {
                    this.faceRecognitionFailed(confidence);
                }
            }, 1500);
            
        }, 2000);
    }
    
    faceRecognitionSuccess(confidence) {
        this.showStatus(`Pengesahan berjaya! (Confidence: ${(confidence * 100).toFixed(1)}%)`, 'success');
        
        // Stop camera
        this.stopCamera();
        
        // Redirect to dashboard after success
        setTimeout(() => {
            this.redirectToDashboard();
        }, 2000);
    }
    
    faceRecognitionFailed(confidence) {
        this.scanAttempts++;
        this.showStatus(`Pengesahan gagal. Confidence: ${(confidence * 100).toFixed(1)}%`, 'error');
        
        if (this.scanAttempts < this.maxScanAttempts) {
            this.showStatus(`Cuba lagi... (${this.maxScanAttempts - this.scanAttempts} percubaan lagi)`, 'warning');
            setTimeout(() => {
                this.showRetryButton();
            }, 2000);
        } else {
            this.showStatus('Pengesahan gagal selepas 3 percubaan. Sila cuba lagi nanti.', 'error');
            this.showRetryButton();
        }
    }
    
    showRetryButton() {
        this.isScanning = false;
        this.retryScanBtn.style.display = 'inline-block';
        this.stopCamera();
    }
    
    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        this.video.srcObject = null;
    }
    
    showStatus(message, type) {
        this.scanStatus.textContent = message;
        this.scanStatus.className = `message ${type}`;
        this.scanStatus.style.display = 'block';
    }
    
    async redirectToDashboard() {
        try {
            // Send verification success to server
            const response = await fetch('../includes/verify_face.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    employee_id: window.employeeData.id,
                    confidence: 0.9, // Simulated confidence
                    location: await this.getCurrentLocation(),
                    device_info: this.getDeviceInfo()
                })
            });
            
            if (response.ok) {
                // Redirect to dashboard
                window.location.href = 'dashboard.php';
            } else {
                throw new Error('Verification failed');
            }
            
        } catch (error) {
            console.error('Error during verification:', error);
            this.showStatus('Ralat semasa pengesahan. Sila cuba lagi.', 'error');
            this.showRetryButton();
        }
    }
    
    async getCurrentLocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve(null);
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                },
                () => resolve(null),
                { timeout: 5000 }
            );
        });
    }
    
    getDeviceInfo() {
        return {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            cookieEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine,
            timestamp: new Date().toISOString()
        };
    }
}

// Initialize face verification when page loads
document.addEventListener('DOMContentLoaded', () => {
    new FaceVerification();
}); 