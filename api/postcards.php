<?php
// api/postcards.php — returns the grid of cards as an HTML fragment.
require __DIR__ . '/db.php';

$rows = db()
              ->query('SELECT id, author, message, image_path, closer, closerColor, created_at
             FROM postcards ORDER BY id DESC LIMIT 200')
    ->fetchAll(PDO::FETCH_ASSOC);

echo '<button id="new-card" class="card new-card" type="button">+ new note</button>';

if (!$rows) {
    echo '<p class="empty">No notes yet — make the first one.</p>';
    return;
}

foreach ($rows as $r) {
    // htmlspecialchars on EVERYTHING that came from a user — this is what
    // stops a message containing <script> from running in the other's browser.
    $img = $r['image_path'] ? '<img src="' . htmlspecialchars($r['image_path'], ENT_QUOTES) . '">' : '';
    $msg    = nl2br(htmlspecialchars($r['message'], ENT_QUOTES));
    $author = htmlspecialchars($r['author'], ENT_QUOTES);
    $when   = htmlspecialchars($r['created_at'], ENT_QUOTES);
    $closerColor = htmlspecialchars($r['closerColor'], ENT_QUOTES);

    echo '<figure class="card" data-id="' . (int)$r['id'] . '">'
    . '<div class="card-inner">'
    .   '<div class="card-front">' . $img . '</div>'
    .   '<div class="card-back">'
    .     '<p class="msg">' . $msg . '</p>'
    .     '<div class="signed">'
    .       '<p class="closer ' . $closerColor . '">' . htmlspecialchars($r['closer'], ENT_QUOTES) . ', ' . '</p>'
    .       '<p class="author">' . $author . '</p>'
    .       '<p class="when">' . $when . '</p>'
    .     '</div>'
    .   '</div>'
    . '</div>'
    . '</figure>';
}

