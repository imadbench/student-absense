<?php
/**
 * Common Utility Functions
 */

// Ensure no output before JSON responses
if (ob_get_level()) {
    ob_clean();
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Generate JSON response
 */
function jsonResponse($success, $data = [], $message = '') {
    // Clean any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Handle file upload
 */
function handleFileUpload($file, $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $errorMsg = $errorMessages[$file['error']] ?? 'Unknown upload error';
        return ['success' => false, 'error' => $errorMsg];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed size'];
    }
    
    // Validate file extension and MIME type
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    // Additional MIME type validation
    $allowedMimeTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimeTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Generate secure filename
    $fileName = 'justif_' . uniqid() . '_' . time() . '.' . $extension;
    $filePath = UPLOAD_DIR . $fileName;
    
    // Ensure upload directory exists and is secure
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Protect against directory traversal
    $filePath = realpath(UPLOAD_DIR) . DIRECTORY_SEPARATOR . basename($fileName);
    if (strpos($filePath, realpath(UPLOAD_DIR)) !== 0) {
        return ['success' => false, 'error' => 'Invalid file path'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_path' => $fileName, 'full_path' => $filePath];
    }
    
    return ['success' => false, 'error' => 'Failed to save file'];
}

/**
 * Delete file
 */
function deleteFile($fileName) {
    $filePath = UPLOAD_DIR . $fileName;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}

?>