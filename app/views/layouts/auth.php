<?php $ips = setting('ips_nombre', 'IPS BIOMED'); ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($title ?? 'Ingreso') . ' · ' . $ips) ?></title>
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/app.css?v=<?= APP_VERSION ?>" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="auth-wrap">
    <section class="auth-brand">
      <img src="<?= e(logo_url('oscuro')) ?>" alt="<?= e($ips) ?>" class="auth-logo">
      <div class="auth-copy">
        <span class="auth-kicker">Medicina general</span>
        <h2>Historia clínica electrónica</h2>
        <p>Admisión de pacientes, atención médica, fórmulas e informes de la IPS en un solo lugar.</p>
        <ul class="auth-points">
          <li><i class="bi bi-person-check"></i> Recepción registra y el médico continúa la atención</li>
          <li><i class="bi bi-file-earmark-medical"></i> Historia clínica y fórmula médica en PDF</li>
          <li><i class="bi bi-shield-lock"></i> Acceso por perfiles con registro de auditoría</li>
        </ul>
      </div>
      <small class="auth-legal">© <?= date('Y') ?> <?= e($ips) ?></small>
    </section>

    <section class="auth-panel">
      <div class="auth-card">
        <img src="<?= e(logo_url('claro')) ?>" alt="<?= e($ips) ?>" class="auth-logo-mobile">
        <?php foreach (flashes() as [$type, $msg]): ?>
          <div class="alert alert-<?= e($type) ?> py-2 small"><?= e($msg) ?></div>
        <?php endforeach; ?>
        <?= $content ?>
      </div>
    </section>
  </div>
  <script src="assets/js/app.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
