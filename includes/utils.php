<?php
/**
 * Utility functions
 */

/**
 * Sanitize input data
 * 
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_input($value);
        }
        return $data;
    }
    
    // Trim whitespace
    $data = trim($data);
    
    // Remove backslashes
    $data = stripslashes($data);
    
    // Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Validate required fields
 * 
 * @param array $data Data to validate
 * @param array $required_fields Required field names
 * @return array Array of missing fields, empty if all fields are present
 */
function validate_required_fields($data, $required_fields) {
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    return $missing_fields;
}

/**
 * Generate a random string
 * 
 * @param int $length Length of the string
 * @return string Random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $random_string;
}

/**
 * Format date for display
 * 
 * @param string $date Date string in ISO format
 * @param string $format PHP date format
 * @return string Formatted date
 */
function format_date($date, $format = 'M j, Y g:i A') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Get current page URL
 * 
 * @return string Current page URL
 */
function get_current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    return $protocol . '://' . $host . $uri;
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @param int $status HTTP status code
 */
function redirect($url, $status = 302) {
    header('Location: ' . $url, true, $status);
    exit;
}

/**
 * Set a flash message
 * 
 * @param string $type Message type (success, error, info, warning)
 * @param string $message Message content
 */
function set_flash_message($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_messages'][$type][] = $message;
}

/**
 * Get flash messages
 * 
 * @param string $type Message type (success, error, info, warning)
 * @param bool $clear Whether to clear messages after retrieval
 * @return array Array of messages
 */
function get_flash_messages($type = null, $clear = true) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['flash_messages'])) {
        return [];
    }
    
    if ($type) {
        $messages = isset($_SESSION['flash_messages'][$type]) ? $_SESSION['flash_messages'][$type] : [];
        
        if ($clear) {
            unset($_SESSION['flash_messages'][$type]);
        }
        
        return $messages;
    }
    
    $messages = $_SESSION['flash_messages'];
    
    if ($clear) {
        unset($_SESSION['flash_messages']);
    }
    
    return $messages;
}

/**
 * Check if a string starts with a specific substring
 * 
 * @param string $haystack The string to search in
 * @param string $needle The substring to search for
 * @return bool True if haystack starts with needle, false otherwise
 */
function starts_with($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Check if a string ends with a specific substring
 * 
 * @param string $haystack The string to search in
 * @param string $needle The substring to search for
 * @return bool True if haystack ends with needle, false otherwise
 */
function ends_with($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}

/**
 * Truncate a string to a specified length
 * 
 * @param string $string String to truncate
 * @param int $length Maximum length
 * @param string $append String to append if truncated
 * @return string Truncated string
 */
function truncate_string($string, $length = 100, $append = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    
    $string = substr($string, 0, $length);
    $string = rtrim($string);
    
    return $string . $append;
}

/**
 * Convert a string to slug format
 * 
 * @param string $string String to convert
 * @return string Slug
 */
function slugify($string) {
    // Replace non-alphanumeric characters with hyphens
    $string = preg_replace('/[^a-z0-9]+/i', '-', $string);
    // Convert to lowercase
    $string = strtolower($string);
    // Remove leading and trailing hyphens
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Get human-readable file size
 * 
 * @param int $bytes Size in bytes
 * @param int $precision Number of decimal places
 * @return string Human-readable size
 */
function format_file_size($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Generate pagination links
 * 
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $url_pattern URL pattern with %d placeholder for page number
 * @param int $links_count Number of links to show
 * @return array Array of pagination links
 */
function generate_pagination_links($current_page, $total_pages, $url_pattern, $links_count = 5) {
    $links = [];
    
    // Always include first page
    $links[] = [
        'page' => 1,
        'url' => sprintf($url_pattern, 1),
        'current' => ($current_page == 1)
    ];
    
    // Calculate start and end pages
    $start = max(2, $current_page - floor($links_count / 2));
    $end = min($total_pages - 1, $current_page + floor($links_count / 2));
    
    // Adjust start and end to maintain links_count
    if ($end - $start + 1 < $links_count) {
        if ($start == 2) {
            $end = min($total_pages - 1, $end + ($links_count - ($end - $start + 1)));
        } elseif ($end == $total_pages - 1) {
            $start = max(2, $start - ($links_count - ($end - $start + 1)));
        }
    }
    
    // Add ellipsis after first page if needed
    if ($start > 2) {
        $links[] = [
            'page' => '...',
            'url' => null,
            'current' => false
        ];
    }
    
    // Add middle pages
    for ($i = $start; $i <= $end; $i++) {
        $links[] = [
            'page' => $i,
            'url' => sprintf($url_pattern, $i),
            'current' => ($current_page == $i)
        ];
    }
    
    // Add ellipsis before last page if needed
    if ($end < $total_pages - 1) {
        $links[] = [
            'page' => '...',
            'url' => null,
            'current' => false
        ];
    }
    
    // Always include last page
    if ($total_pages > 1) {
        $links[] = [
            'page' => $total_pages,
            'url' => sprintf($url_pattern, $total_pages),
            'current' => ($current_page == $total_pages)
        ];
    }
    
    return $links;
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function get_client_ip() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * Check if request is AJAX
 * 
 * @return bool True if AJAX request, false otherwise
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Send JSON response
 * 
 * @param mixed $data Data to send
 * @param int $status HTTP status code
 */
function send_json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get base URL
 * 
 * @return string Base URL
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_dir = dirname($script_name);
    
    if ($base_dir == '/' || $base_dir == '\\') {
        $base_dir = '';
    }
    
    return $protocol . '://' . $host . $base_dir;
}

/**
 * Get asset URL
 * 
 * @param string $path Asset path
 * @return string Asset URL
 */
function get_asset_url($path) {
    return get_base_url() . '/assets/' . ltrim($path, '/');
}