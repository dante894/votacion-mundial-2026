<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET — obtener últimos mensajes
if ($method === 'GET') {
    $since = (int)($_GET['since'] ?? 0);
    $stmt  = $db->prepare(
        "SELECT id, user_name, user_avatar, message, created_at
         FROM chat_messages
         WHERE id > ?
         ORDER BY created_at ASC
         LIMIT 50"
    );
    $stmt->execute([$since]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'messages' => $messages]);
    exit;
}

// POST — guardar mensaje
if ($method === 'POST') {
    $user = getUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (empty($data['message'])) {
        $data = $_POST;
    }

    $message = trim($data['message'] ?? '');

    if ($message === '' || mb_strlen($message) > 300) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Mensaje inválido']);
        exit;
    }

    $stmt = $db->prepare(
        "INSERT INTO chat_messages (user_id, user_name, user_avatar, message)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([
        $user['id'],
        $user['name'],
        $user['avatar'] ?? null,
        $message,
    ]);

    echo json_encode(['ok' => true, 'id' => $db->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
