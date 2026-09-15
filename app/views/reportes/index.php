<?php
$qs = ['desde' => $f['desde'], 'hasta' => $f['hasta'], 'medico' => $f['medico'], 'regimen' => $f['regimen'], 'eps_id' => $f['eps']];
$acciones = '<a class="btn btn-light border" href="' . url('reportes/excel', $qs) . '"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a>'
    . '<a class="btn btn-primary" target="_blank" href="' . url('reportes/pdf', $qs) . '"><i class="bi bi-file-earmark-pdf"></i> PDF</a>';
echo page_head('Informes de atención', 'Del ' . fecha($f['desde']) . ' al ' . fecha($f['hasta']), $acciones);
?>
<div class="card mb-3">
  <div class="card-body">
    <form class="filter-bar" method="get">
      <input type="hidden" name="r" value="reportes">
      <div><label class="form-label">Desde</label><input type="date" name="desde" value="<?= e($f['desde']) ?>" class="form-control form-control-sm"></div>
      <div><label class="form-label">Hasta</label><input type="date" name="hasta" value="<?= e($f['hasta']) ?>" class="form-control form-control-sm"></div>
      <?php if (!has_role('medico')): ?>
        <div><label class="form-label">Médico</label><select name="medico" class="form-select form-select-sm"><?= options($medicos, $f['medico'], 'Todos') ?></select></div>
      <?php endif; ?>
      <div><label class="form-label">Régimen</label><select name="regimen" class="form-select form-select-sm"><?= options(catalogo('regimen'), $f['regimen'], 'Todos') ?></select></div>
      <div><label class="form-label">EPS</label><select name="eps_id" class="form-select form-select-sm"><?= options($epsLista, $f['eps'], 'Todas') ?></select></div>
      <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-funnel"></i> Aplicar</button>
    </form>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-lg-3">
    <div class="kpi"><div class="kpi-icon"><i class="bi bi-clipboard2-check"></i></div>
      <div><div class="kpi-label">Atenciones</div><div class="kpi-value"><?= number_format($total, 0, ',', '.') ?></div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="kpi"><div class="kpi-icon gris"><i class="bi bi-people"></i></div>
      <div><div class="kpi-label">Pacientes distintos</div><div class="kpi-value"><?= number_format($pacientes, 0, ',', '.') ?></div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="kpi"><div class="kpi-icon gris"><i class="bi bi-arrow-repeat"></i></div>
      <div><div class="kpi-label">Consultas por paciente</div><div class="kpi-value"><?= $pacientes ? number_format($total / $pacientes, 1, ',', '.') : '0' ?></div></div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="kpi"><div class="kpi-icon gris"><i class="bi bi-capsule"></i></div>
      <div><div class="kpi-label">Con fórmula médica</div>
        <div class="kpi-value"><?= number_format(count(array_filter($rows, static fn ($r) => (int) $r['medicamentos'] > 0)), 0, ',', '.') ?></div>
        <div class="kpi-sub">en la página actual</div></div></div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header"><h2><i class="bi bi-clipboard2-pulse"></i>Diagnósticos más frecuentes</h2></div>
      <div class="card-body pt-2"><?= bar_list(array_map(static fn ($d) => [$d['codigo'] . ' · ' . $d['descripcion'], (int) $d['n']], $topDx)) ?></div></div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header"><h2><i class="bi bi-hospital"></i>Atenciones por EPS</h2></div>
      <div class="card-body pt-2"><?= bar_list($porEps) ?></div></div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100"><div class="card-header"><h2><i class="bi bi-person-badge"></i>Por médico</h2></div>
      <div class="card-body pt-2"><?= bar_list($porMedico) ?></div></div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100"><div class="card-header"><h2><i class="bi bi-gender-ambiguous"></i>Por sexo</h2></div>
      <div class="card-body pt-2"><?= bar_list($porSexo) ?></div></div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100"><div class="card-header"><h2><i class="bi bi-shield-plus"></i>Por régimen</h2></div>
      <div class="card-body pt-2"><?= bar_list($porRegimen) ?></div></div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card h-100"><div class="card-header"><h2><i class="bi bi-clipboard2"></i>Por tipo de consulta</h2></div>
      <div class="card-body pt-2"><?= bar_list($porTipo) ?></div></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2><i class="bi bi-table"></i>Detalle de atenciones</h2><span class="text-muted small">Base para RIPS y facturación</span></div>
  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-inbox"></i>No hay atenciones finalizadas en el periodo seleccionado.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead><tr><th>Fecha</th><th>Paciente</th><th>Documento</th><th>Edad</th><th>EPS / régimen</th><th>Tipo</th><th>Dx principal</th><th>Médico</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="text-nowrap"><?= e(fecha($r['cerrada_en'], true)) ?></td>
            <td><?= e(nombre_paciente($r)) ?></td>
            <td class="text-nowrap small"><?= e($r['tipo_documento'] . ' ' . $r['numero_documento']) ?></td>
            <td class="num"><?= (int) $r['edad_anios'] ?></td>
            <td class="small"><?= e($r['eps'] ?? 'Sin EPS') ?><div class="cell-sub"><?= e($r['regimen']) ?></div></td>
            <td class="small"><?= e($r['tipo_consulta']) ?></td>
            <td class="small"><strong><?= e($r['dx_codigo']) ?></strong> <?= e(mb_strimwidth((string) $r['dx_descripcion'], 0, 45, '…')) ?></td>
            <td class="small text-nowrap"><?= e($r['med_nombres'] . ' ' . $r['med_apellidos']) ?></td>
            <td class="table-actions">
              <a class="btn btn-sm btn-light border" href="<?= url('consultas/ver', ['id' => $r['consulta_id']]) ?>" title="Ver historia"><i class="bi bi-eye"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= paginacion($pg) ?>
  <?php endif; ?>
</div>
