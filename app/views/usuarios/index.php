<?= page_head('Usuarios', 'Perfiles de acceso al sistema', '<a class="btn btn-primary" href="' . url('usuarios/crear') . '"><i class="bi bi-person-plus"></i> Nuevo usuario</a>') ?>

<div class="card">
  <div class="card-header">
    <form class="filter-bar w-100" method="get">
      <input type="hidden" name="r" value="usuarios">
      <div class="input-group" style="max-width: 360px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Nombre, usuario o correo">
      </div>
      <select name="rol" class="form-select" style="max-width: 220px" onchange="this.form.submit()"><?= options(catalogo('roles'), $rol, 'Todos los perfiles') ?></select>
      <button class="btn btn-light border" type="submit">Buscar</button>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Usuario</th><th>Perfil</th><th>Documento</th><th>Contacto</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="<?= (int) $r['activo'] ? '' : 'opacity-75' ?>">
          <td>
            <div class="d-flex align-items-center gap-2">
              <span class="avatar avatar-sm"><?= e(iniciales(nombre_usuario($r))) ?></span>
              <div>
                <div class="cell-main"><?= e(nombre_usuario($r)) ?></div>
                <div class="cell-sub">@<?= e($r['usuario']) ?><?= $r['registro_medico'] ? ' · RM ' . e($r['registro_medico']) : '' ?></div>
              </div>
            </div>
          </td>
          <td><span class="badge-rol <?= e($r['rol']) ?>"><?= e(catalogo('roles')[$r['rol']] ?? $r['rol']) ?></span><?= $r['especialidad'] ? '<div class="cell-sub">' . e($r['especialidad']) . '</div>' : '' ?></td>
          <td class="small text-nowrap"><?= e($r['tipo_documento'] . ' ' . $r['documento']) ?></td>
          <td class="small"><?= e($r['email']) ?><?= $r['telefono'] ? '<div class="cell-sub">' . e($r['telefono']) . '</div>' : '' ?></td>
          <td class="small text-nowrap"><?= $r['ultimo_acceso'] ? e(fecha($r['ultimo_acceso'], true)) : '<span class="text-muted">Nunca</span>' ?></td>
          <td>
            <?php if ((int) $r['activo']): ?>
              <span class="badge-status badge-status-success"><i class="bi bi-check2-circle"></i>Activo</span>
            <?php else: ?>
              <span class="badge-status badge-status-muted"><i class="bi bi-slash-circle"></i>Inactivo</span>
            <?php endif; ?>
            <?php if ($r['bloqueado_hasta'] && strtotime($r['bloqueado_hasta']) > time()): ?>
              <div class="cell-sub text-rojo"><i class="bi bi-lock"></i> Bloqueado</div>
            <?php endif; ?>
          </td>
          <td class="table-actions">
            <a class="btn btn-sm btn-light border" href="<?= url('usuarios/editar', ['id' => $r['id']]) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
            <?php if ((int) $r['id'] !== (int) user()['id']): ?>
              <form method="post" action="<?= url('usuarios/estado') ?>" class="d-inline"
                    data-confirm="<?= (int) $r['activo'] ? '¿Desactivar el acceso de ' . e(nombre_usuario($r)) . '?' : '¿Activar el acceso de ' . e(nombre_usuario($r)) . '?' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-sm btn-light border" title="<?= (int) $r['activo'] ? 'Desactivar' : 'Activar' ?>">
                  <i class="bi <?= (int) $r['activo'] ? 'bi-person-slash' : 'bi-person-check' ?>"></i>
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= paginacion($pg) ?>
</div>
