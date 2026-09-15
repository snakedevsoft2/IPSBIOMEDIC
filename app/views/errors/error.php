<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/app.css?v=<?= APP_VERSION ?>" rel="stylesheet">
</head>
<body class="error-body">
  <div class="error-card">
    <img src="assets/img/logo-color.png" alt="Logo" class="mb-4" style="height:44px">
    <div class="error-code"><?= (int) $code ?></div>
    <h1><?= e($title) ?></h1>
    <p><?= e($message) ?></p>
    <div class="d-flex gap-2 justify-content-center">
      <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
      <a href="index.php" class="btn btn-primary"><i class="bi bi-house"></i> Ir al inicio</a>
    </div>
  </div>
</body>
</html>
