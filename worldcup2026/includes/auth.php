<?php
// includes/auth.php

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['samesite' => 'Lax', 'httponly' => true]);
        session_start();
    }
}

function currentUser(): ?array {
    startSecureSession();
    if (empty($_SESSION['user_id'])) return null;
    $db = getDB();
    return $db->prepare("SELECT * FROM users WHERE id=?")->execute([$_SESSION['user_id']])
           ? $db->prepare("SELECT * FROM users WHERE id=?")->execute([$_SESSION['user_id']]) && false
           : null;
}

function getUser(): ?array {
    startSecureSession();
    if (empty($_SESSION['user_id'])) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function isAdmin(): bool {
    $user = getUser();
    return $user && $user['role'] === 'admin';
}

function isLoggedIn(): bool {
    return getUser() !== null;
}

function loginUser(array $user): void {
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
}

function logoutUser(): void {
    startSecureSession();
    session_destroy();
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: /index.php');
        exit;
    }
}

// ── Google OAuth ────────────────────────────────────────────────────────
// Configurar en config/oauth.php con CLIENT_ID y CLIENT_SECRET de Google Cloud Console
function getGoogleAuthUrl(): string {
    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'access_type'   => 'online',
        'state'         => csrf_token(),
    ]);
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
}

function handleGoogleCallback(string $code): ?array {
    // Intercambiar code por token
    $tokenRes = httpPost('https://oauth2.googleapis.com/token', [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]);

    if (empty($tokenRes['access_token'])) return null;

    // Obtener datos del usuario
    $userInfo = httpGet('https://www.googleapis.com/oauth2/v3/userinfo', $tokenRes['access_token']);
    if (empty($userInfo['email'])) return null;

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$userInfo['email']]);
    $user = $stmt->fetch();

    if (!$user) {
        $db->prepare("INSERT INTO users (name, email, oauth_provider, oauth_id, avatar) VALUES (?,?,?,?,?)")
           ->execute([$userInfo['name'], $userInfo['email'], 'google', $userInfo['sub'], $userInfo['picture'] ?? null]);
        $stmt->execute([$userInfo['email']]);
        $user = $stmt->fetch();
    } else {
        // Actualizar avatar si cambió
        $db->prepare("UPDATE users SET avatar=?, oauth_id=? WHERE id=?")
           ->execute([$userInfo['picture'] ?? $user['avatar'], $userInfo['sub'], $user['id']]);
    }

    return $user;
}

function httpPost(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

function httpGet(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

function csrf_token(): string {
    startSecureSession();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(string $token): bool {
    return hash_equals($_SESSION['csrf'] ?? '', $token);
}
