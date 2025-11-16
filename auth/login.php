<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/google-oauth.php';

// Redirect to Google OAuth
header('Location: ' . getAuthUrl());
exit;
