<?php
$u = user();
$actual = ruta_actual();
[$totalEspera, $espera] = notificaciones_espera();
$ips = setting('ips_nombre', 'IPS BIOMED');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title><?= e(($title ?? 'Inicio') . ' · ' . $ips) ?></title>
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/app.css?v=<?= APP_VERSION ?>" rel="stylesheet">
</head>
<body>

<header class="topnav">
  <div class="topnav-inner">
    <a class="brand" href="<?= url('dashboard') ?>" aria-label="Inicio">
      <img src="<?= e(logo_url('claro')) ?>" alt="<?= e($ips) ?>">
    </a>

    <nav class="mainnav" id="mainNav">
      <?php foreach (menu_items() as $item):
          $roles = $item[3];
          if ($roles !== '*' && !in_array($u['rol'], $roles, true)) continue;
          if (isset($item[4])):
              $childRoutes = array_column($item[4], 0);
              $isActive = in_array($actual, $childRoutes, true); ?>
          <div class="dropdown">
            <a href="#" class="nav-link-b dropdown-toggle<?= $isActive ? ' active' : '' ?>" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi <?= $item[2] ?>"></i><?= e($item[1]) ?>
            </a>
            <ul class="dropdown-menu shadow-sm">
              <?php foreach ($item[4] as $child): ?>
                <li><a class="dropdown-item<?= $actual === $child[0] ? ' active' : '' ?>" href="<?= url($child[0]) ?>"><i class="bi <?= $child[2] ?>"></i><?= e($child[1]) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= url($item[0]) ?>" class="nav-link-b<?= $actual === $item[0] ? ' active' : '' ?>">
            <i class="bi <?= $item[2] ?>"></i><?= e($item[1]) ?>
          </a>
        <?php endif; endforeach; ?>
    </nav>

    <div class="topnav-actions">
      <div class="dropdown">
        <button class="icon-btn" data-bs-toggle="dropdown" aria-label="Pacientes en espera" title="Pacientes en espera">
          <i class="bi bi-bell"></i>
          <?php if ($totalEspera > 0): ?><span class="notif-count"><?= $totalEspera > 99 ? '99+' : $totalEspera ?></span><?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end notif-menu shadow">
          <div class="notif-head">
            <strong>Sala de espera</strong>
            <span class="text-muted small"><?= $totalEspera ?> paciente(s)</span>
          </div>
          <?php if (!$espera): ?>
            <div class="notif-empty"><i class="bi bi-check2-circle"></i> No hay pacientes esperando.</div>
          <?php endif; ?>
          <?php foreach ($espera as $n): ?>
            <a class="notif-item" href="<?= url('admisiones') ?>">
              <span class="avatar avatar-sm"><?= e(iniciales(nombre_paciente($n))) ?></span>
              <span class="flex-grow-1 text-truncate">
                <span class="d-block text-truncate fw-medium"><?= e(nombre_paciente($n)) ?></span>
                <small class="text-muted">Llegó <?= date('h:i a', strtotime($n['fecha_hora'])) ?></small>
              </span>
              <?php if ($n['prioridad'] === 'Prioritaria'): ?><span class="tag-rojo">Prioritaria</span><?php endif; ?>
            </a>
          <?php endforeach; ?>
          <a class="notif-foot" href="<?= url('admisiones') ?>">Ver sala de espera <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>

      <div class="dropdown">
        <button class="user-chip" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="avatar"><?= e(iniciales(nombre_usuario($u))) ?></span>
          <span class="user-chip-text d-none d-xl-block">
            <strong><?= e($u['nombres']) ?></strong>
            <small><?= e(catalogo('roles')[$u['rol']] ?? $u['rol']) ?></small>
          </span>
          <i class="bi bi-chevron-down small text-muted d-none d-xl-inline"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li class="px-3 py-2 border-bottom mb-1">
            <div class="fw-semibold"><?= e(nombre_usuario($u)) ?></div>
            <small class="text-muted"><?= e($u['email']) ?></small>
          </li>
          <li><a class="dropdown-item" href="<?= url('perfil') ?>"><i class="bi bi-person-circle"></i>Mi perfil</a></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form method="post" action="<?= url('logout') ?>">
              <?= csrf_field() ?>
              <button class="dropdown-item text-rojo" type="submit"><i class="bi bi-box-arrow-right"></i>Cerrar sesión</button>
            </form>
          </li>
        </ul>
      </div>

      <button class="icon-btn d-lg-none" type="button" data-toggle-nav aria-label="Menú"><i class="bi bi-list"></i></button>
    </div>
  </div>
</header>

<main class="page">
  <?php foreach (flashes() as [$type, $msg]): ?>
    <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
      <?= e($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  <?php endforeach; ?>
  <?= $content ?>
</main>

<footer class="app-footer">
  <span><?= e($ips) ?><?= setting('ips_nit') ? ' · NIT ' . e(setting('ips_nit')) : '' ?></span>
  <span>Historia clínica electrónica · v<?= APP_VERSION ?> · Desarrollado por Snakedev</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js?v=<?= APP_VERSION ?>"></script>
<?= implode("\n", $GLOBALS['_scripts'] ?? []) ?>
</body>
</html>
