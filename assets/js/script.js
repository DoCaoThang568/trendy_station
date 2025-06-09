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
        return;
    }
    if (modalElement.escapeKeyListener) {
        document.removeEventListener('keydown', modalElement.escapeKeyListener);
        delete modalElement.escapeKeyListener;
    }
    if (immediate) {
        if (modalElement.parentNode) modalElement.parentNode.removeChild(modalElement);
        return;
    }
    modalElement.classList.remove('show');
    setTimeout(() => {
        if (modalElement.parentNode) modalElement.parentNode.removeChild(modalElement);
    }, 300);
}

/**
 * View Return Detail
 * @param {number} returnId - The ID of the return slip.
 */
function viewReturnDetail(returnId) {
    if (!returnId) {
        showToast('ID phiếu trả hàng không hợp lệ.', 'error');
        return;
    }
    fetch(`ajax/get_return_detail.php?id=${returnId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showToast(data.message || 'Không thể tải chi tiết.', 'error');
                return;
            }
            const { return_info: info, return_items: items } = data;
            let itemsHtml = `<table class="detail-table"><thead><tr><th>STT</th><th>Sản phẩm</th><th>Mã SP</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead><tbody>`;
            items.forEach((item, i) => {
                itemsHtml += `<tr>
                    <td>${i + 1}</td>
                    <td>${item.product_name}</td>
                    <td>${item.product_code}</td>
                    <td>${item.quantity}</td>
                    <td>${formatCurrency(item.unit_price)}</td>
                    <td>${formatCurrency(item.quantity * item.unit_price)}</td>
                </tr>`;
            });
            itemsHtml += `</tbody></table>`;
            const content = `
                <div class="return-detail-modal-content">
                    <p><strong>Mã phiếu:</strong> ${info.return_code || 'N/A'}</p>
                    <p><strong>Ngày:</strong> ${formatDate(info.return_date)}</p>
                    <p><strong>Lý do:</strong> ${info.reason}</p>
                    <p><strong>Tổng hoàn:</strong> <span class="text-danger fw-bold">${formatCurrency(info.total_refund)}</span></p>
                    <p><strong>HĐ gốc:</strong> ${info.sale_code || 'N/A'}</p>
                    <hr>
                    <p><strong>Khách hàng:</strong> ${info.customer_name || 'Khách lẻ'}</p>
                    <p><strong>SĐT:</strong> ${info.customer_phone || 'N/A'}</p>
                    <hr>
                    <h4>Sản phẩm trả</h4>
                    ${itemsHtml}
                </div>
                <style>
                    .return-detail-modal-content p { margin-bottom: 0.3rem; font-size:0.9rem; }
                    .detail-table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; font-size:0.85rem; }
                    .detail-table th, .detail-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                    .detail-table th { background-color: #f2f2f2; }
                    .text-danger { color: #dc3545; }
                    .fw-bold { font-weight: bold; }
                </style>`;
            createModal(`Chi tiết Phiếu Trả #R${info.return_id}`, content, null, 'returnDetailModal');
        })
        .catch(error => {
            console.error('Err fetch return details:', error);
            showToast('Lỗi tải chi tiết phiếu trả.', 'error');
        });
}