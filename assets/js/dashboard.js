/**
 * Dashboard JavaScript
 * Sistem Kehadiran ISN
 */

class Dashboard {
    constructor() {
        this.clockInBtn = document.getElementById('clockInBtn');
        this.clockOutBtn = document.getElementById('clockOutBtn');
        this.locationCheckBtn = document.getElementById('locationCheckBtn');
        this.attendanceModal = document.getElementById('attendanceModal');
        this.modalTitle = document.getElementById('modalTitle');
        this.modalContent = document.getElementById('modalContent');
        
        this.init();
    }
    
    init() {
        if (this.clockInBtn) {
            this.clockInBtn.addEventListener('click', () => this.handleClockIn());
        }
        
        if (this.clockOutBtn) {
            this.clockOutBtn.addEventListener('click', () => this.handleClockOut());
        }
        
        if (this.locationCheckBtn) {
            this.locationCheckBtn.addEventListener('click', () => this.checkLocation());
        }
        
        // Close modal when clicking on X
        const closeBtn = document.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeModal());
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === this.attendanceModal) {
                this.closeModal();
            }
        });
    }
    
    async handleClockIn() {
        try {
            // Check location first
            if (!await this.checkLocation()) {
                this.showMessage('Anda mesti berada dalam lingkungan 150 meter dari ISN untuk Clock-in', 'error');
                return;
            }
            
            // Show confirmation modal
            this.showClockInModal();
            
        } catch (error) {
            console.error('Error handling clock in:', error);
            this.showMessage('Ralat semasa memproses Clock-in', 'error');
        }
    }
    
    async handleClockOut() {
        try {
            // Check location first
            if (!await this.checkLocation()) {
                this.showMessage('Anda mesti berada dalam lingkungan 150 meter dari ISN untuk Clock-out', 'error');
                return;
            }
            
            // Show confirmation modal
            this.showClockOutModal();
            
        } catch (error) {
            console.error('Error handling clock out:', error);
            this.showMessage('Ralat semasa memproses Clock-out', 'error');
        }
    }
    
    async checkLocation() {
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
                    
                    if (isWithinRange) {
                        this.showMessage(`Lokasi sah! Anda berada ${distance.toFixed(0)} meter dari ISN`, 'success');
                    } else {
                        this.showMessage(`Lokasi tidak sah! Anda berada ${distance.toFixed(0)} meter dari ISN (maksimum 150m)`, 'error');
                    }
                    
                    resolve(isWithinRange);
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    this.showMessage('Tidak dapat mengesan lokasi anda', 'error');
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
    
    showClockInModal() {
        this.modalTitle.textContent = 'Clock In';
        this.modalContent.innerHTML = `
            <div class="clock-confirmation">
                <h4>Pengesahan Clock In</h4>
                <p>Adakah anda pasti mahu Clock In sekarang?</p>
                <div class="current-time">
                    <strong>Masa Semasa:</strong> ${new Date().toLocaleTimeString('ms-MY')}
                </div>
                <div class="location-info">
                    <strong>Lokasi:</strong> <span id="currentLocation">Mengesan...</span>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-success" id="confirmClockIn">
                        <i class="fas fa-check"></i> Ya, Clock In
                    </button>
                    <button class="btn btn-secondary" onclick="dashboard.closeModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </div>
        `;
        
        this.attendanceModal.style.display = 'block';
        
        // Get current location for display
        this.getCurrentLocationForDisplay();
        
        // Add event listener for confirm button
        document.getElementById('confirmClockIn').addEventListener('click', () => this.confirmClockIn());
    }
    
    showClockOutModal() {
        this.modalTitle.textContent = 'Clock Out';
        this.modalContent.innerHTML = `
            <div class="clock-confirmation">
                <h4>Pengesahan Clock Out</h4>
                <p>Adakah anda pasti mahu Clock Out sekarang?</p>
                <div class="current-time">
                    <strong>Masa Semasa:</strong> ${new Date().toLocaleTimeString('ms-MY')}
                </div>
                <div class="location-info">
                    <strong>Lokasi:</strong> <span id="currentLocation">Mengesan...</span>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-warning" id="confirmClockOut">
                        <i class="fas fa-check"></i> Ya, Clock Out
                    </button>
                    <button class="btn btn-secondary" onclick="dashboard.closeModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </div>
        `;
        
        this.attendanceModal.style.display = 'block';
        
        // Get current location for display
        this.getCurrentLocationForDisplay();
        
        // Add event listener for confirm button
        document.getElementById('confirmClockOut').addEventListener('click', () => this.confirmClockOut());
    }
    
    async getCurrentLocationForDisplay() {
        const locationSpan = document.getElementById('currentLocation');
        if (!locationSpan) return;
        
        try {
            const position = await this.getCurrentPosition();
            const distance = this.calculateDistance(
                position.coords.latitude,
                position.coords.longitude,
                3.1390, // ISN Latitude
                101.6869 // ISN Longitude
            );
            
            locationSpan.innerHTML = `
                ${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}
                <br><small>(${distance.toFixed(0)}m dari ISN)</small>
            `;
            
        } catch (error) {
            locationSpan.textContent = 'Tidak dapat mengesan lokasi';
        }
    }
    
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }
            
            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            });
        });
    }
    
    async confirmClockIn() {
        try {
            const position = await this.getCurrentPosition();
            const deviceInfo = this.getDeviceInfo();
            
            const response = await fetch('../includes/clock_in.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    device_info: deviceInfo
                })
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.showMessage('Clock In berjaya!', 'success');
                    this.closeModal();
                    // Reload page to update status
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showMessage(result.message || 'Clock In gagal', 'error');
                }
            } else {
                throw new Error('Network error');
            }
            
        } catch (error) {
            console.error('Error during clock in:', error);
            this.showMessage('Ralat semasa Clock In', 'error');
        }
    }
    
    async confirmClockOut() {
        try {
            const position = await this.getCurrentPosition();
            const deviceInfo = this.getDeviceInfo();
            
            const response = await fetch('../includes/clock_out.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    device_info: deviceInfo
                })
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.showMessage('Clock Out berjaya!', 'success');
                    this.closeModal();
                    // Reload page to update status
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showMessage(result.message || 'Clock Out gagal', 'error');
                }
            } else {
                throw new Error('Network error');
            }
            
        } catch (error) {
            console.error('Error during clock out:', error);
            this.showMessage('Ralat semasa Clock Out', 'error');
        }
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
    
    closeModal() {
        this.attendanceModal.style.display = 'none';
    }
    
    showMessage(message, type) {
        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;
        
        // Insert at top of dashboard
        const dashboard = document.querySelector('.dashboard');
        dashboard.insertBefore(messageDiv, dashboard.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
}

// Initialize dashboard when page loads
let dashboard;
document.addEventListener('DOMContentLoaded', () => {
    dashboard = new Dashboard();
}); 