<?= page_head('Historias clínicas', 'Atenciones de medicina general registradas') ?>

<div class="card">
  <div class="card-header">
    <form class="filter-bar w-100" method="get">
      <input type="hidden" name="r" value="consultas">
      <div><label class="form-label">Desde</label><input type="date" name="desde" value="<?= e($f['desde']) ?>" class="form-control form-control-sm"></div>
      <div><label class="form-label">Hasta</label><input type="date" name="hasta" value="<?= e($f['hasta']) ?>" class="form-control form-control-sm"></div>
      <div><label class="form-label">Médico</label><select name="medico" class="form-select form-select-sm"><?= options($medicos, $f['medico'], 'Todos') ?></select></div>
      <div><label class="form-label">Estado</label><select name="estado" class="form-select form-select-sm"><?= options(['cerrada' => 'Finalizadas', 'borrador' => 'Borradores'], $f['estado'], 'Todas') ?></select></div>
      <div class="flex-grow-1"><label class="form-label">Paciente</label><input type="search" name="q" value="<?= e($f['q']) ?>" class="form-control form-control-sm" placeholder="Documento o nombre"></div>
      <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
    </form>
  </div>

  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-journal-x"></i>No hay atenciones con los filtros seleccionados.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead><tr><th>Fecha</th><th>Paciente</th><th>Diagnóstico principal</th><th>Médico</th><th>Estado</th><th class="text-end">Documentos</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="text-nowrap"><?= e(fecha($r['cerrada_en'] ?? $r['creado_en'], true)) ?><div class="cell-sub"><?= e($r['tipo_consulta']) ?></div></td>
            <td>
              <a class="cell-main" href="<?= url('consultas/ver', ['id' => $r['id']]) ?>"><?= e(nombre_paciente($r)) ?></a>
              <div class="cell-sub"><?= e($r['tipo_documento'] . ' ' . $r['numero_documento']) ?> · <?= e(edad($r['fecha_nacimiento'], $r['creado_en'])) ?> · <?= e($r['sexo']) ?></div>
            </td>
            <td class="small"><?= $r['dx'] ? e($r['dx']) : '<span class="text-muted">—</span>' ?></td>
            <td class="small text-nowrap"><?= e($r['med_nombres'] . ' ' . $r['med_apellidos']) ?></td>
            <td><?= badge_estado($r['estado']) ?></td>
            <td class="table-actions">
              <a class="btn btn-sm btn-light border" href="<?= url('consultas/ver', ['id' => $r['id']]) ?>" title="Ver"><i class="bi bi-eye"></i></a>
              <?php if ($r['estado'] === 'cerrada'): ?>
                <a class="btn btn-sm btn-light border" target="_blank" href="<?= url('pdf/historia', ['id' => $r['id']]) ?>" title="Historia clínica PDF"><i class="bi bi-file-earmark-medical"></i></a>
                <?php if ($r['tiene_formula']): ?>
                  <a class="btn btn-sm btn-light border" target="_blank" href="<?= url('pdf/formula', ['id' => $r['id']]) ?>" title="Fórmula médica PDF"><i class="bi bi-capsule"></i></a>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= paginacion($pg) ?>
  <?php endif; ?>
</div>
