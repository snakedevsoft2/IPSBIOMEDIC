<?php if (!$valido): ?>
  <h1>Enlace no válido</h1>
  <p class="lead-sm">El enlace para restablecer la contraseña expiró o ya fue utilizado.</p>
  <a href="<?= url('recuperar') ?>" class="btn btn-primary btn-lg w-100">Solicitar un nuevo enlace</a>
  <a href="<?= url('login') ?>" class="btn btn-link w-100 mt-2">Volver al ingreso</a>
<?php else: ?>
  <h1>Nueva contraseña</h1>
  <p class="lead-sm">Hola <?= e($reset['nombres']) ?>, cree una nueva contraseña para el usuario <strong><?= e($reset['usuario']) ?></strong>.</p>
  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="post" action="<?= url('restablecer', ['token' => $token]) ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label" for="password">Nueva contraseña</label>
      <div class="input-group">
        <input type="password" id="password" name="password" class="form-control" required minlength="8" autocomplete="new-password" autofocus>
        <button class="btn btn-light border" type="button" data-toggle-password="password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
      </div>
      <div class="form-text">Mínimo 8 caracteres, con letras y números.</div>
    </div>
    <div class="mb-4">
      <label class="form-label" for="password_confirm">Confirmar contraseña</label>
      <input type="password" id="password_confirm" name="password_confirm" class="form-control" required minlength="8" autocomplete="new-password">
    </div>
    <button class="btn btn-primary btn-lg w-100" type="submit">Guardar contraseña</button>
  </form>
<?php endif; ?>
