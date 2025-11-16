<?php
// Google OAuth Helper Functions

function getGoogleClient() {
    require_once __DIR__ . '/../vendor/autoload.php';

    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->addScope("email");
    $client->addScope("profile");
    $client->addScope("https://www.googleapis.com/auth/gmail.readonly");
    $client->setAccessType('offline');
    $client->setPrompt('consent');

    return $client;
}

function getAuthUrl() {
    $client = getGoogleClient();
    return $client->createAuthUrl();
}

function handleGoogleCallback($code) {
    $client = getGoogleClient();
    $token = $client->fetchAccessTokenWithAuthCode($code);

    if (isset($token['error'])) {
        throw new Exception('Error fetching access token: ' . $token['error']);
    }

    $client->setAccessToken($token);

    // Get user info
    $oauth = new Google_Service_Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    $refreshToken = isset($token['refresh_token']) ? $token['refresh_token'] : null;

    return [
        'google_id' => $userInfo->id,
        'email' => $userInfo->email,
        'name' => $userInfo->name,
        'picture' => $userInfo->picture,
        'access_token' => $token['access_token'],
        'refresh_token' => $refreshToken
    ];
}

function refreshAccessToken($refreshToken) {
    $client = getGoogleClient();
    $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

    if (isset($token['error'])) {
        throw new Exception('Error refreshing token: ' . $token['error']);
    }

    return $token['access_token'];
}
