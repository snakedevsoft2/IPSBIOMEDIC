<a href="<?= url('login') ?>" class="small d-inline-flex align-items-center gap-1 mb-3"><i class="bi bi-arrow-left"></i> Volver al ingreso</a>
<h1>Recuperar contraseña</h1>

<?php if ($enviado): ?>
  <div class="alert alert-success mt-3">
    <i class="bi bi-envelope-check me-1"></i>
    Si el correo <strong><?= e($email) ?></strong> está registrado, recibirá un enlace para crear una nueva contraseña en los próximos minutos. Revise también la carpeta de spam.
  </div>
  <p class="text-muted small">El enlace vence en 60 minutos. ¿No llegó? Espere unos minutos e intente de nuevo o comuníquese con el administrador de la IPS.</p>
<?php else: ?>
  <p class="lead-sm">Escriba el correo electrónico asociado a su usuario y le enviaremos un enlace para restablecerla.</p>
  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="post" action="<?= url('recuperar') ?>">
    <?= csrf_field() ?>
    <div class="mb-4">
      <label class="form-label" for="email">Correo electrónico</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
        <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" required autofocus autocomplete="email">
      </div>
    </div>
    <button class="btn btn-primary btn-lg w-100" type="submit">Enviar enlace</button>
  </form>
<?php endif; ?>
