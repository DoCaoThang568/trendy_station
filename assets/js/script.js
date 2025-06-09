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
 * @param {string} content - Modal content
 * @param {function} onSave - Save callback
 * @return {string} Modal ID
 */
function createModal(title, content, onSave = null) {
    const modalId = 'modal_' + Date.now();
    const modalHtml = `
        <div id="${modalId}" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>${title}</h3>
                    <span class="close" onclick="closeModal('${modalId}')">&times;</span>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.getElementById(modalId).style.display = 'block';
    
    // Add save button if callback provided
    if (onSave) {
        const modalBody = document.querySelector(`#${modalId} .modal-body`);
        modalBody.insertAdjacentHTML('beforeend', `
            <div class="form-actions">
                <button class="btn btn-secondary" onclick="closeModal('${modalId}')">Hủy</button>
                <button class="btn btn-primary" onclick="handleModalSave('${modalId}')">Lưu</button>
            </div>
        `);
        
        // Store callback
        window[`saveCallback_${modalId}`] = onSave;
    }
    
    return modalId;
}

/**
 * Close modal
 * @param {string} modalId - Modal ID to close
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        setTimeout(() => modal.remove(), 300);
        
        // Clean up callback
        if (window[`saveCallback_${modalId}`]) {
            delete window[`saveCallback_${modalId}`];
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
            const href = this.getAttribute('href');
            if (href && href.length > 1 && href !== "#") { // Check if href is not just "#" and has more characters
                try {
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault(); // Only prevent default if it's a valid internal link
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                } catch (error) {
                    console.warn(`Smooth scroll failed for selector: ${href}`, error);
                    // Optionally, allow default behavior if querySelector fails for some other reason
                    // For example, if it's a link to another page that happens to start with #
                    // but in this context, href^="#" should mostly be internal.
                }
            } else if (href === "#") {
                e.preventDefault(); // Prevent default for href="#" to avoid jumping to top
                console.log('Anchor with href="#" clicked, default prevented.');
            }
            // If href is not valid or target not found, default browser behavior will apply (e.g., navigating to another page or doing nothing)
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
