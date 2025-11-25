/**
 * Movie Details Page JavaScript
 * Handles booking form interactions and dynamic updates
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize booking form functionality
    initBookingForm();
    
    // Initialize user menu
    initUserMenu();
    
    // Initialize mobile menu
    initMobileMenu();
});

/**
 * Initialize booking form functionality
 */
function initBookingForm() {
    const suatSelect = document.getElementById('suat_id');
    const giaVeInput = document.getElementById('gia_ve');
    const soGheInput = document.getElementById('so_ghe');
    const bookingForm = document.querySelector('.booking-form');
    const seatGrid = document.getElementById('seatGrid');
    
    if (!suatSelect || !giaVeInput || !bookingForm) return;
    
    // Update price when showtime is selected
    suatSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const price = selectedOption.getAttribute('data-price');
            const room = selectedOption.getAttribute('data-room');
            const type = selectedOption.getAttribute('data-type');
            
            giaVeInput.value = price;
            
            // Reset seat input and render seat map for this showtime
            soGheInput.value = '';
            soGheInput.placeholder = `Chọn ghế (A1 - E10) — ${room} - ${type}`;
            if (seatGrid) {
                renderSeatMap(seatGrid);
                loadBookedSeats(selectedOption.value, seatGrid);
            }
            
            // Show booking info
            updateBookingInfo(selectedOption);
        } else {
            giaVeInput.value = '';
            soGheInput.placeholder = 'A1 - E10';
            if (seatGrid) seatGrid.innerHTML = '';
        }
    });
    
    // Prevent manual editing (we use seat map)
    if (soGheInput) {
        soGheInput.addEventListener('keydown', (e) => e.preventDefault());
        soGheInput.addEventListener('paste', (e) => e.preventDefault());
        soGheInput.readOnly = true;
    }
    
    // Handle form submission
    bookingForm.addEventListener('submit', function(e) {
        if (!validateBookingForm()) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        showLoadingState();
    });
}

/**
 * Render 5x10 seat grid (A..E x 1..10)
 */
function renderSeatMap(container) {
    if (!container) return;
    container.innerHTML = '';
    const rows = ['A','B','C','D','E'];
    for (let r = 0; r < rows.length; r++) {
        for (let c = 1; c <= 10; c++) {
            const seatCode = `${rows[r]}${c}`;
            const div = document.createElement('div');
            div.className = 'seat';
            div.dataset.seat = seatCode;
            div.textContent = seatCode;
            div.addEventListener('click', onSeatClick);
            container.appendChild(div);
        }
    }
}

/**
 * Load booked seats for a showtime and mark them
 */
function loadBookedSeats(suatId, container) {
    if (!suatId || !container) return;
    fetch(`get_booked_seats.php?suat_id=${encodeURIComponent(suatId)}`)
        .then(r => r.json())
        .then(data => {
            const booked = (data && data.success && Array.isArray(data.booked)) ? data.booked : [];
            const seatDivs = container.querySelectorAll('.seat');
            seatDivs.forEach(div => {
                const code = div.dataset.seat;
                if (booked.includes(code)) {
                    div.classList.add('booked');
                    div.classList.remove('selected');
                } else {
                    div.classList.remove('booked');
                }
            });
        })
        .catch(() => {
            // Ignore error, seats remain selectable
        });
}

/**
 * Seat click handler: single selection
 */
function onSeatClick(e) {
    const el = e.currentTarget;
    if (el.classList.contains('booked')) return;
    const grid = el.parentElement;
    // single-select: clear previous
    grid.querySelectorAll('.seat.selected').forEach(s => s.classList.remove('selected'));
    el.classList.add('selected');
    const soGheInput = document.getElementById('so_ghe');
    if (soGheInput) soGheInput.value = el.dataset.seat;
}

/**
 * Update booking information display
 */
function updateBookingInfo(selectedOption) {
    const bookingInfo = document.querySelector('.booking-info');
    if (!bookingInfo) return;
    
    const timeText = selectedOption.textContent.split(' - ')[0];
    const roomText = selectedOption.getAttribute('data-room');
    const typeText = selectedOption.getAttribute('data-type');
    const priceText = selectedOption.getAttribute('data-price');
    
    // Create or update showtime info
    let showtimeInfo = bookingInfo.querySelector('.showtime-info');
    if (!showtimeInfo) {
        showtimeInfo = document.createElement('div');
        showtimeInfo.className = 'showtime-info';
        showtimeInfo.innerHTML = '<h4>Thông tin suất chiếu</h4>';
        bookingInfo.insertBefore(showtimeInfo, bookingInfo.querySelector('.booking-note'));
    }
    
    showtimeInfo.innerHTML = `
        <h4>Thông tin suất chiếu</h4>
        <ul>
            <li><strong>Thời gian:</strong> ${timeText}</li>
            <li><strong>Phòng:</strong> ${roomText}</li>
            <li><strong>Loại phòng:</strong> ${typeText}</li>
            <li><strong>Giá vé:</strong> ${formatPrice(priceText)}đ</li>
        </ul>
    `;
}

/**
 * Validate booking form
 */
function validateBookingForm() {
    const suatId = document.getElementById('suat_id').value;
    const soGhe = document.getElementById('so_ghe').value.trim();
    const giaVe = document.getElementById('gia_ve').value;
    
    if (!suatId) {
        showAlert('Vui lòng chọn suất chiếu!', 'error');
        return false;
    }
    
    if (!soGhe) {
        showAlert('Vui lòng nhập số ghế!', 'error');
        return false;
    }
    
    if (!giaVe || giaVe <= 0) {
        showAlert('Giá vé không hợp lệ!', 'error');
        return false;
    }
    
    // Validate seat format
    const seatPattern = /^[A-E](10|[1-9])$/i;
    if (!seatPattern.test(soGhe)) {
        showAlert('Vui lòng nhập đúng định dạng ghế (VD: A1, B5)!', 'error');
        return false;
    }
    
    return true;
}

/**
 * Show loading state
 */
function showLoadingState() {
    const form = document.querySelector('.booking-form');
    const submitBtn = form.querySelector('.btn-primary');
    
    form.classList.add('loading');
    submitBtn.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Đang xử lý...';
    submitBtn.disabled = true;
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    // Insert at the top of booking section
    const bookingSection = document.querySelector('.booking-section .container');
    if (bookingSection) {
        bookingSection.insertBefore(alert, bookingSection.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

/**
 * Format price with thousand separators
 */
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}

/**
 * Initialize user menu functionality
 */
function initUserMenu() {
    const userBtn = document.querySelector('[data-user-menu]');
    const userDropdown = document.querySelector('[data-user-dropdown]');
    
    if (!userBtn || !userDropdown) return;
    
    userBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!userBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.remove('active');
        }
    });
}

/**
 * Initialize mobile menu functionality
 */
function initMobileMenu() {
    const menuOpenBtn = document.querySelector('[data-menu-open-btn]');
    const menuCloseBtn = document.querySelector('[data-menu-close-btn]');
    const navbar = document.querySelector('[data-navbar]');
    const overlay = document.querySelector('[data-overlay]');
    
    if (!menuOpenBtn || !menuCloseBtn || !navbar || !overlay) return;
    
    menuOpenBtn.addEventListener('click', function() {
        navbar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
    
    menuCloseBtn.addEventListener('click', function() {
        navbar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    overlay.addEventListener('click', function() {
        navbar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    });
}

/**
 * Smooth scroll to top
 */
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

/**
 * Initialize go to top button
 */
function initGoToTop() {
    const goTopBtn = document.querySelector('[data-go-top]');
    
    if (!goTopBtn) return;
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            goTopBtn.classList.add('active');
        } else {
            goTopBtn.classList.remove('active');
        }
    });
    
    // Scroll to top when clicked
    goTopBtn.addEventListener('click', scrollToTop);
}

// Initialize go to top functionality
initGoToTop();

/**
 * Add smooth scrolling to all anchor links
 */
document.addEventListener('click', function(e) {
    if (e.target.matches('a[href^="#"]')) {
        e.preventDefault();
        const targetId = e.target.getAttribute('href').substring(1);
        const targetElement = document.getElementById(targetId);
        
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }
});

/**
 * Add loading animation to buttons
 */
document.addEventListener('click', function(e) {
    if (e.target.matches('.btn-primary') || e.target.closest('.btn-primary')) {
        const btn = e.target.matches('.btn-primary') ? e.target : e.target.closest('.btn-primary');
        
        // Add ripple effect
        const ripple = document.createElement('span');
        ripple.className = 'ripple';
        ripple.style.position = 'absolute';
        ripple.style.borderRadius = '50%';
        ripple.style.background = 'rgba(255, 255, 255, 0.6)';
        ripple.style.transform = 'scale(0)';
        ripple.style.animation = 'ripple 0.6s linear';
        ripple.style.left = (e.clientX - btn.offsetLeft) + 'px';
        ripple.style.top = (e.clientY - btn.offsetTop) + 'px';
        
        btn.style.position = 'relative';
        btn.style.overflow = 'hidden';
        btn.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }
});

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .user-dropdown.active {
        display: block;
    }
    
    .go-top.active {
        opacity: 1;
        visibility: visible;
    }
`;
document.head.appendChild(style);
