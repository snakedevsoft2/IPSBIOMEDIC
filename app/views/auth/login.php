<h1>Bienvenido</h1>
<p class="lead-sm">Ingrese con su usuario o correo electrónico.</p>

<?php if ($error): ?>
  <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= url('login') ?>" novalidate>
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="usuario">Usuario o correo</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-person"></i></span>
      <input type="text" id="usuario" name="usuario" class="form-control" value="<?= e($login) ?>" required autofocus autocomplete="username">
    </div>
  </div>
  <div class="mb-2">
    <label class="form-label" for="password">Contraseña</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock"></i></span>
      <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
      <button class="btn btn-light border" type="button" data-toggle-password="password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
    </div>
  </div>
  <div class="d-flex justify-content-end mb-4">
    <a href="<?= url('recuperar') ?>" class="small fw-medium">¿Olvidó su contraseña?</a>
  </div>
  <button class="btn btn-primary btn-lg w-100" type="submit">Ingresar <i class="bi bi-arrow-right"></i></button>
</form>

<p class="text-muted small mt-4 mb-0"><i class="bi bi-shield-lock me-1"></i>Acceso exclusivo para personal autorizado. La actividad queda registrada.</p>
