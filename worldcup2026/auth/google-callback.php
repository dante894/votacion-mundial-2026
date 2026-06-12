<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

if (!$code || !csrf_verify($state)) {
    header('Location: /worldcup2026/login.php?error=oauth_failed');
    exit;
}

$user = handleGoogleCallback($code);

if (!$user) {
    header('Location: /worldcup2026/login.php?error=oauth_failed');
    exit;
}

loginUser($user);

$redirect = $_SESSION['oauth_redirect'] ?? '/worldcup2026/index.php';
unset($_SESSION['oauth_redirect']);
header('Location: ' . $redirect);
exit;
