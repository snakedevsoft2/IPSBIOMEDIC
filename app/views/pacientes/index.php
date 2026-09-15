<?php
$puedeEditar = has_role('administrador', 'recepcion');
echo page_head('Pacientes', 'Registro de pacientes de la IPS', $puedeEditar
    ? '<a class="btn btn-primary" href="' . url('pacientes/crear') . '"><i class="bi bi-person-plus"></i> Registrar paciente</a>' : '');
?>
<div class="card">
  <div class="card-header">
    <form class="filter-bar w-100" method="get">
      <input type="hidden" name="r" value="pacientes">
      <div class="input-group" style="max-width: 440px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Buscar por documento o nombre" autofocus>
      </div>
      <button class="btn btn-light border" type="submit">Buscar</button>
      <?php if ($q !== ''): ?><a href="<?= url('pacientes') ?>" class="btn btn-link">Limpiar</a><?php endif; ?>
    </form>
  </div>

  <?php if (!$rows): ?>
    <div class="empty-state">
      <i class="bi bi-person-x"></i>
      <?= $q !== '' ? 'No se encontraron pacientes para «' . e($q) . '».' : 'Aún no hay pacientes registrados.' ?>
      <?php if ($puedeEditar): ?>
        <div class="mt-3"><a class="btn btn-primary" href="<?= url('pacientes/crear', ['documento' => $q]) ?>"><i class="bi bi-person-plus"></i> Registrar paciente</a></div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr><th>Paciente</th><th>Documento</th><th>Edad / sexo</th><th>EPS / régimen</th><th>Teléfono</th><th>Última atención</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm"><?= e(iniciales(nombre_paciente($p))) ?></span>
                <a class="cell-main" href="<?= url('pacientes/ver', ['id' => $p['id']]) ?>"><?= e(nombre_paciente($p)) ?></a>
              </div>
            </td>
            <td class="text-nowrap"><?= e($p['tipo_documento'] . ' ' . $p['numero_documento']) ?></td>
            <td class="text-nowrap"><?= e(edad($p['fecha_nacimiento'])) ?> · <?= e($p['sexo']) ?></td>
            <td><div><?= e($p['eps_nombre'] ?? 'Sin EPS') ?></div><div class="cell-sub"><?= e($p['regimen']) ?></div></td>
            <td class="text-nowrap"><?= e($p['telefono']) ?></td>
            <td class="text-nowrap"><?= $p['ultima_atencion'] ? e(fecha($p['ultima_atencion'])) : '<span class="text-muted">—</span>' ?></td>
            <td class="table-actions">
              <?php if ($puedeEditar): ?>
                <a class="btn btn-sm btn-soft" href="<?= url('admisiones/crear', ['paciente_id' => $p['id']]) ?>" title="Admitir a sala de espera"><i class="bi bi-box-arrow-in-right"></i> Admitir</a>
                <a class="btn btn-sm btn-light border" href="<?= url('pacientes/editar', ['id' => $p['id']]) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
              <?php endif; ?>
              <a class="btn btn-sm btn-light border" href="<?= url('pacientes/ver', ['id' => $p['id']]) ?>" title="Ver ficha"><i class="bi bi-eye"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= paginacion($pg) ?>
  <?php endif; ?>
</div>
