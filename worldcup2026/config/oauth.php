<?php
// config/oauth.php
// 1. Ir a https://console.cloud.google.com/
// 2. Crear proyecto → APIs & Services → Credentials → OAuth 2.0 Client ID
// 3. Authorized redirect URIs: https://tudominio.com/auth/google-callback.php

define('GOOGLE_CLIENT_ID',     'TU_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'TU_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  'http://localhost/worldcup2026/auth/google-callback.php');

// Cambiar a tu dominio en producción:
// define('GOOGLE_REDIRECT_URI', 'https://tudominio.com/auth/google-callback.php');

define('APP_NAME', 'FIFA World Cup 2026');
define('APP_URL',  'http://localhost/worldcup2026');
