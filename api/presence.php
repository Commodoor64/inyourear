<?php
// api/presence.php — heartbeat in; presence AND last-seen out.
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
header('Content-Type: application/json');

$me     = current_user();
$now    = time();
$window = 20;

// WRITE: I'm here now (upsert my row).
db()->prepare("
    INSERT INTO presence (username, last_seen) VALUES (?, ?)
    ON CONFLICT(username) DO UPDATE SET last_seen = excluded.last_seen
")->execute([$me, $now]);

// READ: everyone's rows, with how long ago each was seen.
$rows = db()->query(
    'SELECT username, last_seen FROM presence ORDER BY last_seen DESC'
)->fetchAll(PDO::FETCH_ASSOC);

$people = array_map(function ($r) use ($now, $window) {
    $ago = $now - (int)$r['last_seen'];
    return [
        'username'    => $r['username'],
        'last_seen'   => (int)$r['last_seen'],
        'seconds_ago' => $ago,
        'online'      => $ago < $window,
    ];
}, $rows);

echo json_encode([
    'me'     => $me,
    'people' => $people,
]);