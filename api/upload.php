<?php
// api/upload.php — receives one postcard and stores it.
require __DIR__ . '/db.php';
header('Content-Type: application/json');

// Only POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Message: trim and cap length.
$message = trim($_POST['message'] ?? '');
if (mb_strlen($message) > 1000) {
    http_response_code(422);
    echo json_encode(['error' => 'Message too long']);
    exit;
}

// Image: check if uploaded
$image = $_FILES['image'] ?? null;
$imagePath = null;
if ($image && $image['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $ext;
    $imagePath = $uploadDir . $filename;
    if (move_uploaded_file($image['tmp_name'], $imagePath)) {
        $imagePath = 'uploads/' . $filename; // relative path for DB
    }
    
}

// Who sent it? Prefer the Basic Auth username; fall back to what's posted.
// (PHP_AUTH_USER is sometimes empty on shared hosting — hence the fallback.)
$author = $_SERVER['PHP_AUTH_USER']
       ?? $_SERVER['REMOTE_USER']
       ?? ($_POST['author'] ?? 'unknown');

$closer = trim($_POST['closer'] ?? '');
$closerColor = trim($_POST['closerColor'] ?? '');
// Prepared statement — parameters are never concatenated into the SQL.
$stmt = db()->prepare(
    'INSERT INTO postcards (author, message, image_path, closer, closerColor) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$author, $message, $imagePath, $closer, $closerColor]);

echo json_encode(['ok' => true, 'id' => db()->lastInsertId()]);
echo $author . " " . $message . " " . $imagePath;

