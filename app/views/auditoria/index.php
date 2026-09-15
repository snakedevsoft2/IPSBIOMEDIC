<?= page_head('Auditoría de accesos', 'Registro de quién consultó, modificó o imprimió información clínica') ?>

<div class="card">
  <div class="card-header">
    <form class="filter-bar w-100" method="get">
      <input type="hidden" name="r" value="auditoria">
      <div><label class="form-label">Desde</label><input type="date" name="desde" value="<?= e($f['desde']) ?>" class="form-control form-control-sm"></div>
      <div><label class="form-label">Hasta</label><input type="date" name="hasta" value="<?= e($f['hasta']) ?>" class="form-control form-control-sm"></div>
      <div><label class="form-label">Usuario</label><select name="usuario_id" class="form-select form-select-sm"><?= options($usuarios, $f['usuario'], 'Todos') ?></select></div>
      <div><label class="form-label">Acción</label><select name="accion" class="form-select form-select-sm"><?= options($acciones, $f['accion'], 'Todas') ?></select></div>
      <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
    </form>
  </div>

  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-shield-check"></i>Sin registros en el periodo seleccionado.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Registro</th><th>Detalle</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="text-nowrap small"><?= e(fecha($r['creado_en'], true)) ?></td>
            <td class="small">
              <?php if ($r['usuario']): ?>
                <div class="cell-main"><?= e($r['nombres'] . ' ' . $r['apellidos']) ?></div>
                <div class="cell-sub">@<?= e($r['usuario']) ?> · <?= e(catalogo('roles')[$r['rol']] ?? '') ?></div>
              <?php else: ?>
                <span class="text-muted">Sin sesión</span>
              <?php endif; ?>
            </td>
            <td class="small"><?= e($acciones[$r['accion']] ?? $r['accion']) ?></td>
            <td class="small">
              <?php if ($r['entidad'] === 'consultas' && $r['entidad_id']): ?>
                <a href="<?= url('consultas/ver', ['id' => $r['entidad_id']]) ?>">Historia #<?= (int) $r['entidad_id'] ?></a>
              <?php elseif ($r['entidad'] === 'pacientes' && $r['entidad_id']): ?>
                <a href="<?= url('pacientes/ver', ['id' => $r['entidad_id']]) ?>">Paciente #<?= (int) $r['entidad_id'] ?></a>
              <?php elseif ($r['entidad']): ?>
                <?= e($r['entidad']) ?><?= $r['entidad_id'] ? ' #' . (int) $r['entidad_id'] : '' ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="small text-muted"><?= e(mb_strimwidth((string) $r['detalle'], 0, 70, '…')) ?></td>
            <td class="small text-muted"><?= e($r['ip']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= paginacion($pg) ?>
  <?php endif; ?>
</div>
