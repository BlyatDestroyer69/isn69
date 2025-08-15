/**
 * Login Form JavaScript
 * Sistem Kehadiran ISN
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const icInput = document.getElementById('ic_number');
    const employeeIdInput = document.getElementById('employee_id');

    // IC Number formatting
    icInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length >= 6) {
            value = value.slice(0, 6) + '-' + value.slice(6);
        }
        if (value.length >= 9) {
            value = value.slice(0, 9) + '-' + value.slice(9);
        }
        
        e.target.value = value;
    });

    // Form validation
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        clearErrors();
        
        // Validate IC Number
        if (!validateIC(icInput.value)) {
            showError(icInput, 'Format IC tidak sah. Gunakan format: 800101-01-1234');
            return;
        }
        
        // Validate Employee ID
        if (!validateEmployeeID(employeeIdInput.value)) {
            showError(employeeIdInput, 'ID Pekerja tidak sah');
            return;
        }
        
        // If validation passes, submit form
        submitForm();
    });

    // IC Number validation
    function validateIC(ic) {
        const icPattern = /^\d{6}-\d{2}-\d{4}$/;
        if (!icPattern.test(ic)) {
            return false;
        }
        
        // Extract date components
        const parts = ic.split('-');
        const year = parseInt(parts[0].substring(0, 2));
        const month = parseInt(parts[0].substring(2, 4));
        const day = parseInt(parts[0].substring(4, 6));
        
        // Basic date validation
        if (month < 1 || month > 12) return false;
        if (day < 1 || day > 31) return false;
        
        return true;
    }

    // Employee ID validation
    function validateEmployeeID(id) {
        return id.length >= 3 && /^[A-Z0-9]+$/.test(id);
    }

    // Show error message
    function showError(input, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.color = 'var(--danger-color)';
        errorDiv.style.fontSize = '0.9rem';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = message;
        
        input.parentNode.appendChild(errorDiv);
        input.style.borderColor = 'var(--danger-color)';
    }

    // Clear all errors
    function clearErrors() {
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(msg => msg.remove());
        
        [icInput, employeeIdInput].forEach(input => {
            input.style.borderColor = 'var(--border-color)';
        });
    }

    // Submit form
    function submitForm() {
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        submitBtn.disabled = true;
        
        // Simulate processing delay
        setTimeout(() => {
            loginForm.submit();
        }, 1000);
    }

    // Auto-focus on first input
    icInput.focus();

    // Enter key navigation
    icInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            employeeIdInput.focus();
        }
    });

    employeeIdInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loginForm.submit();
        }
    });

    // Real-time validation feedback
    icInput.addEventListener('blur', function() {
        if (this.value && !validateIC(this.value)) {
            showError(this, 'Format IC tidak sah');
        }
    });

    employeeIdInput.addEventListener('blur', function() {
        if (this.value && !validateEmployeeID(this.value)) {
            showError(this, 'ID Pekerja tidak sah');
        }
    });

    // Clear errors when user starts typing
    icInput.addEventListener('input', clearErrors);
    employeeIdInput.addEventListener('input', clearErrors);
}); 