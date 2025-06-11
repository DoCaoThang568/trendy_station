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
        
        // Ensure Vietnam timezone is used
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        
        $formatted = date('d/m/Y H:i:s', strtotime($dateString));
        
        // Restore original timezone
        date_default_timezone_set($originalTimezone);
        
        return $formatted;
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
        // Handle both English codes (legacy) and Vietnamese values (current database)
        switch (strtolower($method_code)) {
            case 'cash':
            case 'tiền mặt':
                return '💵 Tiền mặt';
            case 'card':
            case 'thẻ tín dụng':
                return '💳 Thẻ tín dụng';
            case 'transfer':
            case 'chuyển khoản':
                return '🏦 Chuyển khoản';
            case 'e-wallet':
            case 'ví điện tử':
                return '📱 Ví điện tử';
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
        // Return specific colors for each payment method
        switch (strtolower($method_code)) {
            case 'cash':
            case 'tiền mặt':
                return 'var(--success-color, #28a745)'; // Green for cash
            case 'card':
            case 'thẻ tín dụng':
                return 'var(--info-color, #17a2b8)'; // Blue for card
            case 'transfer':
            case 'chuyển khoản':
                return 'var(--purple-color, #6f42c1)'; // Purple for bank transfer
            case 'e-wallet':
            case 'ví điện tử':
                return 'var(--warning-color, #ffc107)'; // Yellow for e-wallet
            default:
                return 'var(--primary-color, #007bff)'; // Default blue
        }
    }
}

?>
