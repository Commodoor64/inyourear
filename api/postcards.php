<?php
// api/postcards.php — returns the grid of cards as an HTML fragment.
require __DIR__ . '/db.php';

$rows = db()
    ->query('SELECT author, message, created_at
             FROM postcards ORDER BY id DESC LIMIT 200')
    ->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo '<p class="empty">No postcards yet — make the first one.</p>';
    return;
}

foreach ($rows as $r) {
    // htmlspecialchars on EVERYTHING that came from a user — this is what
    // stops a message containing <script> from running in the other's browser.
    $msg    = nl2br(htmlspecialchars($r['message'], ENT_QUOTES));
    $author = htmlspecialchars($r['author'], ENT_QUOTES);
    $when   = htmlspecialchars($r['created_at'], ENT_QUOTES);

    echo '<figure class="card">'
       . '<figcaption><p class="msg">' . $msg . '</p>'
       . '<p class="meta">— ' . $author . ' · ' . $when . '</p></figcaption>'
       . '</figure>';
}