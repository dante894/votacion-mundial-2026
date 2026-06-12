<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
logoutUser();
header('Location: /worldcup2026/index.php');
exit;
