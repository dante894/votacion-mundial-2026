<?php
// config/database.php — SQLite para hosting gratuito (compatible con InfinityFree, 000webhost, etc.)
// Si el host soporta MySQL, cambiar a mysqli/PDO MySQL

define('DB_PATH', __DIR__ . '/../database/worldcup.db');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA foreign_keys=ON');
    return $pdo;
}

function initDB(): void {
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT,
            role TEXT DEFAULT 'user',
            oauth_provider TEXT,
            oauth_id TEXT,
            avatar TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS teams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            flag_code TEXT NOT NULL,
            group_name TEXT NOT NULL,
            fifa_name TEXT
        );

        CREATE TABLE IF NOT EXISTS matches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            home_team_id INTEGER NOT NULL,
            away_team_id INTEGER NOT NULL,
            stage TEXT NOT NULL DEFAULT 'groups',
            group_name TEXT,
            match_date DATETIME,
            vote_start DATETIME,
            vote_end DATETIME,
            home_score INTEGER,
            away_score INTEGER,
            status TEXT DEFAULT 'upcoming',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (home_team_id) REFERENCES teams(id),
            FOREIGN KEY (away_team_id) REFERENCES teams(id)
        );

        CREATE TABLE IF NOT EXISTS votes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            match_id INTEGER NOT NULL,
            team_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, match_id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (match_id) REFERENCES matches(id),
            FOREIGN KEY (team_id) REFERENCES teams(id)
        );

        CREATE TABLE IF NOT EXISTS sessions (
            id TEXT PRIMARY KEY,
            user_id INTEGER,
            data TEXT,
            expires_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ");
}
