<?= page_head('Mi perfil', catalogo('roles')[$u['rol']] . ' · @' . $u['usuario']) ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body text-center">
        <span class="avatar avatar-lg mx-auto mb-3" style="width:72px;height:72px;font-size:1.4rem"><?= e(iniciales(nombre_usuario($u))) ?></span>
        <h2 class="h5 mb-1"><?= e(nombre_usuario($u)) ?></h2>
        <div class="text-muted small mb-2"><?= e($u['email']) ?></div>
        <span class="badge-rol <?= e($u['rol']) ?>"><?= e(catalogo('roles')[$u['rol']]) ?></span>
        <hr>
        <dl class="dl-grid text-start">
          <div><dt>Documento</dt><dd><?= e($u['tipo_documento'] . ' ' . $u['documento']) ?></dd></div>
          <?php if ($u['registro_medico']): ?><div><dt>Registro médico</dt><dd><?= e($u['registro_medico']) ?></dd></div><?php endif; ?>
          <?php if ($u['especialidad']): ?><div><dt>Especialidad</dt><dd><?= e($u['especialidad']) ?></dd></div><?php endif; ?>
          <div><dt>Último acceso</dt><dd><?= e(fecha($u['ultimo_acceso'], true)) ?></dd></div>
        </dl>
        <p class="form-text mb-0">Su documento, perfil y registro médico solo los puede cambiar un administrador.</p>
      </div>
    </div>

    <?php if ($u['rol'] === 'medico'): ?>
      <div class="card">
        <div class="card-header"><h2><i class="bi bi-pen"></i>Firma para los documentos</h2></div>
        <div class="card-body">
          <?php if ($firma): ?>
            <div class="border rounded p-3 mb-3 text-center bg-light"><img src="<?= $firma ?>" alt="Firma" style="max-height:90px;max-width:100%"></div>
            <form method="post" action="<?= url('perfil') ?>" class="mb-3" data-confirm="¿Eliminar la firma cargada?">
              <?= csrf_field() ?><input type="hidden" name="accion" value="quitar_firma">
              <button class="btn btn-sm btn-outline-danger w-100">Eliminar firma</button>
            </form>
          <?php else: ?>
            <p class="text-muted small">Aún no ha cargado su firma. Se imprimirá en historias clínicas, fórmulas, órdenes e incapacidades.</p>
          <?php endif; ?>
          <form method="post" action="<?= url('perfil') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?><input type="hidden" name="accion" value="firma">
            <label class="form-label" for="firma">Imagen de la firma (PNG con fondo transparente o JPG)</label>
            <input type="file" id="firma" name="firma" class="form-control mb-2" accept="image/png,image/jpeg" required>
            <button class="btn btn-primary w-100"><i class="bi bi-upload"></i> Cargar firma</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-person-gear"></i>Datos de contacto</h2></div>
      <div class="card-body">
        <form method="post" action="<?= url('perfil') ?>">
          <?= csrf_field() ?><input type="hidden" name="accion" value="datos">
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label" for="email">Correo electrónico</label>
              <input type="email" id="email" name="email" value="<?= e($u['email']) ?>" class="form-control" required>
              <div class="form-text">A este correo llega el enlace para restablecer la contraseña.</div>
            </div>
            <div class="col-md-5">
              <label class="form-label" for="telefono">Teléfono</label>
              <input id="telefono" name="telefono" value="<?= e($u['telefono']) ?>" class="form-control" maxlength="30">
            </div>
          </div>
          <div class="text-end mt-3"><button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar</button></div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2><i class="bi bi-shield-lock"></i>Cambiar contraseña</h2></div>
      <div class="card-body">
        <form method="post" action="<?= url('perfil') ?>">
          <?= csrf_field() ?><input type="hidden" name="accion" value="password">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="actual">Contraseña actual</label>
              <input type="password" id="actual" name="actual" class="form-control" required autocomplete="current-password">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="password">Nueva contraseña</label>
              <div class="input-group">
                <input type="password" id="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                <button class="btn btn-light border" type="button" data-toggle-password="password"><i class="bi bi-eye"></i></button>
              </div>
              <div class="form-text">Mínimo 8 caracteres con letras y números.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="password_confirm">Confirmar nueva contraseña</label>
              <input type="password" id="password_confirm" name="password_confirm" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
          </div>
          <div class="text-end mt-3"><button class="btn btn-primary"><i class="bi bi-key"></i> Cambiar contraseña</button></div>
        </form>
      </div>
    </div>
  </div>
</div>
