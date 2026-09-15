<?php
$u = user();
$cerrada = $c['estado'] === 'cerrada';
$campo = static function (string $label, mixed $valor): void {
    if (trim((string) $valor) === '') {
        return;
    }
    echo '<div class="hc-field"><div class="hc-field-label">' . e($label) . '</div><div class="readonly-block">' . e($valor) . '</div></div>';
};

$acciones = '';
if ($cerrada) {
    $acciones .= '<a class="btn btn-primary" target="_blank" href="' . url('pdf/historia', ['id' => $c['id']]) . '"><i class="bi bi-file-earmark-medical"></i> Historia clínica PDF</a>';
    if ($items) {
        $acciones .= '<a class="btn btn-outline-primary" target="_blank" href="' . url('pdf/formula', ['id' => $c['id']]) . '"><i class="bi bi-capsule"></i> Fórmula médica</a>';
    }
    if ($ordenes) {
        $acciones .= '<a class="btn btn-outline-primary" target="_blank" href="' . url('pdf/ordenes', ['id' => $c['id']]) . '"><i class="bi bi-clipboard2-data"></i> Órdenes</a>';
    }
    if ($c['incapacidad_dias']) {
        $acciones .= '<a class="btn btn-outline-primary" target="_blank" href="' . url('pdf/incapacidad', ['id' => $c['id']]) . '"><i class="bi bi-calendar2-x"></i> Incapacidad</a>';
    }
} elseif ($u['rol'] === 'medico' && (int) $c['medico_id'] === (int) $u['id']) {
    $acciones .= '<a class="btn btn-primary" href="' . url('consultas/editar', ['id' => $c['id']]) . '"><i class="bi bi-pencil-square"></i> Continuar diligenciando</a>';
}

$vitales = [
    'Tensión arterial' => ($c['ta_sistolica'] && $c['ta_diastolica']) ? $c['ta_sistolica'] . '/' . $c['ta_diastolica'] . ' mmHg' : '',
    'Frecuencia cardíaca' => $c['frecuencia_cardiaca'] ? $c['frecuencia_cardiaca'] . ' lpm' : '',
    'Frecuencia respiratoria' => $c['frecuencia_respiratoria'] ? $c['frecuencia_respiratoria'] . ' rpm' : '',
    'Temperatura' => $c['temperatura'] ? $c['temperatura'] . ' °C' : '',
    'Saturación O₂' => $c['saturacion'] ? $c['saturacion'] . ' %' : '',
    'Peso' => $c['peso'] ? (float) $c['peso'] . ' kg' : '',
    'Talla' => $c['talla'] ? (float) $c['talla'] . ' cm' : '',
    'IMC' => $c['imc'] ? $c['imc'] . ' kg/m²' : '',
    'Perímetro abdominal' => $c['perimetro_abdominal'] ? (float) $c['perimetro_abdominal'] . ' cm' : '',
    'Glucometría' => $c['glucometria'] ? $c['glucometria'] . ' mg/dL' : '',
];
$vitales = array_filter($vitales);

echo page_head('Historia clínica', 'Atención del ' . fecha($c['cerrada_en'] ?? $c['creado_en'], true), $acciones);
?>
<?php if (!$cerrada): ?>
  <div class="alert alert-warning"><i class="bi bi-pencil me-1"></i>Esta atención está en <strong>borrador</strong>: aún no ha sido finalizada por el médico.</div>
<?php endif; ?>

<div class="patient-banner">
  <span class="avatar avatar-lg"><?= e(iniciales(nombre_paciente($p))) ?></span>
  <div class="flex-grow-1">
    <a class="pb-name text-reset" href="<?= url('pacientes/ver', ['id' => $p['id']]) ?>"><?= e(nombre_paciente($p)) ?></a>
    <div class="pb-meta">
      <span><i class="bi bi-person-vcard"></i><?= e($p['tipo_documento'] . ' ' . $p['numero_documento']) ?></span>
      <span><i class="bi bi-calendar-heart"></i><?= e(edad($p['fecha_nacimiento'], $c['creado_en'])) ?> · <?= e(catalogo('sexo')[$p['sexo']] ?? '') ?></span>
      <span><i class="bi bi-hospital"></i><?= e($c['adm_eps'] ?? 'Sin EPS') ?> · <?= e($c['adm_regimen']) ?></span>
    </div>
  </div>
  <div><?= badge_estado($c['estado']) ?></div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-chat-left-text"></i>Anamnesis</h2></div>
      <div class="card-body">
        <?php $campo('Motivo de consulta', $c['motivo_consulta']); ?>
        <?php $campo('Enfermedad actual', $c['enfermedad_actual']); ?>
        <?php $campo('Revisión por sistemas', $c['revision_sistemas']); ?>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-clipboard2-heart"></i>Antecedentes</h2></div>
      <div class="card-body">
        <?php $hay = false; foreach (Clinica::ANTECEDENTES as $k => $label): if (trim((string) ($ant[$k] ?? '')) === '') continue; $hay = true; $campo($label, $ant[$k]); endforeach; ?>
        <?php if (!$hay): ?><p class="text-muted mb-0">No refiere antecedentes.</p><?php endif; ?>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-heart-pulse"></i>Examen físico</h2></div>
      <div class="card-body">
        <?php if ($vitales): ?>
          <dl class="dl-grid mb-3">
            <?php foreach ($vitales as $label => $valor): ?><div><dt><?= e($label) ?></dt><dd><?= e($valor) ?></dd></div><?php endforeach; ?>
          </dl>
        <?php endif; ?>
        <?php foreach (Clinica::EXAMEN as $k => $label) { $campo($label, $c[$k]); } ?>
        <?php $campo('Paraclínicos aportados', $c['paraclinicos']); ?>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-clipboard2-pulse"></i>Diagnósticos</h2></div>
      <?php if (!$dx): ?>
        <div class="card-body text-muted">Sin diagnósticos registrados.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Tipo</th><th>Código</th><th>Descripción</th><th>Clase</th></tr></thead>
            <tbody>
            <?php foreach ($dx as $d): ?>
              <tr><td><span class="dx-tipo"><?= e($d['tipo']) ?></span></td><td class="fw-semibold"><?= e($d['codigo']) ?></td><td><?= e($d['descripcion']) ?></td><td class="small"><?= e($d['clase']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-signpost-split"></i>Análisis y plan</h2></div>
      <div class="card-body">
        <?php $campo('Análisis', $c['analisis']); ?>
        <?php $campo('Plan de manejo / conducta', $c['plan_manejo']); ?>
        <?php $campo('Recomendaciones', $c['recomendaciones']); ?>
        <?php $campo('Próximo control', $c['proximo_control']); ?>
        <?php if ($c['incapacidad_dias']): ?>
          <?php $campo('Incapacidad', $c['incapacidad_dias'] . ' día(s) a partir del ' . fecha($c['incapacidad_desde'])); ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($items): ?>
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-capsule"></i>Fórmula médica</h2></div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>#</th><th>Medicamento</th><th>Posología</th><th class="text-end">Cantidad</th></tr></thead>
          <tbody>
          <?php foreach ($items as $i => $it): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><div class="cell-main"><?= e($it['medicamento']) ?> <?= e($it['concentracion']) ?></div><div class="cell-sub"><?= e(trim($it['forma_farmaceutica'] . ' · Vía ' . mb_strtolower((string) $it['via']), ' ·')) ?></div></td>
              <td class="small"><?= e(implode(' · ', array_filter([$it['dosis'], $it['frecuencia'], $it['duracion']]))) ?><?php if ($it['indicaciones']): ?><div class="cell-sub"><?= e($it['indicaciones']) ?></div><?php endif; ?></td>
              <td class="num"><?= (int) $it['cantidad'] ?> <div class="cell-sub">(<?= e($it['cantidad_letras']) ?>)</div></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($formula['observaciones']): ?><div class="card-body border-top"><?php $campo('Observaciones', $formula['observaciones']); ?></div><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($ordenes): ?>
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-clipboard2-data"></i>Órdenes</h2></div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Tipo</th><th>Código</th><th>Descripción</th><th class="text-end">Cant.</th></tr></thead>
          <tbody>
          <?php foreach ($ordenes as $o): ?>
            <tr><td class="small"><?= e($o['tipo']) ?></td><td><?= e($o['codigo']) ?></td><td><?= e($o['descripcion']) ?><?php if ($o['observacion']): ?><div class="cell-sub"><?= e($o['observacion']) ?></div><?php endif; ?></td><td class="num"><?= (int) $o['cantidad'] ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-info-circle"></i>Datos de la atención</h2></div>
      <div class="card-body">
        <dl class="dl-grid" style="grid-template-columns: 1fr 1fr">
          <div><dt>Admisión</dt><dd><?= e(fecha($c['admision_fecha'], true)) ?></dd></div>
          <div><dt>Finalizada</dt><dd><?= $c['cerrada_en'] ? e(fecha($c['cerrada_en'], true)) : '—' ?></dd></div>
          <div><dt>Tipo de consulta</dt><dd><?= e($c['tipo_consulta']) ?></dd></div>
          <div><dt>Modalidad</dt><dd><?= e($c['modalidad']) ?></dd></div>
          <div><dt>Finalidad</dt><dd><?= e($c['finalidad']) ?></dd></div>
          <div><dt>Causa externa</dt><dd><?= e($c['causa_externa']) ?></dd></div>
          <div><dt>Autorización</dt><dd><?= e($c['numero_autorizacion'] ?: '—') ?></dd></div>
          <div><dt>Copago</dt><dd><?= e(dinero($c['valor_copago'])) ?></dd></div>
        </dl>
        <hr>
        <div class="hc-field-label">Médico tratante</div>
        <div class="fw-semibold"><?= e($c['med_nombres'] . ' ' . $c['med_apellidos']) ?></div>
        <div class="text-muted small">Registro médico <?= e($c['registro_medico'] ?: '—') ?><?= $c['especialidad'] ? ' · ' . e($c['especialidad']) : '' ?></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2><i class="bi bi-sticky"></i>Notas aclaratorias</h2></div>
      <div class="card-body">
        <?php if (!$notas): ?><p class="text-muted small">Sin notas aclaratorias.</p><?php endif; ?>
        <?php foreach ($notas as $n): ?>
          <div class="nota-item">
            <div class="small text-muted mb-1"><?= e(fecha($n['creado_en'], true)) ?> · <?= e($n['nombres'] . ' ' . $n['apellidos']) ?></div>
            <div class="readonly-block"><?= e($n['nota']) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if ($cerrada && $u['rol'] === 'medico'): ?>
          <form method="post" action="<?= url('consultas/nota') ?>" class="mt-3">
            <?= csrf_field() ?>
            <input type="hidden" name="consulta_id" value="<?= (int) $c['id'] ?>">
            <label class="form-label" for="nota">Agregar nota aclaratoria</label>
            <textarea class="form-control mb-2" id="nota" name="nota" rows="3" required minlength="5" placeholder="Corrección o aclaración; la historia original no se modifica."></textarea>
            <button class="btn btn-sm btn-primary w-100" data-confirm-click="Las notas aclaratorias no se pueden borrar. ¿Guardar la nota?">Guardar nota</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
