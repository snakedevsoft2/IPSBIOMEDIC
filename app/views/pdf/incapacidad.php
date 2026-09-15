<?php
$docTitulo = 'CERTIFICADO DE INCAPACIDAD';
$docNumero = 'Atención N.º ' . str_pad((string) $c['id'], 6, '0', STR_PAD_LEFT);
$docFecha = 'Expedido: ' . fecha($c['cerrada_en'], true);
require __DIR__ . '/_head.php';
$dias = (int) $c['incapacidad_dias'];
$hasta = date('Y-m-d', strtotime($c['incapacidad_desde'] . ' +' . ($dias - 1) . ' day'));
$principal = $dx[0] ?? null;
?>

<?php require __DIR__ . '/_paciente.php'; ?>

<h2 class="sec">Incapacidad otorgada</h2>
<table class="grid">
  <tr>
    <td style="width:25%"><span class="lbl">Días</span><span class="big"><?= $dias ?></span> (<?= e(numero_letras($dias)) ?>)</td>
    <td style="width:25%"><span class="lbl">Desde</span><span class="big"><?= e(fecha($c['incapacidad_desde'])) ?></span></td>
    <td style="width:25%"><span class="lbl">Hasta</span><span class="big"><?= e(fecha($hasta)) ?></span></td>
    <td style="width:25%"><span class="lbl">Origen</span><?= e($c['causa_externa']) ?></td>
  </tr>
  <?php if ($principal): ?>
  <tr>
    <td colspan="4"><span class="lbl">Diagnóstico principal</span><strong><?= e($principal['codigo']) ?></strong> · <?= e($principal['descripcion']) ?></td>
  </tr>
  <?php endif; ?>
  <tr>
    <td colspan="2"><span class="lbl">Tipo de atención</span>Consulta externa · Medicina general · <?= e($c['modalidad']) ?></td>
    <td colspan="2"><span class="lbl">Ocupación</span><?= e($p['ocupacion'] ?: '—') ?></td>
  </tr>
</table>

<p class="txt" style="margin-top:12px">
  Se certifica que el(la) paciente <strong><?= e(nombre_paciente($p)) ?></strong>, identificado(a) con
  <?= e($p['tipo_documento'] . ' ' . $p['numero_documento']) ?>, fue valorado(a) en consulta de medicina general y requiere
  incapacidad por <?= $dias ?> (<?= e(numero_letras($dias)) ?>) día(s).
</p>

<?php require __DIR__ . '/_firma.php'; ?>
