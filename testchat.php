<?php
require_once __DIR__ . '/includes/bootstrap.php';
$db = getDB();

// Crear tabla
$db->exec("CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    user_avatar VARCHAR(500) DEFAULT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "<h2>Test Chat</h2>";

// Ver si la tabla existe
$tables = $db->query("SHOW TABLES LIKE 'chat_messages'")->fetchAll();
echo "<p>Tabla chat_messages: " . (count($tables) > 0 ? '✅ existe' : '❌ no existe') . "</p>";

// Insertar mensaje de prueba
$user = getUser();
if ($user) {
    $db->prepare("INSERT INTO chat_messages (user_id, user_name, user_avatar, message) VALUES (?,?,?,?)")
       ->execute([$user['id'], $user['name'], $user['avatar'], 'Mensaje de prueba ⚽']);
    echo "<p style='color:green'>✅ Mensaje insertado correctamente</p>";
} else {
    echo "<p style='color:orange'>⚠️ No estás logueado, no se insertó mensaje</p>";
}

// Ver mensajes
$msgs = $db->query("SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
echo "<p>Mensajes en BD: " . count($msgs) . "</p>";
foreach($msgs as $m) {
    echo "<p>- [{$m['id']}] {$m['user_name']}: {$m['message']}</p>";
}
