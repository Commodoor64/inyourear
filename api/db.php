<?php

function db(): PDO {

    static $pdo = null;
    if ($pdo !== null) return $pdo;

    
    $path = getenv('POSTCARD_DB') ?: '/home2/urmvvkte/app_data/postcards.db';
    

    $pdo = new PDO('sqlite:' . $path);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec('PRAGMA journal_mode = WAL;');



    $pdo->exec("
        CREATE TABLE IF NOT EXISTS postcards (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            author      TEXT NOT NULL DEFAULT 'unknown',
            message     TEXT NOT NULL DEFAULT '',
            image_path  TEXT NOT NULL,
            closer      TEXT NOT NULL DEFAULT 'Love',
            closerColor      TEXT NOT NULL DEFAULT 'red',
            created_at  TEXT NOT NULL DEFAULT (datetime('now'))
        );
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS presence (
        username   TEXT PRIMARY KEY,
        last_seen  INTEGER NOT NULL
    );
");

    // $pdo->exec("
    //     CREATE TABLE IF NOT EXISTS postcards (
    //         id          INTEGER PRIMARY KEY AUTOINCREMENT,
    //         author      TEXT NOT NULL DEFAULT 'unknown',
    //         message     TEXT NOT NULL DEFAULT '',
    //         created_at  TEXT NOT NULL DEFAULT (datetime('now'))
    //     );
    // ");

    return $pdo;
}