<?php
$docTitulo = 'HISTORIA CLÍNICA';
$docNumero = 'Medicina general · Atención N.º ' . str_pad((string) $c['id'], 6, '0', STR_PAD_LEFT);
$docFecha = 'Fecha: ' . fecha($c['cerrada_en'], true);
require __DIR__ . '/_head.php';

$texto = static function (string $label, mixed $valor, bool $siempre = false): void {
    $valor = trim((string) $valor);
    if ($valor === '' && !$siempre) {
        return;
    }
    echo '<div class="txt"><span class="lbl">' . e($label) . '</span>' . ($valor === '' ? '<span class="muted">No refiere</span>' : nl2br(e($valor))) . '</div>';
};
$dash = static fn ($v): string => ($v === null || $v === '') ? '—' : e($v);
?>

<h2 class="sec" style="margin-top:0">Identificación del paciente</h2>
<table class="grid">
  <tr>
    <td colspan="2" style="width:40%"><span class="lbl">Nombre completo</span><span class="big"><?= e(nombre_paciente($p)) ?></span></td>
    <td style="width:20%"><span class="lbl">Documento</span><?= e($p['tipo_documento'] . ' ' . $p['numero_documento']) ?></td>
    <td style="width:20%"><span class="lbl">Fecha de nacimiento</span><?= e(fecha($p['fecha_nacimiento'])) ?></td>
    <td style="width:20%"><span class="lbl">Edad</span><?= e(edad($p['fecha_nacimiento'], $c['cerrada_en'])) ?></td>
  </tr>
  <tr>
    <td><span class="lbl">Sexo</span><?= e(catalogo('sexo')[$p['sexo']] ?? '') ?></td>
    <td><span class="lbl">Estado civil</span><?= $dash($p['estado_civil']) ?></td>
    <td><span class="lbl">Ocupación</span><?= $dash($p['ocupacion']) ?></td>
    <td><span class="lbl">Escolaridad</span><?= $dash($p['escolaridad']) ?></td>
    <td><span class="lbl">Grupo sanguíneo</span><?= $dash($p['grupo_sanguineo']) ?></td>
  </tr>
  <tr>
    <td colspan="2"><span class="lbl">Dirección</span><?= $dash(trim($p['direccion'] . ' ' . $p['barrio'])) ?></td>
    <td><span class="lbl">Municipio</span><?= $dash(trim($p['municipio'] . ($p['departamento'] ? ', ' . $p['departamento'] : ''), ', ')) ?></td>
    <td><span class="lbl">Zona</span><?= $dash(catalogo('zona')[$p['zona']] ?? null) ?></td>
    <td><span class="lbl">Teléfono</span><?= e($p['telefono']) ?></td>
  </tr>
  <tr>
    <td colspan="2"><span class="lbl">EPS / entidad responsable</span><?= $dash($c['adm_eps']) ?></td>
    <td><span class="lbl">Régimen</span><?= $dash($c['adm_regimen']) ?></td>
    <td><span class="lbl">Tipo de afiliado</span><?= $dash($p['tipo_afiliado']) ?></td>
    <td><span class="lbl">Pertenencia étnica</span><?= $dash($p['etnia']) ?></td>
  </tr>
  <tr>
    <td colspan="3"><span class="lbl">Acompañante</span><?= $dash(trim($p['acompanante_nombre'] . ($p['acompanante_parentesco'] ? ' (' . $p['acompanante_parentesco'] . ')' : '') . ' ' . $p['acompanante_telefono'])) ?></td>
    <td colspan="2"><span class="lbl">Responsable</span><?= $dash(trim($p['responsable_nombre'] . ($p['responsable_parentesco'] ? ' (' . $p['responsable_parentesco'] . ')' : '') . ' ' . $p['responsable_telefono'])) ?></td>
  </tr>
</table>

<h2 class="sec">Datos de la atención</h2>
<table class="grid">
  <tr>
    <td style="width:20%"><span class="lbl">Ingreso</span><?= e(fecha($c['admision_fecha'], true)) ?></td>
    <td style="width:20%"><span class="lbl">Tipo de consulta</span><?= e($c['tipo_consulta']) ?></td>
    <td style="width:20%"><span class="lbl">Modalidad</span><?= e($c['modalidad']) ?></td>
    <td style="width:20%"><span class="lbl">Finalidad</span><?= e($c['finalidad']) ?></td>
    <td style="width:20%"><span class="lbl">Causa externa</span><?= e($c['causa_externa']) ?></td>
  </tr>
  <tr>
    <td><span class="lbl">Finalización</span><?= e(fecha($c['cerrada_en'], true)) ?></td>
    <td><span class="lbl">N.º autorización</span><?= $dash($c['numero_autorizacion']) ?></td>
    <td colspan="3"><span class="lbl">Médico tratante</span><?= e($c['med_nombres'] . ' ' . $c['med_apellidos']) ?> · RM <?= $dash($c['registro_medico']) ?></td>
  </tr>
</table>

<h2 class="sec">Anamnesis</h2>
<?php $texto('Motivo de consulta', $c['motivo_consulta'], true); ?>
<?php $texto('Enfermedad actual', $c['enfermedad_actual'], true); ?>

<h2 class="sec">Antecedentes</h2>
<table class="grid">
  <?php $keys = array_keys(Clinica::ANTECEDENTES);
  if ($p['sexo'] === 'M') { $keys = array_values(array_diff($keys, ['gineco_obstetricos'])); }
  foreach (array_chunk($keys, 2) as $par): ?>
    <tr>
      <?php foreach ($par as $k): $valor = trim((string) ($ant[$k] ?? '')); ?>
        <td style="width:50%"><span class="lbl"><?= e(Clinica::ANTECEDENTES[$k]) ?></span><?= $valor === '' ? '<span class="muted">No refiere</span>' : nl2br(e($valor)) ?></td>
      <?php endforeach; ?>
      <?php if (count($par) === 1): ?><td></td><?php endif; ?>
    </tr>
  <?php endforeach; ?>
</table>

<?php if (trim((string) $c['revision_sistemas']) !== ''): ?>
  <h2 class="sec">Revisión por sistemas</h2>
  <?php $texto('', $c['revision_sistemas']); ?>
<?php endif; ?>

<h2 class="sec">Examen físico</h2>
<table class="grid">
  <tr>
    <th class="center">TA (mmHg)</th><th class="center">FC (lpm)</th><th class="center">FR (rpm)</th><th class="center">T (°C)</th>
    <th class="center">SatO₂ (%)</th><th class="center">Peso (kg)</th><th class="center">Talla (cm)</th><th class="center">IMC</th>
    <th class="center">P. abd. (cm)</th><th class="center">Glucometría</th>
  </tr>
  <tr>
    <td class="center"><?= $c['ta_sistolica'] ? e($c['ta_sistolica'] . '/' . $c['ta_diastolica']) : '—' ?></td>
    <td class="center"><?= $dash($c['frecuencia_cardiaca']) ?></td>
    <td class="center"><?= $dash($c['frecuencia_respiratoria']) ?></td>
    <td class="center"><?= $dash($c['temperatura']) ?></td>
    <td class="center"><?= $dash($c['saturacion']) ?></td>
    <td class="center"><?= $c['peso'] ? e((float) $c['peso']) : '—' ?></td>
    <td class="center"><?= $c['talla'] ? e((float) $c['talla']) : '—' ?></td>
    <td class="center"><?= $dash($c['imc']) ?></td>
    <td class="center"><?= $c['perimetro_abdominal'] ? e((float) $c['perimetro_abdominal']) : '—' ?></td>
    <td class="center"><?= $dash($c['glucometria']) ?></td>
  </tr>
</table>
<div style="margin-top:6px">
  <?php foreach (Clinica::EXAMEN as $k => $label) { $texto($label, $c[$k]); } ?>
</div>

<?php if (trim((string) $c['paraclinicos']) !== ''): ?>
  <h2 class="sec">Paraclínicos aportados</h2>
  <?php $texto('', $c['paraclinicos']); ?>
<?php endif; ?>

<h2 class="sec">Impresión diagnóstica</h2>
<table class="grid">
  <tr><th style="width:13%">Tipo</th><th style="width:10%">CIE-10</th><th>Descripción</th><th style="width:20%">Clase</th></tr>
  <?php foreach ($dx as $d): ?>
    <tr><td><?= e($d['tipo']) ?></td><td><strong><?= e($d['codigo']) ?></strong></td><td><?= e($d['descripcion']) ?></td><td><?= e($d['clase']) ?></td></tr>
  <?php endforeach; ?>
</table>

<h2 class="sec">Análisis y plan de manejo</h2>
<?php $texto('Análisis', $c['analisis']); ?>
<?php $texto('Plan de manejo / conducta', $c['plan_manejo'], true); ?>
<?php $texto('Recomendaciones y signos de alarma', $c['recomendaciones']); ?>
<?php $texto('Próximo control', $c['proximo_control']); ?>

<?php if ($items): ?>
  <h2 class="sec">Fórmula médica</h2>
  <table class="grid">
    <tr><th style="width:4%">#</th><th style="width:36%">Medicamento</th><th>Posología</th><th style="width:18%" class="right">Cantidad</th></tr>
    <?php foreach ($items as $i => $it): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><strong><?= e($it['medicamento']) ?></strong> <?= e($it['concentracion']) ?><br><span class="muted"><?= e(trim($it['forma_farmaceutica'] . ' · vía ' . mb_strtolower((string) $it['via']), ' ·')) ?></span></td>
        <td><?= e(implode(' · ', array_filter([$it['dosis'], $it['frecuencia'], $it['duracion']]))) ?><?= $it['indicaciones'] ? '<br><span class="muted">' . e($it['indicaciones']) . '</span>' : '' ?></td>
        <td class="right"><?= (int) $it['cantidad'] ?> (<?= e($it['cantidad_letras']) ?>)</td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php if ($ordenes): ?>
  <h2 class="sec">Órdenes</h2>
  <table class="grid">
    <tr><th style="width:22%">Tipo</th><th style="width:12%">CUPS</th><th>Descripción</th><th style="width:8%" class="right">Cant.</th></tr>
    <?php foreach ($ordenes as $o): ?>
      <tr><td><?= e($o['tipo']) ?></td><td><?= $dash($o['codigo']) ?></td><td><?= e($o['descripcion']) ?><?= $o['observacion'] ? '<br><span class="muted">' . e($o['observacion']) . '</span>' : '' ?></td><td class="right"><?= (int) $o['cantidad'] ?></td></tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php if ($c['incapacidad_dias']): ?>
  <h2 class="sec">Incapacidad</h2>
  <p class="txt"><?= (int) $c['incapacidad_dias'] ?> (<?= e(numero_letras((int) $c['incapacidad_dias'])) ?>) día(s), desde el <?= e(fecha($c['incapacidad_desde'])) ?> hasta el <?= e(fecha(date('Y-m-d', strtotime($c['incapacidad_desde'] . ' +' . ((int) $c['incapacidad_dias'] - 1) . ' day')))) ?>.</p>
<?php endif; ?>

<?php if ($notas): ?>
  <h2 class="sec">Notas aclaratorias</h2>
  <?php foreach ($notas as $n): ?>
    <div class="nota"><span class="lbl"><?= e(fecha($n['creado_en'], true) . ' · ' . $n['nombres'] . ' ' . $n['apellidos']) ?></span><?= nl2br(e($n['nota'])) ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_firma.php'; ?>
