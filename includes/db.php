<?php
// Database helper functions

function getDatabase() {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

function initializeDatabase() {
    $db = getDatabase();

    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            google_id TEXT UNIQUE NOT NULL,
            email TEXT NOT NULL,
            name TEXT,
            picture TEXT,
            access_token TEXT,
            refresh_token TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create opportunities table
    $db->exec("
        CREATE TABLE IF NOT EXISTS opportunities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            email_subject TEXT NOT NULL,
            sender TEXT NOT NULL,
            classification TEXT NOT NULL,
            relevance_score INTEGER NOT NULL,
            company_name TEXT,
            email_date TEXT NOT NULL,
            email_snippet TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_user_id ON opportunities(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_classification ON opportunities(classification)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON opportunities(created_at)");

    return $db;
}

function getUserById($userId) {
    $db = getDatabase();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserByGoogleId($googleId) {
    $db = getDatabase();
    $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->execute([$googleId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createOrUpdateUser($googleId, $email, $name, $picture, $accessToken, $refreshToken = null) {
    $db = getDatabase();

    $existing = getUserByGoogleId($googleId);

    if ($existing) {
        // Update existing user
        $stmt = $db->prepare("
            UPDATE users
            SET email = ?, name = ?, picture = ?, access_token = ?, refresh_token = ?, last_login = CURRENT_TIMESTAMP
            WHERE google_id = ?
        ");
        $stmt->execute([$email, $name, $picture, $accessToken, $refreshToken ?: $existing['refresh_token'], $googleId]);
        return $existing['id'];
    } else {
        // Create new user
        $stmt = $db->prepare("
            INSERT INTO users (google_id, email, name, picture, access_token, refresh_token)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$googleId, $email, $name, $picture, $accessToken, $refreshToken]);
        return $db->lastInsertId();
    }
}

function insertOpportunity($userId, $subject, $sender, $classification, $score, $companyName, $emailDate, $snippet = '') {
    $db = getDatabase();
    $stmt = $db->prepare("
        INSERT INTO opportunities (user_id, email_subject, sender, classification, relevance_score, company_name, email_date, email_snippet)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $subject, $sender, $classification, $score, $companyName, $emailDate, $snippet]);
    return $db->lastInsertId();
}

function getOpportunitiesByUser($userId, $limit = 50) {
    $db = getDatabase();
    $stmt = $db->prepare("
        SELECT * FROM opportunities
        WHERE user_id = ? AND classification != 'ignore'
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Initialize database on first load
initializeDatabase();
