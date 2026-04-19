<?php
require_once 'config/init.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if it's an image upload request
if ($_POST['action'] !== 'upload_image' || !isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$file = $_FILES['image'];

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
    exit;
}

// Check file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.']);
    exit;
}

// Check file size (5MB max)
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit;
}

// Create upload directory if it doesn't exist
$uploadDir = 'uploads/editor/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Optionally resize image if it's too large
    $resizedPath = resizeImageIfNeeded($filepath, 1200, 800); // Max 1200x800
    
    // Save to database for tracking
    $query = "INSERT INTO uploaded_images (filename, original_name, file_path, file_size, mime_type, uploaded_by, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssisi', $filename, $file['name'], $resizedPath, $file['size'], $mimeType, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    echo json_encode([
        'success' => true,
        'url' => $resizedPath,
        'filename' => $filename,
        'original_name' => $file['name']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}

/**
 * Resize image if it exceeds maximum dimensions
 */
function resizeImageIfNeeded($filepath, $maxWidth = 1200, $maxHeight = 800) {
    $imageInfo = getimagesize($filepath);
    if (!$imageInfo) {
        return $filepath; // Not an image or corrupted
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    // Check if resize is needed
    if ($width <= $maxWidth && $height <= $maxHeight) {
        return $filepath; // No resize needed
    }
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filepath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($filepath);
            break;
        default:
            return $filepath; // Unsupported type
    }
    
    if (!$source) {
        return $filepath;
    }
    
    // Create new image
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Generate new filename for resized image
    $pathInfo = pathinfo($filepath);
    $resizedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_resized.' . $pathInfo['extension'];
    
    // Save resized image
    $saved = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $saved = imagejpeg($resized, $resizedPath, 85);
            break;
        case IMAGETYPE_PNG:
            $saved = imagepng($resized, $resizedPath, 8);
            break;
        case IMAGETYPE_GIF:
            $saved = imagegif($resized, $resizedPath);
            break;
        case IMAGETYPE_WEBP:
            $saved = imagewebp($resized, $resizedPath, 85);
            break;
    }
    
    // Clean up
    imagedestroy($source);
    imagedestroy($resized);
    
    if ($saved) {
        // Remove original file and use resized version
        unlink($filepath);
        return $resizedPath;
    }
    
    return $filepath;
}
?>