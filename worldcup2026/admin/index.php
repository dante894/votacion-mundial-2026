<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requireAdmin();

$db   = getDB();
$user = getUser();
$msg  = '';

// ── Acciones POST ───────────────────────────────────────────────────────
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_verify($_POST['csrf'] ?? '')) die('CSRF');

    $action = $_POST['action'] ?? '';

    if($action === 'create_match') {
        $db->prepare("INSERT INTO matches (home_team_id, away_team_id, stage, group_name, match_date, vote_start, vote_end, status) VALUES (?,?,?,?,?,?,?,'upcoming')")
           ->execute([
               (int)$_POST['home_team_id'],
               (int)$_POST['away_team_id'],
               $_POST['stage'],
               $_POST['group_name'] ?: null,
               $_POST['match_date'] ?: null,
               $_POST['vote_start'],
               $_POST['vote_end'],
           ]);
        $msg = '✅ Partido creado correctamente';
    }

    if($action === 'update_score') {
        $db->prepare("UPDATE matches SET home_score=?, away_score=?, status=? WHERE id=?")
           ->execute([(int)$_POST['home_score'], (int)$_POST['away_score'], $_POST['status'], (int)$_POST['match_id']]);
        $msg = '✅ Resultado actualizado';
    }

    if($action === 'delete_match') {
        $id = (int)$_POST['match_id'];
        $db->prepare("DELETE FROM votes   WHERE match_id=?")->execute([$id]);
        $db->prepare("DELETE FROM matches WHERE id=?")->execute([$id]);
        $msg = '🗑 Partido eliminado';
    }
}

// ── Datos ───────────────────────────────────────────────────────────────
$teams = $db->query("SELECT * FROM teams ORDER BY group_name, name")->fetchAll();

$matches = $db->query("
    SELECT m.*,
           ht.name AS home_name, ht.flag_code AS home_flag,
           at.name AS away_name, at.flag_code AS away_flag,
           (SELECT COUNT(*) FROM votes v WHERE v.match_id=m.id) AS total_votes,
           (SELECT COUNT(*) FROM votes v WHERE v.match_id=m.id AND v.team_id=m.home_team_id) AS home_votes,
           (SELECT COUNT(*) FROM votes v WHERE v.match_id=m.id AND v.team_id=m.away_team_id) AS away_votes
    FROM matches m
    JOIN teams ht ON ht.id=m.home_team_id
    JOIN teams at ON at.id=m.away_team_id
    ORDER BY m.created_at DESC
")->fetchAll();

$users      = $db->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$totalVotes = $db->query("SELECT COUNT(*) FROM votes")->fetchColumn();

$stageLabels = [
    'groups'=>'Fase de Grupos','round32'=>'Dieciseisavos','round16'=>'Octavos',
    'quarterfinals'=>'Cuartos','semifinals'=>'Semifinal','final'=>'Final'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — World Cup 2026</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#050810;--surface:#0a0e14;--card:#0f1520;--border:#1a2333;--gold:#f0b429;--green:#22c55e;--blue:#3b82f6;--red:#ef4444;--text:#dde4ef;--muted:#4a5a6a;--radius:12px}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh}
a{color:inherit;text-decoration:none}

/* NAV */
nav{display:flex;align-items:center;justify-content:space-between;padding:14px 28px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:50}
.nav-title{font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:2px;color:var(--gold)}
.nav-right{display:flex;align-items:center;gap:14px;font-size:13px;color:var(--muted)}
.nav-right a{color:var(--muted);transition:color .2s}.nav-right a:hover{color:var(--text)}

/* LAYOUT */
.layout{display:flex;min-height:calc(100vh - 53px)}
aside{width:220px;flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);padding:20px 0}
.aside-link{display:flex;align-items:center;gap:10px;padding:10px 20px;font-size:13px;font-weight:500;color:var(--muted);transition:all .15s;cursor:pointer;border:none;background:none;width:100%;text-align:left}
.aside-link:hover,.aside-link.active{color:var(--text);background:rgba(255,255,255,.04)}
.aside-link .icon{font-size:16px;width:20px;text-align:center}
main{flex:1;padding:28px;overflow-x:auto}

/* TABS */
.tab{display:none}.tab.active{display:block}
.stats-row{display:flex;gap:14px;margin-bottom:24px;flex-wrap:wrap}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 22px;flex:1;min-width:140px}
.stat-card .val{font-family:'Bebas Neue',sans-serif;font-size:36px;color:var(--gold)}
.stat-card .lbl{font-size:12px;color:var(--muted);margin-top:2px}

/* MSG */
.msg{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);border-radius:10px;padding:12px 16px;font-size:13px;color:var(--green);margin-bottom:20px}

/* FORM */
.form-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px}
.form-card h3{font-size:15px;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border)}
.form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.field label{display:block;font-size:11px;font-weight:600;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px}
.field input,.field select{width:100%;padding:9px 12px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;font-family:inherit;outline:none;transition:border .2s}
.field input:focus,.field select:focus{border-color:rgba(240,180,41,.4)}
.field select option{background:var(--card)}
.btn{padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:opacity .2s}
.btn:hover{opacity:.85}
.btn-primary{background:linear-gradient(135deg,var(--gold),#d4911e);color:#000}
.btn-danger{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:var(--red)}
.btn-sm{padding:5px 12px;font-size:12px}

/* TABLE */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}
thead tr{background:rgba(255,255,255,.03);border-bottom:1px solid var(--border)}
th{padding:10px 12px;text-align:left;font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;white-space:nowrap}
td{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
tr:hover td{background:rgba(255,255,255,.02)}

/* flag */
.flag-sm{width:26px;height:17px;object-fit:cover;border-radius:2px;vertical-align:middle;margin-right:6px}
.vs-cell{display:flex;align-items:center;gap:6px;white-space:nowrap}

/* badge */
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600}
.badge-open{background:rgba(34,197,94,.15);color:var(--green)}
.badge-closed{background:rgba(90,100,110,.15);color:var(--muted)}
.badge-upcoming{background:rgba(59,130,246,.15);color:var(--blue)}

/* vote bar */
.mini-bar{height:4px;border-radius:99px;background:rgba(255,255,255,.08);overflow:hidden;margin-top:4px;min-width:80px}
.mini-fill{height:100%;background:linear-gradient(90deg,var(--blue),var(--green));border-radius:99px}
</style>
</head>
<body>

<nav>
  <div class="nav-title">⚙ ADMIN — WORLD CUP 2026</div>
  <div class="nav-right">
    <span>👤 <?= htmlspecialchars($user['name']) ?></span>
    <a href="../index.php">← Ver sitio</a>
    <a href="../auth/logout.php">Salir</a>
  </div>
</nav>

<div class="layout">
<aside>
  <button class="aside-link active" onclick="showTab('dashboard')"><span class="icon">📊</span>Dashboard</button>
  <button class="aside-link" onclick="showTab('matches')"><span class="icon">⚽</span>Partidos</button>
  <button class="aside-link" onclick="showTab('create')"><span class="icon">➕</span>Crear Partido</button>
  <button class="aside-link" onclick="showTab('users')"><span class="icon">👥</span>Usuarios</button>
</aside>

<main>
<?php if($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

<!-- DASHBOARD -->
<div class="tab active" id="tab-dashboard">
  <h2 style="font-size:20px;font-weight:700;margin-bottom:20px">Dashboard</h2>
  <div class="stats-row">
    <div class="stat-card"><div class="val"><?= count($matches) ?></div><div class="lbl">Partidos creados</div></div>
    <div class="stat-card"><div class="val"><?= $totalVotes ?></div><div class="lbl">Votos totales</div></div>
    <div class="stat-card"><div class="val"><?= count($users) ?></div><div class="lbl">Usuarios registrados</div></div>
    <div class="stat-card"><div class="val"><?= count(array_filter($matches, fn($m)=>$m['vote_end']>=date('Y-m-d H:i:s')&&$m['vote_start']<=date('Y-m-d H:i:s'))) ?></div><div class="lbl">Votaciones abiertas</div></div>
  </div>

  <div class="form-card">
    <h3>🏆 Partidos con más votos</h3>
    <table>
      <thead><tr><th>Partido</th><th>Votos</th><th>Local</th><th>Visitante</th></tr></thead>
      <tbody>
      <?php $top = array_slice(usort($matches, fn($a,$b)=>$b['total_votes']<=>$a['total_votes']) ? $matches : $matches, 0, 5); ?>
      <?php foreach(array_slice($matches,0,5) as $m):
        $total=(int)$m['total_votes']; $hp=$total>0?round($m['home_votes']/$total*100):50; ?>
        <tr>
          <td class="vs-cell">
            <img class="flag-sm" src="https://flagcdn.com/w40/<?=$m['home_flag']?>.png" onerror="this.src='https://flagcdn.com/w40/un.png'">
            <?=htmlspecialchars($m['home_name'])?> vs
            <img class="flag-sm" src="https://flagcdn.com/w40/<?=$m['away_flag']?>.png" onerror="this.src='https://flagcdn.com/w40/un.png'">
            <?=htmlspecialchars($m['away_name'])?>
          </td>
          <td><?=$total?></td>
          <td><?=$hp?>%<div class="mini-bar"><div class="mini-fill" style="width:<?=$hp?>%"></div></div></td>
          <td><?=100-$hp?>%</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- PARTIDOS -->
<div class="tab" id="tab-matches">
  <h2 style="font-size:20px;font-weight:700;margin-bottom:20px">Partidos (<?=count($matches)?>)</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Partido</th><th>Etapa</th><th>Estado</th><th>Votos</th><th>Fecha partido</th><th>Voto cierre</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach($matches as $m):
        $now = date('Y-m-d H:i:s');
        $open = $m['vote_start'] <= $now && $m['vote_end'] >= $now;
        $total=(int)$m['total_votes']; $hp=$total>0?round($m['home_votes']/$total*100):50;
      ?>
        <tr>
          <td class="vs-cell">
            <img class="flag-sm" src="https://flagcdn.com/w40/<?=$m['home_flag']?>.png" onerror="this.src='https://flagcdn.com/w40/un.png'">
            <?=htmlspecialchars($m['home_name'])?>
            <span style="color:var(--muted)">vs</span>
            <img class="flag-sm" src="https://flagcdn.com/w40/<?=$m['away_flag']?>.png" onerror="this.src='https://flagcdn.com/w40/un.png'">
            <?=htmlspecialchars($m['away_name'])?>
            <?php if($m['home_score']!==null): ?>
              <span style="color:var(--gold);font-weight:700;margin-left:4px"><?=$m['home_score']?>-<?=$m['away_score']?></span>
            <?php endif; ?>
          </td>
          <td><?=$stageLabels[$m['stage']]??$m['stage']?></td>
          <td>
            <?php if($open): ?>
              <span class="badge badge-open">🟢 Abierta</span>
            <?php elseif($m['vote_end'] < $now): ?>
              <span class="badge badge-closed">⚫ Cerrada</span>
            <?php else: ?>
              <span class="badge badge-upcoming">🔵 Próxima</span>
            <?php endif; ?>
          </td>
          <td>
            <?=$total?> votos
            <?php if($total>0): ?>
              <div class="mini-bar" style="min-width:60px"><div class="mini-fill" style="width:<?=$hp?>%"></div></div>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--muted)"><?=$m['match_date']?date('d/m/Y H:i',strtotime($m['match_date'])):'-'?></td>
          <td style="font-size:12px;color:var(--muted)"><?=date('d/m H:i',strtotime($m['vote_end']))?></td>
          <td>
            <div style="display:flex;gap:6px;align-items:center">
              <!-- Score form -->
              <form method="POST" style="display:flex;gap:4px;align-items:center">
                <input type="hidden" name="action" value="update_score">
                <input type="hidden" name="match_id" value="<?=$m['id']?>">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="number" name="home_score" value="<?=$m['home_score']??''?>" placeholder="0" style="width:44px;padding:4px 6px;font-size:12px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:6px;color:var(--text);text-align:center">
                <span style="color:var(--muted);font-size:11px">-</span>
                <input type="number" name="away_score" value="<?=$m['away_score']??''?>" placeholder="0" style="width:44px;padding:4px 6px;font-size:12px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:6px;color:var(--text);text-align:center">
                <select name="status" style="padding:4px 6px;font-size:12px;background:var(--card);border:1px solid var(--border);border-radius:6px;color:var(--text)">
                  <option value="upcoming" <?=$m['status']==='upcoming'?'selected':''?>>Próximo</option>
                  <option value="live" <?=$m['status']==='live'?'selected':''?>>En vivo</option>
                  <option value="finished" <?=$m['status']==='finished'?'selected':''?>>Finalizado</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">✓</button>
              </form>
              <!-- Delete -->
              <form method="POST" onsubmit="return confirm('¿Eliminar partido y todos sus votos?')">
                <input type="hidden" name="action" value="delete_match">
                <input type="hidden" name="match_id" value="<?=$m['id']?>">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <button type="submit" class="btn btn-danger btn-sm">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- CREAR PARTIDO -->
<div class="tab" id="tab-create">
  <h2 style="font-size:20px;font-weight:700;margin-bottom:20px">Crear Partido para Votar</h2>
  <div class="form-card">
    <h3>⚽ Nuevo Partido</h3>
    <form method="POST">
      <input type="hidden" name="action" value="create_match">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <div class="form-grid">
        <div class="field">
          <label>Equipo Local 🏠</label>
          <select name="home_team_id" required>
            <option value="">Seleccionar...</option>
            <?php foreach($teams as $t): ?>
              <option value="<?=$t['id']?>">[<?=$t['group_name']?>] <?=htmlspecialchars($t['name'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Equipo Visitante ✈</label>
          <select name="away_team_id" required>
            <option value="">Seleccionar...</option>
            <?php foreach($teams as $t): ?>
              <option value="<?=$t['id']?>">[<?=$t['group_name']?>] <?=htmlspecialchars($t['name'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Etapa</label>
          <select name="stage" required>
            <?php foreach($stageLabels as $k=>$v): ?>
              <option value="<?=$k?>"><?=$v?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Grupo (si aplica)</label>
          <select name="group_name">
            <option value="">— Sin grupo —</option>
            <?php foreach(range('A','L') as $g): ?>
              <option value="<?=$g?>">Grupo <?=$g?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Fecha del Partido</label>
          <input type="datetime-local" name="match_date">
        </div>
        <div class="field">
          <label>Inicio de Votación ★</label>
          <input type="datetime-local" name="vote_start" required>
        </div>
        <div class="field">
          <label>Cierre de Votación ★</label>
          <input type="datetime-local" name="vote_end" required>
        </div>
      </div>
      <div style="margin-top:20px">
        <button type="submit" class="btn btn-primary">➕ Crear Partido</button>
      </div>
    </form>
  </div>

  <!-- Preview de banderas en tiempo real -->
  <div class="form-card" id="preview-card" style="display:none">
    <h3>👁 Vista previa</h3>
    <div style="display:flex;align-items:center;gap:20px;padding:8px 0">
      <div style="text-align:center"><img id="prev-home-flag" src="" style="width:60px;height:40px;object-fit:cover;border-radius:6px"><div id="prev-home-name" style="font-size:12px;margin-top:4px"></div></div>
      <div style="font-size:24px;color:var(--muted)">VS</div>
      <div style="text-align:center"><img id="prev-away-flag" src="" style="width:60px;height:40px;object-fit:cover;border-radius:6px"><div id="prev-away-name" style="font-size:12px;margin-top:4px"></div></div>
    </div>
  </div>
</div>

<!-- USUARIOS -->
<div class="tab" id="tab-users">
  <h2 style="font-size:20px;font-weight:700;margin-bottom:20px">Usuarios (<?=count($users)?>)</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Registro</th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
        <tr>
          <td style="color:var(--muted)"><?=$u['id']?></td>
          <td><?=htmlspecialchars($u['name'])?></td>
          <td style="color:var(--muted)"><?=htmlspecialchars($u['email'])?></td>
          <td>
            <span class="badge <?=$u['role']==='admin'?'badge-open':'badge-upcoming'?>">
              <?=$u['role']==='admin'?'⚙ Admin':'👤 Usuario'?>
            </span>
          </td>
          <td style="font-size:12px;color:var(--muted)"><?=date('d/m/Y',strtotime($u['created_at']))?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</main>
</div>

<script>
function showTab(name) {
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.aside-link').forEach(l=>l.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  event.currentTarget.classList.add('active');
}

// Preview de banderas en crear partido
const flagMap = <?php
$map = [];
foreach($teams as $t) $map[$t['id']] = ['name'=>$t['name'],'flag'=>$t['flag_code']];
echo json_encode($map);
?>;

function updatePreview() {
  const hId = document.querySelector('[name=home_team_id]')?.value;
  const aId = document.querySelector('[name=away_team_id]')?.value;
  const card = document.getElementById('preview-card');
  if(!hId || !aId) { card.style.display='none'; return; }
  const h = flagMap[hId], a = flagMap[aId];
  if(h && a) {
    document.getElementById('prev-home-flag').src = `https://flagcdn.com/w80/${h.flag}.png`;
    document.getElementById('prev-away-flag').src = `https://flagcdn.com/w80/${a.flag}.png`;
    document.getElementById('prev-home-name').textContent = h.name;
    document.getElementById('prev-away-name').textContent = a.name;
    card.style.display = 'block';
  }
}
document.querySelector('[name=home_team_id]')?.addEventListener('change', updatePreview);
document.querySelector('[name=away_team_id]')?.addEventListener('change', updatePreview);
</script>
</body>
</html>
