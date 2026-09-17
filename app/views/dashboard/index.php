<?php
$acciones = '<form method="get" class="d-flex gap-2 align-items-center">'
    . '<input type="hidden" name="r" value="dashboard">'
    . '<label class="visually-hidden" for="mes">Periodo</label>'
    . '<select id="mes" name="mes" class="form-select" onchange="this.form.submit()">' . options($meses, $mes, '') . '</select></form>';
if (has_role('administrador', 'recepcion')) {
    $acciones .= '<a href="' . url('admisiones/crear') . '" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nueva admisión</a>';
}
$subtitulo = ucfirst(fecha_larga()) . ($u['rol'] === 'medico' ? ' · Sus indicadores de atención' : ' · Resumen de la IPS');

push_script('<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>');
push_script('<script>
document.addEventListener("DOMContentLoaded", function () {
  biomedBar("chartDias", ' . json_encode($dias) . ', ' . json_encode($serie) . ', {
    label: "Atenciones",
    tooltip: { title: function (items) { return items[0].label + " de ' . e($nombreMes) . '"; } }
  });
});
</script>');
?>
<?= page_head('Hola, ' . $u['nombres'], $subtitulo, $acciones) ?>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-xl-3">
    <div class="kpi">
      <div class="kpi-icon"><i class="bi bi-clipboard2-check"></i></div>
      <div>
        <div class="kpi-label">Atendidos hoy</div>
        <div class="kpi-value"><?= number_format($kpi['hoy'], 0, ',', '.') ?></div>
        <div class="kpi-sub"><?= $kpi['en_atencion'] ?> en consulta en este momento</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a href="<?= url('admisiones') ?>" class="kpi text-reset">
      <div class="kpi-icon <?= $kpi['espera'] > 0 ? 'rojo' : 'gris' ?>"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div class="kpi-label">En sala de espera</div>
        <div class="kpi-value"><?= number_format($kpi['espera'], 0, ',', '.') ?></div>
        <div class="kpi-sub">Ver sala de espera <i class="bi bi-arrow-right"></i></div>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="kpi">
      <div class="kpi-icon"><i class="bi bi-calendar2-check"></i></div>
      <div>
        <div class="kpi-label">Atendidos en <?= e($nombreMes) ?></div>
        <div class="kpi-value"><?= number_format($kpi['mes'], 0, ',', '.') ?></div>
        <div class="kpi-sub"><?= number_format($kpi['formulas'], 0, ',', '.') ?> fórmulas médicas emitidas</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a href="<?= url('pacientes') ?>" class="kpi text-reset">
      <div class="kpi-icon gris"><i class="bi bi-people"></i></div>
      <div>
        <div class="kpi-label">Pacientes registrados</div>
        <div class="kpi-value"><?= number_format($kpi['pacientes'], 0, ',', '.') ?></div>
        <div class="kpi-sub"><?= number_format($kpi['pacientes_mes'], 0, ',', '.') ?> nuevos en <?= e($nombreMes) ?></div>
      </div>
    </a>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card h-100">
      <div class="card-header">
        <h2><i class="bi bi-bar-chart"></i>Pacientes atendidos por día</h2>
        <span class="text-muted small"><?= e($meses[$mes] ?? '') ?> · Total <?= number_format($kpi['mes'], 0, ',', '.') ?></span>
      </div>
      <div class="card-body"><div class="chart-box"><canvas id="chartDias" aria-label="Atenciones por día" role="img"></canvas></div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">
        <h2><i class="bi bi-people"></i>Pacientes de hoy</h2>
        <a href="<?= url('admisiones') ?>" class="small fw-medium">Ver todos</a>
      </div>
      <?php if (!$hoy): ?>
        <div class="empty-state"><i class="bi bi-cup-hot"></i>No hay pacientes en espera ni en consulta.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>Llegada</th><th>Paciente</th><th>Médico</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($hoy as $a): ?>
              <tr>
                <td class="text-nowrap"><?= date('h:i a', strtotime($a['fecha_hora'])) ?></td>
                <td>
                  <div class="cell-main"><?= e(nombre_paciente($a)) ?>
                    <?php if ($a['prioridad'] === 'Prioritaria'): ?><span class="tag-rojo ms-1">Prioritaria</span><?php endif; ?>
                  </div>
                  <div class="cell-sub"><?= e($a['tipo_documento'] . ' ' . $a['numero_documento']) ?> · <?= e(edad($a['fecha_nacimiento'])) ?></div>
                </td>
                <td class="small"><?= $a['med_nombres'] ? e($a['med_nombres'] . ' ' . $a['med_apellidos']) : '<span class="text-muted">Sin asignar</span>' ?></td>
                <td><?= badge_estado($a['estado']) ?></td>
                <td class="table-actions">
                  <?php if ($u['rol'] === 'medico' && $a['estado'] === 'en_espera'): ?>
                    <form method="post" action="<?= url('consultas/atender') ?>" class="d-inline">
                      <?= csrf_field() ?><input type="hidden" name="admision_id" value="<?= (int) $a['id'] ?>">
                      <button class="btn btn-sm btn-primary">Atender</button>
                    </form>
                  <?php elseif ($u['rol'] === 'medico' && $a['consulta_id'] && (int) $a['medico_id'] === (int) $u['id']): ?>
                    <a class="btn btn-sm btn-soft" href="<?= url('consultas/editar', ['id' => $a['consulta_id']]) ?>">Continuar</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><h2><i class="bi bi-clipboard2-pulse"></i>Diagnósticos más frecuentes</h2></div>
      <div class="card-body pt-2">
        <?= bar_list(array_map(static fn ($d) => [$d['codigo'] . ' · ' . $d['descripcion'], (int) $d['n']], $topDx)) ?>
      </div>
    </div>
  </div>
</div>

<?php if ($porMedico): ?>
<div class="row g-3">
  <div class="col-12">
    <div class="card h-100">
      <div class="card-header"><h2><i class="bi bi-person-badge"></i>Atenciones por médico</h2></div>
      <div class="card-body pt-2"><?= bar_list($porMedico) ?></div>
    </div>
  </div>
</div>
<?php endif; ?>
