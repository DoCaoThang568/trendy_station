/**
 * Trendy Station - Main JavaScript File
 * Contains all common functions and utilities
 */

// Global variables
let currentPage = 'products';

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
