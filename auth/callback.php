<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/google-oauth.php';

if (!isset($_GET['code'])) {
    header('Location: ../index.php?error=no_code');
    exit;
}

try {
    $userData = handleGoogleCallback($_GET['code']);

    // Create or update user in database
    $userId = createOrUpdateUser(
        $userData['google_id'],
        $userData['email'],
        $userData['name'],
        $userData['picture'],
        $userData['access_token'],
        $userData['refresh_token']
    );

    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_name'] = $userData['name'];
    $_SESSION['user_picture'] = $userData['picture'];

    header('Location: ../dashboard.php?connected=true');
    exit;
} catch (Exception $e) {
    error_log('OAuth callback error: ' . $e->getMessage());
    header('Location: ../index.php?error=auth_failed');
    exit;
}
