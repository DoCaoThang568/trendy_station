/**
 * Trendy Station - Main JavaScript File
 * Contains all common functions and utilities
 */

// Global variables
let currentPage = 'products';

/**
 * Navigation keyboard shortcuts
 * Fixed for browser compatibility
 */
document.addEventListener('DOMContentLoaded', function() {
    // Ensure DOM is loaded before attaching events
    console.log('Trendy Station keyboard shortcuts loaded');
});

document.addEventListener('keydown', function(e) {
    // Debug logging
    if (e.altKey) {
        console.log('Alt key detected with:', e.key, e.code, e.keyCode);
    }
    
    // Check if Alt key is pressed (avoid conflicts with browser shortcuts)
    if (e.altKey && !e.ctrlKey && !e.shiftKey) {
        let handled = false;
        
        switch(e.key) {
            case '1':
            case 'Digit1':
                e.preventDefault();
                e.stopPropagation();
                navigateToPage('dashboard');
                showToast('Chuyển trang Tổng quan (Alt+1)', 'info');
                handled = true;
                break;
            case '2':
            case 'Digit2':
                e.preventDefault();
                e.stopPropagation();
                navigateToPage('products');
                showToast('Chuyển trang Sản phẩm (Alt+2)', 'info');
                handled = true;
                break;
            case '3':
            case 'Digit3':
                e.preventDefault();
                e.stopPropagation();
                navigateToPage('sales');
                showToast('Chuyển trang Bán hàng (Alt+3)', 'info');
                handled = true;
                break;
            case '4':
            case 'Digit4':
                e.preventDefault();
                e.stopPropagation();
                navigateToPage('imports');
                showToast('Chuyển trang Nhập hàng (Alt+4)', 'info');
                handled = true;
                break;
            case '5':
            case 'Digit5':
                e.preventDefault();
                e.stopPropagation();
                navigateToPage('customers');
                showToast('Chuyển trang Khách hàng (Alt+5)', 'info');
                handled = true;
                break;
            case '6':
            case 'Digit6':
                e.preventDefault();
                e.stopPropagation();
                navigateToPage('returns');
                showToast('Chuyển trang Trả hàng (Alt+6)', 'info');
                handled = true;
                break;
            case '7':
            case 'Digit7':
                e.preventDefault();
                e.stopPropagation();
                navigateToPage('reports');
                showToast('Chuyển trang Báo cáo (Alt+7)', 'info');
                handled = true;
                break;
            case 'h':
            case 'H':
                e.preventDefault();
                e.stopPropagation();
                showKeyboardShortcuts();
                handled = true;
                break;
        }
        
        // Alternative check using keyCode for older browsers
        if (!handled && e.keyCode >= 49 && e.keyCode <= 55) {
            e.preventDefault();
            e.stopPropagation();
            const pageMap = {
                49: 'dashboard',   // Alt+1
                50: 'products',    // Alt+2
                51: 'sales',       // Alt+3
                52: 'imports',     // Alt+4
                53: 'customers',   // Alt+5
                54: 'returns',     // Alt+6
                55: 'reports'      // Alt+7
            };
            
            const pageNames = {
                'dashboard': 'Tổng quan',
                'products': 'Sản phẩm',
                'sales': 'Bán hàng',
                'imports': 'Nhập hàng',
                'customers': 'Khách hàng',
                'returns': 'Trả hàng',
                'reports': 'Báo cáo'
            };
            
            const page = pageMap[e.keyCode];
            if (page) {
                navigateToPage(page);
                showToast(`Chuyển trang ${pageNames[page]} (Alt+${e.keyCode - 48})`, 'info');
            }
        }
    }
    
    // Other global shortcuts
    switch(e.key) {
        case 'Escape':
            // Close all modals
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    closeModal(modal.id);
                }
            });
            break;
        case 'F1':
            e.preventDefault();
            showKeyboardShortcuts();
            break;
    }
});

/**
 * Navigate to page
 * @param {string} page - Page to navigate to
 */
function navigateToPage(page) {
    console.log('Navigating to page:', page);
    window.location.href = `index.php?page=${page}`;
}

/**
 * Show keyboard shortcuts help
 */
function showKeyboardShortcuts() {
    const content = `
        <div style="font-family: monospace; line-height: 1.8;">
            <h4>🚀 Phím tắt Navigation</h4>
            <div style="margin-bottom: 1rem;">
                <strong>Alt + 1:</strong> Tổng quan<br>
                <strong>Alt + 2:</strong> Sản phẩm<br>
                <strong>Alt + 3:</strong> Bán hàng<br>
                <strong>Alt + 4:</strong> Nhập hàng<br>
                <strong>Alt + 5:</strong> Khách hàng<br>
                <strong>Alt + 6:</strong> Trả hàng<br>
                <strong>Alt + 7:</strong> Báo cáo<br>
            </div>
            
            <h4>⚡ Phím tắt chung</h4>
            <div style="margin-bottom: 1rem;">
                <strong>F1 / Alt + H:</strong> Hiển thị trợ giúp<br>
                <strong>Escape:</strong> Đóng modal<br>
                <strong>Ctrl + S:</strong> Lưu (trang hiện tại)<br>
                <strong>Ctrl + N:</strong> Thêm mới (trang hiện tại)<br>
            </div>
            
            <h4>🛒 Phím tắt Bán hàng</h4>
            <div style="margin-bottom: 1rem;">
                <strong>F2:</strong> Thêm sản phẩm<br>
                <strong>F3:</strong> Thêm khách hàng<br>
                <strong>F4:</strong> Thanh toán<br>
                <strong>F5:</strong> In hóa đơn<br>
                <strong>Ctrl + D:</strong> Xóa draft<br>
            </div>
            
            <div style="text-align: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                <small style="color: #666;">Nhấn <strong>Escape</strong> để đóng</small>
            </div>
        </div>
    `;
    
    createModal('🎯 Hướng dẫn phím tắt', content);
}

/**
 * Test keyboard shortcuts functionality
 */
function testKeyboardShortcuts() {
    showToast('🧪 Testing phím tắt navigation...', 'info');
    console.log('Keyboard shortcuts test initiated');
    
    // Test each navigation
    const pages = ['dashboard', 'products', 'sales', 'imports', 'customers', 'returns', 'reports'];
    let currentTest = 0;
    
    function runNextTest() {
        if (currentTest < pages.length) {
            const page = pages[currentTest];
            console.log(`Testing navigation to: ${page}`);
            showToast(`✅ Test ${currentTest + 1}: ${page}`, 'success');
            currentTest++;
            setTimeout(runNextTest, 1000);
        } else {
            showToast('🎉 All keyboard shortcut tests completed!', 'success');
        }
    }
    
    runNextTest();
}

/**
 * Add keyboard shortcut indicators to navigation
 */
function addKeyboardIndicators() {
    const navItems = document.querySelectorAll('.nav-item');
    const shortcuts = ['Alt+1', 'Alt+2', 'Alt+3', 'Alt+4', 'Alt+5', 'Alt+6', 'Alt+7'];
    
    navItems.forEach((item, index) => {
        if (index < shortcuts.length) {
            // Add shortcut indicator
            const indicator = document.createElement('span');
            indicator.textContent = shortcuts[index];
            indicator.style.cssText = `
                font-size: 0.75rem;
                opacity: 0.7;
                margin-left: 0.5rem;
                color: var(--accent-color);
                font-weight: 500;
            `;
            item.appendChild(indicator);
        }
    });
}

// Add indicators when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(addKeyboardIndicators, 100);
    
    // Add test button to footer for debugging
    const footer = document.querySelector('.footer');
    if (footer) {
        const testBtn = document.createElement('button');
        testBtn.textContent = '🧪 Test Phím tắt';
        testBtn.onclick = testKeyboardShortcuts;
        testBtn.style.cssText = `
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            margin: 1rem;
            font-size: 0.9rem;
        `;
        footer.appendChild(testBtn);
    }
});

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type: success, error, warning
 */
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast toast-${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

/**
 * Format currency VND
 * @param {number} amount - Amount to format
 * @return {string} Formatted currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

/**
 * Format date
 * @param {string|Date} date - Date to format
 * @return {string} Formatted date
 */
function formatDate(date) {
    return new Date(date).toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Validate form data
 * @param {object} data - Form data to validate
 * @param {array} required - Required fields
 * @return {object} Validation result
 */
function validateForm(data, required = []) {
    const errors = [];
    
    required.forEach(field => {
        if (!data[field] || data[field].trim() === '') {
            errors.push(`${field} không được để trống`);
        }
    });
    
    // Validate email if present
    if (data.email && !isValidEmail(data.email)) {
        errors.push('Email không hợp lệ');
    }
    
    // Validate phone if present
    if (data.phone && !isValidPhone(data.phone)) {
        errors.push('Số điện thoại không hợp lệ');
    }
    
    return {
        isValid: errors.length === 0,
        errors: errors
    };
}

/**
 * Validate email
 * @param {string} email - Email to validate
 * @return {boolean} Is valid
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate phone number
 * @param {string} phone - Phone to validate
 * @return {boolean} Is valid
 */
function isValidPhone(phone) {
    const phoneRegex = /^(0|84|\+84)[3|5|7|8|9][0-9]{8}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

/**
 * Show loading state
 * @param {string} elementId - Element to show loading
 */
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add('loading');
    }
}

/**
 * Hide loading state
 * @param {string} elementId - Element to hide loading
 */
function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove('loading');
    }
}

/**
 * Create modal
 * @param {string} title - Modal title
 * @param {string} content - Modal content (HTML string)
 * @param {function} [onSave=null] - Save callback function
 * @param {string} [modalIdSuffix=''] - Optional suffix for modal ID for uniqueness
 * @return {string} Modal ID
 */
function createModal(title, content, onSave = null, modalIdSuffix = '') {
    // Remove any existing modals first to prevent issues if not closed properly
    const existingModals = document.querySelectorAll('.modal');
    existingModals.forEach(existingModal => {
        if (existingModal.parentNode) {
            // Pass true for immediate removal without transition, as we are opening a new one.
            closeModal(existingModal.id, true); 
        }
    });

    const modalId = 'modal_' + (modalIdSuffix || Date.now());
    
    const modalElement = document.createElement('div');
    modalElement.id = modalId;
    modalElement.className = 'modal'; // Base class for styling
    
    // Construct inner HTML. The 'content' passed is for the modal-body.
    modalElement.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>${title}</h3>
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body">
                ${content}
            </div>
            ${onSave ? `
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary cancel-button">Hủy</button>
                <button type="button" class="btn btn-primary save-button">Lưu</button>
            </div>` : ''}
        </div>
    `;
    
    document.body.appendChild(modalElement);

    const closeButton = modalElement.querySelector('.close-button');
    const saveButton = modalElement.querySelector('.save-button');
    const cancelButton = modalElement.querySelector('.cancel-button');

    // Handler to close the modal
    const closeModalHandler = () => closeModal(modalId);
    
    if (closeButton) {
        closeButton.addEventListener('click', closeModalHandler);
    }
    if (cancelButton) {
        cancelButton.addEventListener('click', closeModalHandler);
    }
    if (saveButton && typeof onSave === 'function') {
        saveButton.addEventListener('click', () => {
            onSave(modalId); // Pass modalId if onSave needs to interact with the modal
        });
    }

    // Close on Escape key
    const escapeKeyListener = (event) => {
        if (event.key === 'Escape') {
            // Check if this is the topmost/active modal before closing
            const allModals = document.querySelectorAll('.modal');
            if (allModals.length > 0 && allModals[allModals.length - 1].id === modalId) {
                 closeModal(modalId);
            }
        }
    };
    document.addEventListener('keydown', escapeKeyListener);
    
    // Store the listener on the element to remove it specifically when this modal closes
    modalElement.escapeKeyListener = escapeKeyListener;

    // Make it visible with transition
    // 1. Set display style that allows visibility (e.g., flex for centering)
    modalElement.style.display = 'flex'; 

    // 2. Add 'show' class to trigger CSS transition (opacity, transform, etc.)
    //    Using requestAnimationFrame ensures the 'display' change is applied before 'show' class.
    requestAnimationFrame(() => {
        modalElement.classList.add('show');
    });

    return modalId;
}

/**
 * Close modal
 * @param {string} modalId - Modal ID to close
 * @param {boolean} [immediate=false] - If true, remove immediately without transition
 */
function closeModal(modalId, immediate = false) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) {
        // console.warn('Modal not found or already removed:', modalId);
        return;
    }

    // Remove the specific Escape key listener associated with this modal
    if (modalElement.escapeKeyListener) {
        document.removeEventListener('keydown', modalElement.escapeKeyListener);
        delete modalElement.escapeKeyListener; // Clean up the stored property
    }

    if (immediate) {
        if (modalElement.parentNode) {
            modalElement.parentNode.removeChild(modalElement);
        }
        return;
    }

    modalElement.classList.remove('show'); // Trigger hide transition

    const handleTransitionEnd = (event) => {
        // Ensure the transitionend event is from the modalElement itself, not a child
        if (event.target === modalElement) {
            modalElement.removeEventListener('transitionend', handleTransitionEnd);
            if (modalElement.parentNode) {
                modalElement.parentNode.removeChild(modalElement);
            }
        }
    };
    
    const modalContentElement = modalElement.querySelector('.modal-content');
    let transitionDuration = 0;
    if (modalContentElement) { // Check based on modal-content or modal itself for transition
        const style = getComputedStyle(modalElement); // Check transition on modal element
        transitionDuration = parseFloat(style.transitionDuration) * 1000;
        if (transitionDuration === 0 && modalContentElement) { // Fallback to modal-content if modal has no duration
             const contentStyle = getComputedStyle(modalContentElement);
             transitionDuration = parseFloat(contentStyle.transitionDuration) * 1000;
        }
    }
    
    if (transitionDuration > 0 && !immediate) {
        modalElement.addEventListener('transitionend', handleTransitionEnd);
        // Fallback timeout in case transitionend doesn't fire
        setTimeout(() => {
            modalElement.removeEventListener('transitionend', handleTransitionEnd); // Clean up
            if (modalElement.parentNode) {
                modalElement.parentNode.removeChild(modalElement);
            }
        }, transitionDuration + 100); // Add a small buffer
    } else {
        // No transition or immediate removal requested
        if (modalElement.parentNode) {
            modalElement.parentNode.removeChild(modalElement);
        }
    }
}

/**
 * Test keyboard shortcuts functionality
 */
function testKeyboardShortcuts() {
    showToast('🧪 Testing phím tắt navigation...', 'info');
    console.log('Keyboard shortcuts test initiated');
    
    // Test each navigation
    const pages = ['dashboard', 'products', 'sales', 'imports', 'customers', 'returns', 'reports'];
    let currentTest = 0;
    
    function runNextTest() {
        if (currentTest < pages.length) {
            const page = pages[currentTest];
            console.log(`Testing navigation to: ${page}`);
            showToast(`✅ Test ${currentTest + 1}: ${page}`, 'success');
            currentTest++;
            setTimeout(runNextTest, 1000);
        } else {
            showToast('🎉 All keyboard shortcut tests completed!', 'success');
        }
    }
    
    runNextTest();
}

/**
 * Add keyboard shortcut indicators to navigation
 */
function addKeyboardIndicators() {
    const navItems = document.querySelectorAll('.nav-item');
    const shortcuts = ['Alt+1', 'Alt+2', 'Alt+3', 'Alt+4', 'Alt+5', 'Alt+6', 'Alt+7'];
    
    navItems.forEach((item, index) => {
        if (index < shortcuts.length) {
            // Add shortcut indicator
            const indicator = document.createElement('span');
            indicator.textContent = shortcuts[index];
            indicator.style.cssText = `
                font-size: 0.75rem;
                opacity: 0.7;
                margin-left: 0.5rem;
                color: var(--accent-color);
                font-weight: 500;
            `;
            item.appendChild(indicator);
        }
    });
}

// Add indicators when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(addKeyboardIndicators, 100);
    
    // Add test button to footer for debugging
    const footer = document.querySelector('.footer');
    if (footer) {
        const testBtn = document.createElement('button');
        testBtn.textContent = '🧪 Test Phím tắt';
        testBtn.onclick = testKeyboardShortcuts;
        testBtn.style.cssText = `
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            margin: 1rem;
            font-size: 0.9rem;
        `;
        footer.appendChild(testBtn);
    }
});

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type: success, error, warning
 */
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast toast-${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

/**
 * Format currency VND
 * @param {number} amount - Amount to format
 * @return {string} Formatted currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

/**
 * Format date
 * @param {string|Date} date - Date to format
 * @return {string} Formatted date
 */
function formatDate(date) {
    return new Date(date).toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Validate form data
 * @param {object} data - Form data to validate
 * @param {array} required - Required fields
 * @return {object} Validation result
 */
function validateForm(data, required = []) {
    const errors = [];
    
    required.forEach(field => {
        if (!data[field] || data[field].trim() === '') {
            errors.push(`${field} không được để trống`);
        }
    });
    
    // Validate email if present
    if (data.email && !isValidEmail(data.email)) {
        errors.push('Email không hợp lệ');
    }
    
    // Validate phone if present
    if (data.phone && !isValidPhone(data.phone)) {
        errors.push('Số điện thoại không hợp lệ');
    }
    
    return {
        isValid: errors.length === 0,
        errors: errors
    };
}

/**
 * Validate email
 * @param {string} email - Email to validate
 * @return {boolean} Is valid
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate phone number
 * @param {string} phone - Phone to validate
 * @return {boolean} Is valid
 */
function isValidPhone(phone) {
    const phoneRegex = /^(0|84|\+84)[3|5|7|8|9][0-9]{8}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

/**
 * Show loading state
 * @param {string} elementId - Element to show loading
 */
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add('loading');
    }
}

/**
 * Hide loading state
 * @param {string} elementId - Element to hide loading
 */
function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove('loading');
    }
}

/**
 * Create modal
 * @param {string} title - Modal title
 * @param {string} content - Modal content (HTML string)
 * @param {function} [onSave=null] - Save callback function
 * @param {string} [modalIdSuffix=''] - Optional suffix for modal ID for uniqueness
 * @return {string} Modal ID
 */
function createModal(title, content, onSave = null, modalIdSuffix = '') {
    // Remove any existing modals first to prevent issues if not closed properly
    const existingModals = document.querySelectorAll('.modal');
    existingModals.forEach(existingModal => {
        if (existingModal.parentNode) {
            // Pass true for immediate removal without transition, as we are opening a new one.
            closeModal(existingModal.id, true); 
        }
    });

    const modalId = 'modal_' + (modalIdSuffix || Date.now());
    
    const modalElement = document.createElement('div');
    modalElement.id = modalId;
    modalElement.className = 'modal'; // Base class for styling
    
    // Construct inner HTML. The 'content' passed is for the modal-body.
    modalElement.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>${title}</h3>
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body">
                ${content}
            </div>
            ${onSave ? `
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary cancel-button">Hủy</button>
                <button type="button" class="btn btn-primary save-button">Lưu</button>
            </div>` : ''}
        </div>
    `;
    
    document.body.appendChild(modalElement);

    const closeButton = modalElement.querySelector('.close-button');
    const saveButton = modalElement.querySelector('.save-button');
    const cancelButton = modalElement.querySelector('.cancel-button');

    // Handler to close the modal
    const closeModalHandler = () => closeModal(modalId);
    
    if (closeButton) {
        closeButton.addEventListener('click', closeModalHandler);
    }
    if (cancelButton) {
        cancelButton.addEventListener('click', closeModalHandler);
    }
    if (saveButton && typeof onSave === 'function') {
        saveButton.addEventListener('click', () => {
            onSave(modalId); // Pass modalId if onSave needs to interact with the modal
        });
    }

    // Close on Escape key
    const escapeKeyListener = (event) => {
        if (event.key === 'Escape') {
            // Check if this is the topmost/active modal before closing
            const allModals = document.querySelectorAll('.modal');
            if (allModals.length > 0 && allModals[allModals.length - 1].id === modalId) {
                 closeModal(modalId);
            }
        }
    };
    document.addEventListener('keydown', escapeKeyListener);
    
    // Store the listener on the element to remove it specifically when this modal closes
    modalElement.escapeKeyListener = escapeKeyListener;

    // Make it visible with transition
    // 1. Set display style that allows visibility (e.g., flex for centering)
    modalElement.style.display = 'flex'; 

    // 2. Add 'show' class to trigger CSS transition (opacity, transform, etc.)
    //    Using requestAnimationFrame ensures the 'display' change is applied before 'show' class.
    requestAnimationFrame(() => {
        modalElement.classList.add('show');
    });

    return modalId;
}

/**
 * Close modal
 * @param {string} modalId - Modal ID to close
 * @param {boolean} [immediate=false] - If true, remove immediately without transition
 */
function closeModal(modalId, immediate = false) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) {
        // console.warn('Modal not found or already removed:', modalId);
        return;
    }

    // Remove the specific Escape key listener associated with this modal
    if (modalElement.escapeKeyListener) {
        document.removeEventListener('keydown', modalElement.escapeKeyListener);
        delete modalElement.escapeKeyListener; // Clean up the stored property
    }

    if (immediate) {
        if (modalElement.parentNode) {
            modalElement.parentNode.removeChild(modalElement);
        }
        return;
    }

    modalElement.classList.remove('show'); // Trigger hide transition

    const handleTransitionEnd = (event) => {
        // Ensure the transitionend event is from the modalElement itself, not a child
        if (event.target === modalElement) {
            modalElement.removeEventListener('transitionend', handleTransitionEnd);
            if (modalElement.parentNode) {
                modalElement.parentNode.removeChild(modalElement);
            }
        }
    };
    
    const modalContentElement = modalElement.querySelector('.modal-content');
    let transitionDuration = 0;
    if (modalContentElement) { // Check based on modal-content or modal itself for transition
        const style = getComputedStyle(modalElement); // Check transition on modal element
        transitionDuration = parseFloat(style.transitionDuration) * 1000;
        if (transitionDuration === 0 && modalContentElement) { // Fallback to modal-content if modal has no duration
             const contentStyle = getComputedStyle(modalContentElement);
             transitionDuration = parseFloat(contentStyle.transitionDuration) * 1000;
        }
    }
    
    if (transitionDuration > 0 && !immediate) {
        modalElement.addEventListener('transitionend', handleTransitionEnd);
        // Fallback timeout in case transitionend doesn't fire
        setTimeout(() => {
            modalElement.removeEventListener('transitionend', handleTransitionEnd); // Clean up
            if (modalElement.parentNode) {
                modalElement.parentNode.removeChild(modalElement);
            }
        }, transitionDuration + 100); // Add a small buffer
    } else {
        // No transition or immediate removal requested
        if (modalElement.parentNode) {
            modalElement.parentNode.removeChild(modalElement);
        }
    }
}

/**
 * Handle modal save
 * @param {string} modalId - Modal ID
 */
function handleModalSave(modalId) {
    const callback = window[`saveCallback_${modalId}`];
    if (callback) {
        const formData = getFormData(modalId);
        callback(formData, modalId);
    }
}

/**
 * Get form data from modal
 * @param {string} modalId - Modal ID
 * @return {object} Form data
 */
function getFormData(modalId) {
    const modal = document.getElementById(modalId);
    const formData = {};
    
    const inputs = modal.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        if (input.name) {
            formData[input.name] = input.value;
        }
    });
    
    return formData;
}

/**
 * Confirm delete action
 * @param {string} itemName - Name of item to delete
 * @param {function} callback - Delete callback
 */
function confirmDelete(itemName, callback) {
    if (confirm(`Bạn có chắc chắn muốn xóa ${itemName}?`)) {
        callback();
    }
}

/**
 * Debounce function
 * @param {function} func - Function to debounce
 * @param {number} wait - Wait time in ms
 * @return {function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * AJAX request helper
 * @param {string} url - URL to request
 * @param {object} options - Request options
 * @return {Promise} Fetch promise
 */
async function apiRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    try {
        showLoading('main-content');
        const response = await fetch(url, finalOptions);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        hideLoading('main-content');
        
        return data;
    } catch (error) {
        hideLoading('main-content');
        showToast('Có lỗi xảy ra: ' + error.message, 'error');
        throw error;
    }
}

/**
 * Search table rows
 * @param {string} searchTerm - Search term
 * @param {string} tableId - Table ID to search
 */
function searchTable(searchTerm, tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const normalizedSearch = searchTerm.toLowerCase().trim();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(normalizedSearch);
        row.style.display = isVisible ? '' : 'none';
        
        // Highlight matching text
        if (isVisible && normalizedSearch) {
            highlightText(row, searchTerm);
        } else {
            removeHighlight(row);
        }
    });
}

/**
 * Highlight text in element
 * @param {Element} element - Element to highlight
 * @param {string} searchTerm - Term to highlight
 */
function highlightText(element, searchTerm) {
    removeHighlight(element);
    
    if (!searchTerm) return;
    
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    const walker = document.createTreeWalker(
        element,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );
    
    const textNodes = [];
    let node;
    
    while (node = walker.nextNode()) {
        textNodes.push(node);
    }
    
    textNodes.forEach(textNode => {
        const parent = textNode.parentNode;
        if (parent.tagName === 'SCRIPT' || parent.tagName === 'STYLE') return;
        
        const text = textNode.textContent;
        if (regex.test(text)) {
            const highlightedText = text.replace(regex, '<mark>$1</mark>');
            const span = document.createElement('span');
            span.innerHTML = highlightedText;
            parent.replaceChild(span, textNode);
        }
    });
}

/**
 * Remove highlight from element
 * @param {Element} element - Element to remove highlight
 */
function removeHighlight(element) {
    const marks = element.querySelectorAll('mark');
    marks.forEach(mark => {
        const parent = mark.parentNode;
        parent.replaceChild(document.createTextNode(mark.textContent), mark);
        parent.normalize();
    });
}

/**
 * Initialize page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    };
    
    // Handle form submissions
    document.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Here you would typically send data to server
        console.log('Form submitted:', data);
        showToast('Dữ liệu đã được lưu thành công!', 'success');
    });
    
    // Add smooth scrolling to anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Initialize tooltips (if needed)
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
});

/**
 * Show tooltip
 * @param {Event} e - Mouse event
 */
function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = e.target.getAttribute('data-tooltip');
    tooltip.style.position = 'absolute';
    tooltip.style.background = 'rgba(0,0,0,0.8)';
    tooltip.style.color = 'white';
    tooltip.style.padding = '0.5rem';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '0.8rem';
    tooltip.style.zIndex = '1002';
    tooltip.style.pointerEvents = 'none';
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
}

/**
 * Hide tooltip
 */
function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Sales specific functions
function clearDraft() {
    if (typeof localStorage !== 'undefined') {
        localStorage.removeItem('sales_draft');
    }
}

// Enhanced validation
function validatePhoneNumber(phone) {
    const phoneRegex = /^[0-9]{10,11}$/;
    return phoneRegex.test(phone);
}

function validateCustomerName(name) {
    return name.trim().length >= 2;
}

// Price formatting
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}

// Quick customer search
function quickCustomerSearch(query) {
    // This could be enhanced to search via AJAX for larger customer databases
    return window.customers ? window.customers.filter(c => 
        c.name.toLowerCase().includes(query.toLowerCase()) ||
        c.phone.includes(query)
    ) : [];
}

// Stock management helpers
function checkLowStock(product) {
    return product.stock_quantity <= 5;
}

function isOutOfStock(product) {
    return product.stock_quantity <= 0;
}

// Cart summary
function getCartSummary() {
    if (!window.cartItems) return { items: 0, total: 0 };
    
    return {
        items: window.cartItems.length,
        total: window.cartItems.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0),
        totalQuantity: window.cartItems.reduce((sum, item) => sum + item.quantity, 0)
    };
}

/**
 * Fetches and displays the details of a specific import in a modal.
 * @param {number|string} importId The ID of the import to display.
 */
async function showImportDetailsModal(importId) {
    console.log('showImportDetailsModal called with importId:', importId); // DEBUG LINE 1

    if (!importId) {
        showToast('ID phiếu nhập không hợp lệ.', 'error');
        return;
    }

    try {
        // Optional: showLoading('main-content'); // Or a more specific loader

        const data = await apiRequest(`ajax/get_import_detail.php?id=${importId}`);
        console.log('Data received from ajax/get_import_detail.php:', data); // DEBUG LINE 2

        // Optional: hideLoading('main-content');

        if (data && data.import) {
            const importData = data.import;
            console.log('Import data object:', importData); // DEBUG LINE 3
            const productsDetails = data.details;

            let productsHtml = `
                <table class="table table-sm table-bordered mt-2" style="font-size: 0.85rem;">
                    <thead class="thead-light">
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Mã SP</th>
                            <th>SL</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>`;

            if (productsDetails && productsDetails.length > 0) {
                productsDetails.forEach(detail => {
                    productsHtml += `
                        <tr>
                            <td>${detail.product_name || 'N/A'}</td>
                            <td>${detail.product_code || 'N/A'}</td>
                            <td>${detail.quantity || 0}</td>
                            <td class="text-right">${detail.unit_price_formatted || '0'}đ</td>
                            <td class="text-right">${detail.total_price_formatted || '0'}đ</td>
                        </tr>`;
                });
            } else {
                productsHtml += '<tr><td colspan="5" class="text-center">Không có sản phẩm chi tiết.</td></tr>';
            }
            productsHtml += '</tbody></table>';

            const modalContent = `
                <div class="import-details-content" style="font-size: 0.9rem;">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Mã phiếu nhập:</strong> ${importData.import_code || 'N/A'}</p>
                            <p><strong>Ngày nhập:</strong> ${importData.created_at_formatted || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tổng tiền:</strong> <span class="text-danger font-weight-bold">${importData.total_amount_formatted || '0'}đ</span></p>
                            <p><strong>Trạng thái:</strong> ${importData.status || 'N/A'}</p>
                        </div>
                    </div>
                    <p class="mb-1"><strong>Ghi chú:</strong></p>
                    <p style="white-space: pre-wrap; background: #f9f9f9; padding: 8px; border-radius: 4px; min-height: 40px;">${importData.notes || 'Không có'}</p>
                    
                    <hr class="my-3">
                    <h5 class="mt-2 mb-2">Thông tin nhà cung cấp</h5>
                    <p><strong>Tên NCC:</strong> ${importData.supplier_name || 'Không xác định'}</p>
                    <p><strong>Điện thoại:</strong> ${importData.supplier_phone || 'Không có'}</p>
                    <p><strong>Địa chỉ:</strong> ${importData.supplier_address || 'Không có'}</p>
                    
                    <hr class="my-3">
                    <h5 class="mt-2 mb-2">Chi tiết sản phẩm nhập</h5>
                    ${productsHtml}
                </div>
            `;
            
            createModal(`Chi tiết phiếu nhập ${importData.import_code || ''}`, modalContent, null, `importDetail_${importId}`);
        } else {
            showToast(data.error || 'Không thể tải chi tiết phiếu nhập. Dữ liệu không hợp lệ.', 'error');
            console.error('Error or invalid data from server:', data); // DEBUG LINE 4
        }
    } catch (error) {
        // Optional: hideLoading('main-content');
        console.error('Lỗi khi tải chi tiết phiếu nhập (trong catch block):', error); // DEBUG LINE 5
        showToast('Lỗi khi tải chi tiết phiếu nhập: ' + (error.message || 'Unknown error'), 'error');
    }
}

// Performance optimization for large product lists
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Enhanced product search with debouncing
if (typeof searchProducts !== 'undefined') {
    window.searchProducts = debounce(window.searchProducts, 300);
}
