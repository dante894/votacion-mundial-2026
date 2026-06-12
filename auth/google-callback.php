<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
if (empty($_GET['code'])) { header('Location: ../login.php?error=no_code'); exit; }
$user = handleGoogleCallback($_GET['code']);
if (!$user) { header('Location: ../login.php?error=oauth_failed'); exit; }
loginUser($user);
header('Location: ../index.php');
exit;