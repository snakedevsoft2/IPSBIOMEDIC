<?php
$tabs = ['ips' => 'Datos de la IPS', 'logos' => 'Logos', 'correo' => 'Correo (SMTP)', 'seguridad' => 'Seguridad', 'eps' => 'EPS / aseguradoras'];
$v = static fn (string $k, string $def = ''): string => e($s[$k] ?? $def);
echo page_head('Configuración', 'Datos que aparecen en la aplicación y en los reportes PDF');
?>
<nav class="nav-tabs-b">
  <?php foreach ($tabs as $k => $label): ?>
    <a class="<?= $tab === $k ? 'active' : '' ?>" href="<?= url('configuracion', ['tab' => $k]) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($tab === 'ips'): ?>
  <form method="post" action="<?= url('configuracion/guardar') ?>">
    <?= csrf_field() ?><input type="hidden" name="seccion" value="ips">
    <div class="card">
      <div class="card-header"><h2><i class="bi bi-building"></i>Identificación de la institución</h2></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nombre o razón social<span class="req">*</span></label><input name="ips_nombre" value="<?= $v('ips_nombre') ?>" class="form-control" maxlength="150" required></div>
          <div class="col-md-3"><label class="form-label">NIT</label><input name="ips_nit" value="<?= $v('ips_nit') ?>" class="form-control" maxlength="30"></div>
          <div class="col-md-3"><label class="form-label">Código de habilitación (REPS)</label><input name="ips_codigo_habilitacion" value="<?= $v('ips_codigo_habilitacion') ?>" class="form-control" maxlength="30"></div>
          <div class="col-md-6"><label class="form-label">Dirección</label><input name="ips_direccion" value="<?= $v('ips_direccion') ?>" class="form-control" maxlength="150"></div>
          <div class="col-md-3"><label class="form-label">Ciudad</label><input name="ips_ciudad" value="<?= $v('ips_ciudad') ?>" class="form-control" maxlength="100"></div>
          <div class="col-md-3"><label class="form-label">Teléfono</label><input name="ips_telefono" value="<?= $v('ips_telefono') ?>" class="form-control" maxlength="50"></div>
          <div class="col-md-6"><label class="form-label">Correo institucional</label><input type="email" name="ips_email" value="<?= $v('ips_email') ?>" class="form-control" maxlength="150"></div>
          <div class="col-md-6"><label class="form-label">Sitio web</label><input name="ips_web" value="<?= $v('ips_web') ?>" class="form-control" maxlength="150" placeholder="https://ipsbiomed.com"></div>
          <div class="col-12">
            <label class="form-label">Texto legal al pie de los PDF</label>
            <input name="pie_reportes" value="<?= $v('pie_reportes', 'Documento confidencial sujeto a reserva legal (Ley 23 de 1981 y Resolución 1995 de 1999).') ?>" class="form-control" maxlength="200">
          </div>
        </div>
      </div>
      <div class="card-footer text-end"><button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar</button></div>
    </div>
  </form>

<?php elseif ($tab === 'logos'): ?>
  <form method="post" action="<?= url('configuracion/guardar') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="seccion" value="logos">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header"><h2><i class="bi bi-image"></i>Logo para fondo claro</h2></div>
          <div class="card-body">
            <p class="text-muted small">Se usa en la barra superior y en todos los reportes PDF.</p>
            <div class="border rounded p-3 mb-3 text-center bg-white"><img src="<?= e(logo_url('claro')) ?>" alt="Logo claro" style="max-height:70px;max-width:100%"></div>
            <input type="file" name="logo_claro" class="form-control" accept="image/png,image/jpeg">
            <?php if (($s['logo_claro'] ?? '') !== ''): ?>
              <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="q1" name="quitar_logo_claro" value="1"><label class="form-check-label small" for="q1">Volver al logo original</label></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header"><h2><i class="bi bi-image-fill"></i>Logo para fondo oscuro</h2></div>
          <div class="card-body">
            <p class="text-muted small">Se usa en la pantalla de ingreso y en los correos.</p>
            <div class="border rounded p-3 mb-3 text-center" style="background:#0f2a4a"><img src="<?= e(logo_url('oscuro')) ?>" alt="Logo oscuro" style="max-height:70px;max-width:100%"></div>
            <input type="file" name="logo_oscuro" class="form-control" accept="image/png,image/jpeg">
            <?php if (($s['logo_oscuro'] ?? '') !== ''): ?>
              <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="q2" name="quitar_logo_oscuro" value="1"><label class="form-check-label small" for="q2">Volver al logo original</label></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="text-end mt-3"><button class="btn btn-primary"><i class="bi bi-upload"></i> Guardar logos</button></div>
  </form>

<?php elseif ($tab === 'correo'): ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <form method="post" action="<?= url('configuracion/guardar') ?>">
        <?= csrf_field() ?><input type="hidden" name="seccion" value="correo">
        <div class="card">
          <div class="card-header"><h2><i class="bi bi-envelope-gear"></i>Servidor de correo saliente</h2></div>
          <div class="card-body">
            <p class="text-muted small">Necesario para que los usuarios puedan recuperar su contraseña. En Hostinger cree un correo (ej. <em>no-reply@ipsbiomed.com</em>) y use <strong>smtp.hostinger.com</strong>, puerto <strong>465</strong> con SSL.</p>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Servidor SMTP</label><input name="smtp_host" value="<?= $v('smtp_host') ?>" class="form-control" placeholder="smtp.hostinger.com"></div>
              <div class="col-md-3"><label class="form-label">Puerto</label><input name="smtp_puerto" value="<?= $v('smtp_puerto', '465') ?>" class="form-control" inputmode="numeric"></div>
              <div class="col-md-3"><label class="form-label">Seguridad</label><select name="smtp_seguridad" class="form-select"><?= options(['ssl' => 'SSL (465)', 'tls' => 'STARTTLS (587)', 'none' => 'Sin cifrado'], $s['smtp_seguridad'] ?? 'ssl', '') ?></select></div>
              <div class="col-md-6"><label class="form-label">Usuario</label><input name="smtp_usuario" value="<?= $v('smtp_usuario') ?>" class="form-control" autocomplete="off"></div>
              <div class="col-md-6"><label class="form-label">Contraseña</label><input type="password" name="smtp_clave" class="form-control" autocomplete="new-password" placeholder="<?= ($s['smtp_clave'] ?? '') !== '' ? '•••••••• (guardada)' : '' ?>"><div class="form-text">Déjela vacía para conservar la actual.</div></div>
              <div class="col-md-6"><label class="form-label">Remitente</label><input name="smtp_remitente" value="<?= $v('smtp_remitente') ?>" class="form-control" placeholder="no-reply@ipsbiomed.com"></div>
            </div>
          </div>
          <div class="card-footer text-end"><button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar</button></div>
        </div>
      </form>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h2><i class="bi bi-send-check"></i>Probar el envío</h2></div>
        <div class="card-body">
          <form method="post" action="<?= url('configuracion/probar') ?>">
            <?= csrf_field() ?>
            <label class="form-label">Enviar correo de prueba a</label>
            <input type="email" name="destino" value="<?= e(user()['email']) ?>" class="form-control mb-2" required>
            <button class="btn btn-outline-primary w-100">Enviar prueba</button>
          </form>
          <p class="form-text mt-3 mb-0">Si no configura SMTP, el sistema intentará usar la función <code>mail()</code> del servidor, que suele terminar en spam.</p>
        </div>
      </div>
    </div>
  </div>

<?php elseif ($tab === 'seguridad'): ?>
  <form method="post" action="<?= url('configuracion/guardar') ?>">
    <?= csrf_field() ?><input type="hidden" name="seccion" value="seguridad">
    <div class="card">
      <div class="card-header"><h2><i class="bi bi-shield-lock"></i>Sesiones y acceso</h2></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Cerrar sesión por inactividad (minutos)</label>
            <input name="sesion_minutos" value="<?= $v('sesion_minutos', '60') ?>" class="form-control" inputmode="numeric">
            <div class="form-text">Entre 5 y 480 minutos.</div>
          </div>
        </div>
        <hr>
        <ul class="text-muted small mb-0">
          <li>Las contraseñas se guardan cifradas (hash) y nunca se muestran.</li>
          <li>Tras 5 intentos fallidos la cuenta se bloquea 15 minutos.</li>
          <li>Todos los accesos e impresiones quedan en <a href="<?= url('auditoria') ?>">Auditoría</a>.</li>
        </ul>
      </div>
      <div class="card-footer text-end"><button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar</button></div>
    </div>
  </form>

<?php else: ?>
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h2><i class="bi bi-plus-circle"></i>Agregar EPS</h2></div>
        <div class="card-body">
          <form method="post" action="<?= url('configuracion/eps') ?>">
            <?= csrf_field() ?><input type="hidden" name="accion" value="agregar">
            <label class="form-label">Nombre</label>
            <input name="nombre" class="form-control mb-2" maxlength="150" required>
            <label class="form-label">Código (opcional)</label>
            <input name="codigo" class="form-control mb-3" maxlength="10">
            <button class="btn btn-primary w-100">Agregar</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h2><i class="bi bi-hospital"></i>EPS y aseguradoras</h2><span class="text-muted small"><?= count($eps) ?> registradas</span></div>
        <div class="table-responsive" style="max-height: 560px; overflow-y: auto">
          <table class="table table-hover">
            <thead><tr><th>Nombre</th><th>Código</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($eps as $row): ?>
              <tr class="<?= (int) $row['activo'] ? '' : 'text-muted' ?>">
                <td><?= e($row['nombre']) ?></td>
                <td><?= e($row['codigo'] ?: '—') ?></td>
                <td><?= (int) $row['activo'] ? '<span class="badge-status badge-status-success"><i class="bi bi-check2"></i>Activa</span>' : '<span class="badge-status badge-status-muted">Inactiva</span>' ?></td>
                <td class="table-actions">
                  <form method="post" action="<?= url('configuracion/eps') ?>" class="d-inline">
                    <?= csrf_field() ?><input type="hidden" name="accion" value="estado"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <button class="btn btn-sm btn-light border"><?= (int) $row['activo'] ? 'Desactivar' : 'Activar' ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
