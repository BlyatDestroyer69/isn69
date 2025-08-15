/**
 * Admin Dashboard JavaScript
 * Sistem Kehadiran ISN
 */

class AdminDashboard {
    constructor() {
        this.init();
    }
    
    init() {
        // Initialize admin dashboard functionality
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Add any additional event listeners here
    }
    
    async forceSPSMSync() {
        try {
            const response = await fetch('../../includes/admin/force_spsm_sync.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.showMessage('SPSM sync initiated successfully', 'success');
                    // Reload page to update stats
                    setTimeout(() => location.reload(), 2000);
                } else {
                    this.showMessage(result.message || 'SPSM sync failed', 'error');
                }
            } else {
                throw new Error('Network error');
            }
            
        } catch (error) {
            console.error('Error during SPSM sync:', error);
            this.showMessage('Error during SPSM sync', 'error');
        }
    }
    
    async checkSystemHealth() {
        try {
            const response = await fetch('../../includes/admin/check_system_health.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                this.displaySystemHealth(result);
            } else {
                throw new Error('Network error');
            }
            
        } catch (error) {
            console.error('Error checking system health:', error);
            this.showMessage('Error checking system health', 'error');
        }
    }
    
    displaySystemHealth(healthData) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.style.display = 'block';
        
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>System Health Report</h3>
                    <span class="close" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="health-report">
                        <div class="health-item ${healthData.database.status === 'healthy' ? 'healthy' : 'unhealthy'}">
                            <h4>Database</h4>
                            <p>Status: ${healthData.database.status}</p>
                            <p>Response Time: ${healthData.database.response_time}ms</p>
                        </div>
                        
                        <div class="health-item ${healthData.spsm_api.status === 'healthy' ? 'healthy' : 'unhealthy'}">
                            <h4>SPSM API</h4>
                            <p>Status: ${healthData.spsm_api.status}</p>
                            <p>Response Time: ${healthData.spsm_api.response_time}ms</p>
                        </div>
                        
                        <div class="health-item ${healthData.file_system.status === 'healthy' ? 'healthy' : 'unhealthy'}">
                            <h4>File System</h4>
                            <p>Status: ${healthData.file_system.status}</p>
                            <p>Free Space: ${healthData.file_system.free_space}</p>
                        </div>
                        
                        <div class="health-item ${healthData.overall.status === 'healthy' ? 'healthy' : 'unhealthy'}">
                            <h4>Overall Status</h4>
                            <p>Status: ${healthData.overall.status}</p>
                            <p>Last Check: ${healthData.overall.timestamp}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }
    
    async backupDatabase() {
        try {
            const response = await fetch('../../includes/admin/backup_database.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.showMessage('Database backup initiated successfully', 'success');
                    
                    // If backup file is provided, trigger download
                    if (result.backup_file) {
                        this.downloadBackup(result.backup_file);
                    }
                } else {
                    this.showMessage(result.message || 'Database backup failed', 'error');
                }
            } else {
                throw new Error('Network error');
            }
            
        } catch (error) {
            console.error('Error during database backup:', error);
            this.showMessage('Error during database backup', 'error');
        }
    }
    
    downloadBackup(backupFile) {
        const link = document.createElement('a');
        link.href = backupFile;
        link.download = `isn_attendance_backup_${new Date().toISOString().split('T')[0]}.sql`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
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

// Initialize admin dashboard when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.adminDashboard = new AdminDashboard();
});

// Global functions for onclick handlers
function forceSPSMSync() {
    window.adminDashboard.forceSPSMSync();
}

function checkSystemHealth() {
    window.adminDashboard.checkSystemHealth();
}

function backupDatabase() {
    window.adminDashboard.backupDatabase();
} 