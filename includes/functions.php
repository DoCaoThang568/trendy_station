<?php

if (!function_exists('formatDate')) {
    /**
     * Formats a date string to d/m/Y H:i format.
     * 
     * @param string $dateString The date string to format.
     * @return string The formatted date.
     */
    function formatDate($dateString) {
        if (empty($dateString) || $dateString === '0000-00-00 00:00:00') {
            return ''; // Or some other placeholder for invalid/empty dates
        }
        return date('d/m/Y H:i', strtotime($dateString));
    }
}

// Add other global utility functions here if needed

if (!function_exists('translatePaymentStatus')) {
    /**
     * Translates payment status to Vietnamese.
     *
     * @param string $status The payment status.
     * @return string The translated status.
     */
    function translatePaymentStatus($status) {
        switch ($status) {
            case 'Đã thanh toán':
                return 'Đã thanh toán';
            case 'Chưa thanh toán':
                return 'Chưa thanh toán';
            case 'Đã hủy':
                return 'Đã hủy';
            case 'Đang xử lý':
                return 'Đang xử lý';
            default:
                return ucfirst($status);
        }
    }
}

if (!function_exists('getStatusColor')) {
    /**
     * Gets a color based on the status.
     *
     * @param string $status The status.
     * @return string The hex color code.
     */
    function getStatusColor($status) {
        switch ($status) {
            case 'Đã thanh toán':
                return 'var(--success-color)'; // Green
            case 'Chưa thanh toán':
                return 'var(--warning-color)'; // Orange
            case 'Đã hủy':
                return 'var(--danger-color)';  // Red
            case 'Đang xử lý':
                return 'var(--info-color)';    // Blue
            default:
                return 'var(--text-secondary)'; // Grey
        }
    }
}

if (!function_exists('translatePaymentMethod')) {
    /**
     * Translates payment method codes to user-friendly names.
     *
     * @param string $method_code The payment method code (e.g., 'cash', 'card', 'transfer').
     * @return string The translated payment method name.
     */
    function translatePaymentMethod($method_code) {
        switch (strtolower($method_code)) {
            case 'cash':
                return '💵 Tiền mặt';
            case 'card':
                return '💳 Thẻ';
            case 'transfer':
                return '🏦 Chuyển khoản';
            default:
                return ucfirst($method_code); // Fallback for unknown methods
        }
    }
}

if (!function_exists('getPaymentMethodStatusColor')) {
    /**
     * Gets a color based on the payment method (can be expanded or merged with getStatusColor).
     *
     * @param string $method_code The payment method code.
     * @return string The CSS variable for color.
     */
    function getPaymentMethodStatusColor($method_code) {
        // For now, let's use a generic success color for all completed payments,
        // or you can define specific colors per payment method.
        return 'var(--success-color)'; 
    }
}

?>
