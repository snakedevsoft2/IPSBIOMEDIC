<?php
$docTitulo = 'ÓRDENES MÉDICAS';
$docNumero = 'Atención N.º ' . str_pad((string) $c['id'], 6, '0', STR_PAD_LEFT);
$docFecha = 'Expedida: ' . fecha($c['cerrada_en'], true);
require __DIR__ . '/_head.php';
?>

<?php require __DIR__ . '/_paciente.php'; ?>

<h2 class="sec">Diagnósticos</h2>
<table class="grid">
  <?php foreach ($dx as $d): ?>
    <tr><td style="width:14%"><?= e($d['tipo']) ?></td><td style="width:10%"><strong><?= e($d['codigo']) ?></strong></td><td><?= e($d['descripcion']) ?></td></tr>
  <?php endforeach; ?>
</table>

<h2 class="sec">Servicios solicitados</h2>
<table class="grid">
  <tr><th style="width:4%">#</th><th style="width:22%">Tipo</th><th style="width:12%">Código CUPS</th><th>Descripción</th><th style="width:8%" class="right">Cant.</th></tr>
  <?php foreach ($ordenes as $i => $o): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= e($o['tipo']) ?></td>
      <td><?= e($o['codigo'] ?: '—') ?></td>
      <td><strong><?= e($o['descripcion']) ?></strong><?= $o['observacion'] ? '<br><span class="muted">' . e($o['observacion']) . '</span>' : '' ?></td>
      <td class="right"><?= (int) $o['cantidad'] ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php if (trim((string) $c['analisis']) !== ''): ?>
  <h2 class="sec">Resumen clínico / justificación</h2>
  <p class="txt"><?= nl2br(e($c['analisis'])) ?></p>
<?php endif; ?>

<?php require __DIR__ . '/_firma.php'; ?>
