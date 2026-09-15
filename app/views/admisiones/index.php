<?php
$esHoy = $fecha === date('Y-m-d');
$esMedico = $u['rol'] === 'medico';
$puedeAdmitir = has_role('administrador', 'recepcion');
$clinico = has_role('administrador', 'medico', 'auxiliar');
$tabs = [
    'activos'     => ['Pendientes', ($conteos['en_espera'] ?? 0) + ($conteos['en_atencion'] ?? 0)],
    'en_espera'   => ['En espera', $conteos['en_espera'] ?? 0],
    'en_atencion' => ['En atención', $conteos['en_atencion'] ?? 0],
    'atendida'    => ['Atendidos', $conteos['atendida'] ?? 0],
    'cancelada'   => ['Cancelados', $conteos['cancelada'] ?? 0],
    'todos'       => ['Todos', array_sum($conteos)],
];
$medicoOpciones = ($esMedico ? ['mios' => 'Mis pacientes y sin asignar'] : []) + $medicos;

echo page_head(
    'Sala de espera',
    ($esHoy ? 'Hoy, ' : '') . fecha_larga($fecha) . ($esHoy ? ' · se actualiza automáticamente' : ''),
    $puedeAdmitir ? '<a href="' . url('admisiones/crear') . '" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nueva admisión</a>' : ''
);
?>
<div <?= $esHoy ? 'data-autorefresh="60"' : '' ?>></div>

<div class="card">
  <div class="card-header flex-wrap">
    <form class="filter-bar" method="get">
      <input type="hidden" name="r" value="admisiones">
      <input type="hidden" name="estado" value="<?= e($estado) ?>">
      <div>
        <label class="form-label">Fecha</label>
        <input type="date" name="fecha" value="<?= e($fecha) ?>" class="form-control form-control-sm" onchange="this.form.submit()">
      </div>
      <div>
        <label class="form-label">Médico</label>
        <select name="medico" class="form-select form-select-sm" onchange="this.form.submit()">
          <?= options($medicoOpciones, $medico, 'Todos los médicos') ?>
        </select>
      </div>
      <?php if (!$esHoy): ?>
        <a class="btn btn-sm btn-light border" href="<?= url('admisiones') ?>">Ir a hoy</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="px-3 pt-2">
    <nav class="nav-tabs-b mb-0">
      <?php foreach ($tabs as $key => [$label, $n]): ?>
        <a class="<?= $estado === $key ? 'active' : '' ?>" href="<?= url('admisiones', ['fecha' => $fecha, 'medico' => $medico, 'estado' => $key]) ?>">
          <?= e($label) ?><span class="count"><?= (int) $n ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <?php if (!$rows): ?>
    <div class="empty-state">
      <i class="bi bi-people"></i>No hay pacientes en esta lista.
      <?php if ($puedeAdmitir && $esHoy): ?>
        <div class="mt-3"><a href="<?= url('admisiones/crear') ?>" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Admitir paciente</a></div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr><th>Llegada</th><th>Paciente</th><th>Consulta</th><th>EPS / régimen</th><th>Médico</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $a):
            $espera = (int) floor((time() - strtotime($a['fecha_hora'])) / 60);
            $propio = (int) $a['medico_id'] === (int) $u['id']; ?>
          <tr>
            <td class="text-nowrap">
              <div class="fw-semibold"><?= date('h:i a', strtotime($a['fecha_hora'])) ?></div>
              <?php if ($a['estado'] === 'en_espera' && $esHoy): ?>
                <div class="cell-sub <?= $espera >= 45 ? 'text-rojo fw-semibold' : '' ?>"><i class="bi bi-clock"></i> <?= $espera < 60 ? $espera . ' min' : floor($espera / 60) . ' h ' . ($espera % 60) . ' min' ?></div>
              <?php endif; ?>
            </td>
            <td>
              <a class="cell-main" href="<?= url('pacientes/ver', ['id' => $a['paciente_id']]) ?>"><?= e(nombre_paciente($a)) ?></a>
              <?php if ($a['prioridad'] === 'Prioritaria'): ?><span class="tag-rojo ms-1">Prioritaria</span><?php endif; ?>
              <div class="cell-sub"><?= e($a['tipo_documento'] . ' ' . $a['numero_documento']) ?> · <?= e(edad($a['fecha_nacimiento'])) ?> · <?= e($a['sexo']) ?></div>
            </td>
            <td>
              <div><?= e($a['tipo_consulta']) ?></div>
              <div class="cell-sub" title="<?= e($a['motivo']) ?>"><?= e(mb_strimwidth((string) $a['motivo'], 0, 60, '…')) ?></div>
            </td>
            <td><div><?= e($a['eps_nombre'] ?? 'Sin EPS') ?></div><div class="cell-sub"><?= e($a['regimen']) ?></div></td>
            <td class="small"><?= $a['med_nombres'] ? e($a['med_nombres'] . ' ' . $a['med_apellidos']) : '<span class="text-muted">Sin asignar</span>' ?></td>
            <td>
              <?= badge_estado($a['estado']) ?>
              <?php if ($a['estado'] === 'cancelada' && $a['motivo_cancelacion']): ?><div class="cell-sub"><?= e($a['motivo_cancelacion']) ?></div><?php endif; ?>
            </td>
            <td class="table-actions">
              <?php if ($esMedico && $a['estado'] === 'en_espera' && ($a['medico_id'] === null || $propio)): ?>
                <form method="post" action="<?= url('consultas/atender') ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="admision_id" value="<?= (int) $a['id'] ?>">
                  <button class="btn btn-sm btn-primary"><i class="bi bi-play-fill"></i> Atender</button>
                </form>
              <?php elseif ($esMedico && $a['estado'] === 'en_atencion' && $propio && $a['consulta_id']): ?>
                <a class="btn btn-sm btn-soft" href="<?= url('consultas/editar', ['id' => $a['consulta_id']]) ?>"><i class="bi bi-pencil-square"></i> Continuar</a>
              <?php endif; ?>

              <?php if ($clinico && $a['estado'] === 'atendida' && $a['consulta_id']): ?>
                <a class="btn btn-sm btn-light border" href="<?= url('consultas/ver', ['id' => $a['consulta_id']]) ?>" title="Ver historia clínica"><i class="bi bi-journal-medical"></i></a>
                <a class="btn btn-sm btn-light border" target="_blank" href="<?= url('pdf/historia', ['id' => $a['consulta_id']]) ?>" title="PDF historia clínica"><i class="bi bi-file-earmark-pdf"></i></a>
              <?php endif; ?>

              <?php if ($puedeAdmitir && $a['estado'] === 'en_espera'): ?>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar"
                        data-id="<?= (int) $a['id'] ?>" data-nombre="<?= e(nombre_paciente($a)) ?>" title="Cancelar admisión"><i class="bi bi-x-lg"></i></button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if ($puedeAdmitir): ?>
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="post" action="<?= url('admisiones/cancelar') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="cancelar-id">
      <div class="modal-header">
        <h5 class="modal-title">Cancelar admisión</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">Paciente: <strong id="cancelar-nombre"></strong></p>
        <label class="form-label" for="cancelar-motivo">Motivo de la cancelación</label>
        <input id="cancelar-motivo" name="motivo" class="form-control" maxlength="255" required placeholder="Ej.: el paciente se retiró antes de ser atendido">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Volver</button>
        <button type="submit" class="btn btn-danger">Cancelar admisión</button>
      </div>
    </form>
  </div>
</div>
<?php push_script('<script>
document.getElementById("modalCancelar").addEventListener("show.bs.modal", function (e) {
  document.getElementById("cancelar-id").value = e.relatedTarget.dataset.id;
  document.getElementById("cancelar-nombre").textContent = e.relatedTarget.dataset.nombre;
});
</script>'); ?>
<?php endif; ?>
