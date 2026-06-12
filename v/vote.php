<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
if(!isLoggedIn()) { header('Location: ../login.php'); exit; }
if(!csrf_verify($_POST['csrf'] ?? '')) { die('CSRF error'); }

$matchId = (int)($_POST['match_id'] ?? 0);
$teamId  = (int)($_POST['team_id']  ?? 0);
$userId  = getUser()['id'];
$now     = date('Y-m-d H:i:s');

$db = getDB();

$match = $db->prepare("SELECT * FROM matches WHERE id=? AND vote_start<=? AND vote_end>=?");
$match->execute([$matchId, $now, $now]);
$m = $match->fetch();

if(!$m) {
    header('Location: ../index.php?error=closed');
    exit;
}

if($teamId != $m['home_team_id'] && $teamId != $m['away_team_id']) {
    header('Location: ../index.php?error=invalid');
    exit;
}

$existing = $db->prepare("SELECT id FROM votes WHERE user_id=? AND match_id=?");
$existing->execute([$userId, $matchId]);
if($existing->fetch()) {
    header('Location: ../index.php?error=already_voted');
    exit;
}

$db->prepare("INSERT INTO votes (user_id, match_id, team_id) VALUES (?,?,?)")
   ->execute([$userId, $matchId, $teamId]);

header('Location: ../index.php?voted=1#votar');
exit;
