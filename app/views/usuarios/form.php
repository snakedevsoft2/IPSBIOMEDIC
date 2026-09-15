<?php
$val = static fn (string $k): string => e($data[$k] ?? '');
$inv = static fn (string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$fb = static fn (string $k): string => isset($errors[$k]) ? '<div class="invalid-feedback">' . e($errors[$k]) . '</div>' : '';
$editando = $usuario !== null;
echo page_head($editando ? 'Editar usuario' : 'Nuevo usuario', $editando ? nombre_usuario($usuario) . ' · @' . $usuario['usuario'] : 'Cree el acceso para un integrante del equipo');
?>
<form method="post" action="<?= url($editando ? 'usuarios/editar' : 'usuarios/crear', $editando ? ['id' => $usuario['id']] : []) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header"><h2><i class="bi bi-person"></i>Datos personales</h2></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Tipo de documento</label>
              <select name="tipo_documento" class="form-select"><?= options(catalogo('tipo_documento'), $data['tipo_documento'] ?? 'CC', '') ?></select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Documento<span class="req">*</span></label>
              <input name="documento" value="<?= $val('documento') ?>" class="form-control<?= $inv('documento') ?>" maxlength="20"><?= $fb('documento') ?>
            </div>
            <div class="col-md-4">
              <label class="form-label">Teléfono</label>
              <input name="telefono" value="<?= $val('telefono') ?>" class="form-control" maxlength="30">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nombres<span class="req">*</span></label>
              <input name="nombres" value="<?= $val('nombres') ?>" class="form-control<?= $inv('nombres') ?>" maxlength="100"><?= $fb('nombres') ?>
            </div>
            <div class="col-md-6">
              <label class="form-label">Apellidos<span class="req">*</span></label>
              <input name="apellidos" value="<?= $val('apellidos') ?>" class="form-control<?= $inv('apellidos') ?>" maxlength="100"><?= $fb('apellidos') ?>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><h2><i class="bi bi-key"></i>Acceso</h2></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Usuario<span class="req">*</span></label>
              <input name="usuario" value="<?= $val('usuario') ?>" class="form-control<?= $inv('usuario') ?>" maxlength="50" autocomplete="off"><?= $fb('usuario') ?>
            </div>
            <div class="col-md-6">
              <label class="form-label">Correo electrónico<span class="req">*</span></label>
              <input type="email" name="email" value="<?= $val('email') ?>" class="form-control<?= $inv('email') ?>" maxlength="150"><?= $fb('email') ?>
              <div class="form-text">Se usa para recuperar la contraseña.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label"><?= $editando ? 'Nueva contraseña (opcional)' : 'Contraseña' ?><?= $editando ? '' : '<span class="req">*</span>' ?></label>
              <div class="input-group">
                <input type="password" id="password" name="password" class="form-control<?= $inv('password') ?>" autocomplete="new-password">
                <button class="btn btn-light border" type="button" data-toggle-password="password"><i class="bi bi-eye"></i></button>
              </div>
              <?= $fb('password') ?>
              <div class="form-text">Mínimo 8 caracteres con letras y números.<?= $editando ? ' Déjela vacía para no cambiarla.' : '' ?></div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirmar contraseña</label>
              <input type="password" name="password_confirm" class="form-control" autocomplete="new-password">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header"><h2><i class="bi bi-person-badge"></i>Perfil y permisos</h2></div>
        <div class="card-body">
          <label class="form-label">Perfil<span class="req">*</span></label>
          <select name="rol" class="form-select<?= $inv('rol') ?>"><?= options(catalogo('roles'), $data['rol'] ?? 'medico', '') ?></select>
          <?= $fb('rol') ?>
          <ul class="form-text mt-2 ps-3 mb-3">
            <li><strong>Administrador:</strong> todo el sistema, usuarios y configuración.</li>
            <li><strong>Médico:</strong> sala de espera, historia clínica, fórmulas e informes.</li>
            <li><strong>Recepción:</strong> registro de pacientes y admisiones (no ve historias clínicas).</li>
            <li><strong>Auxiliar:</strong> consulta de pacientes e historias, sin editar.</li>
          </ul>

          <label class="form-label">Especialidad</label>
          <input name="especialidad" value="<?= $val('especialidad') ?>" class="form-control mb-3" maxlength="100" placeholder="Medicina general">

          <label class="form-label">Registro médico</label>
          <input name="registro_medico" value="<?= $val('registro_medico') ?>" class="form-control<?= $inv('registro_medico') ?>" maxlength="50">
          <?= $fb('registro_medico') ?>
          <div class="form-text mb-3">Obligatorio para médicos: se imprime en fórmulas y certificados.</div>

          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= (int) ($data['activo'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Usuario activo</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="notificar" name="notificar" value="1" <?= $notificar ? 'checked' : '' ?>>
            <label class="form-check-label" for="notificar">Enviar correo de bienvenida</label>
          </div>
        </div>
      </div>

      <div class="d-grid gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> <?= $editando ? 'Guardar cambios' : 'Crear usuario' ?></button>
        <a class="btn btn-light border" href="<?= url('usuarios') ?>">Cancelar</a>
      </div>
    </div>
  </div>
</form>
