<?php
// OpportunityOS Configuration

// Database configuration
define('DB_PATH', __DIR__ . '/../database/opportunityos.db');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost:8000/auth/callback.php');

// Anthropic Claude API Configuration
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: '');

// Perplexity API Configuration
define('PERPLEXITY_API_KEY', getenv('PERPLEXITY_API_KEY') ?: '');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
