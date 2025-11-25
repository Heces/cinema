
//Xử lý các tương tác trên trang đăng nhập và đăng ký


// hiện mật khẩu
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.name = 'eye-off-outline';
    } else {
        input.type = 'password';
        icon.name = 'eye-outline';
    }
}

// xác thực
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            showFieldError(input, 'Trường này là bắt buộc');
            isValid = false;
        } else {
            clearFieldError(input);
        }
    });
    
    // email
    const emailInput = form.querySelector('input[type="email"]');
    if (emailInput && emailInput.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value)) {
            showFieldError(emailInput, 'Email không hợp lệ');
            isValid = false;
        }
    }
    
    // dienthoai
    const phoneInput = form.querySelector('input[type="tel"]');
    if (phoneInput && phoneInput.value) {
        const phoneRegex = /^[0-9]{10,11}$/;
        if (!phoneRegex.test(phoneInput.value.replace(/\s/g, ''))) {
            showFieldError(phoneInput, 'Số điện thoại không hợp lệ');
            isValid = false;
        }
    }
    
    // matkhau
    const passwordInput = form.querySelector('input[name="password"]');
    const confirmPasswordInput = form.querySelector('input[name="confirm_password"]');
    if (passwordInput && confirmPasswordInput) {
        if (passwordInput.value !== confirmPasswordInput.value) {
            showFieldError(confirmPasswordInput, 'Mật khẩu xác nhận không khớp');
            isValid = false;
        }
    }
    
    
    return isValid;
}

// error
function showFieldError(input, message) {
    clearFieldError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    
    input.classList.add('error');
    input.parentNode.appendChild(errorDiv);
}

function clearFieldError(input) {
    input.classList.remove('error');
    const existingError = input.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}


// thongbao
function showAlert(type, message) {

    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    
    const icon = type === 'success' ? 'checkmark-circle' : 'alert-circle';
    alertDiv.innerHTML = `
        <ion-icon name="${icon}"></ion-icon>
        <span>${message}</span>
    `;
    
    const form = document.querySelector('.auth-form');
    form.parentNode.insertBefore(alertDiv, form);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// thoigianthuc
function setupRealTimeValidation(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                showFieldError(this, 'Trường này là bắt buộc');
            } else {
                clearFieldError(this);
            }
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                clearFieldError(this);
            }
        });
    });
}

// DOM 
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form[action=""]');
    const registerForm = document.getElementById('registerForm');
    
    if (loginForm) {
        handleFormSubmit('loginForm');
        setupRealTimeValidation('loginForm');
    }
    
    if (registerForm) {
        handleFormSubmit('registerForm');
        setupRealTimeValidation('registerForm');
    }
    
 
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
        showAlert('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
    }
    
    // css error
    const style = document.createElement('style');
    style.textContent = `
        .form-input.error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
        }
        
        .field-error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .field-error::before {
            content: '⚠';
            font-size: 14px;
        }
    `;
    document.head.appendChild(style);
});


function checkPasswordStrength(password) {
    let strength = 0;
    const checks = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password)
    };
    
    strength = Object.values(checks).filter(Boolean).length;
    
    return {
        score: strength,
        checks: checks,
        level: strength < 2 ? 'weak' : strength < 4 ? 'medium' : 'strong'
    };
}

// do manh mat khau
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.querySelector('input[name="password"]');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
        });
    }
});
