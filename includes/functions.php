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

?>
