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

// Who sent it? Prefer the Basic Auth username; fall back to what's posted.
// (PHP_AUTH_USER is sometimes empty on shared hosting — hence the fallback.)
$author = $_SERVER['PHP_AUTH_USER']
       ?? $_SERVER['REMOTE_USER']
       ?? ($_POST['author'] ?? 'unknown');


// Prepared statement — parameters are never concatenated into the SQL.
$stmt = db()->prepare(
    'INSERT INTO postcards (author, message) VALUES (?, ?)'
);
$stmt->execute([$author, $message]);

echo json_encode(['ok' => true, 'id' => db()->lastInsertId()]);
echo $author . " " . $message;

