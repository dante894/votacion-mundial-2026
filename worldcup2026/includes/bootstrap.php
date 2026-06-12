<?php
// includes/bootstrap.php — incluir en cada página

define('ROOT', dirname(__DIR__));

require_once ROOT . '/config/oauth.php';
require_once ROOT . '/includes/database.php';
require_once ROOT . '/includes/teams_data.php';
require_once ROOT . '/includes/auth.php';

// Inicializar BD y datos base en primera ejecución
initDB();
seedTeams();
seedAdmin();

startSecureSession();
