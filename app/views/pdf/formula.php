<?php
$docTitulo = 'FÓRMULA MÉDICA';
$docNumero = 'N.º ' . str_pad((string) $formula['id'], 6, '0', STR_PAD_LEFT) . ' · Atención ' . str_pad((string) $c['id'], 6, '0', STR_PAD_LEFT);
$docFecha = 'Expedida: ' . fecha($c['cerrada_en'], true);
require __DIR__ . '/_head.php';
$principal = $dx[0] ?? null;
?>

<?php require __DIR__ . '/_paciente.php'; ?>

<?php if ($principal): ?>
<table class="grid" style="margin-top:6px">
  <tr>
    <td><span class="lbl">Diagnóstico principal</span><strong><?= e($principal['codigo']) ?></strong> · <?= e($principal['descripcion']) ?>
      <?php if (count($dx) > 1): ?><br><span class="muted">Relacionados: <?= e(implode(', ', array_column(array_slice($dx, 1), 'codigo'))) ?></span><?php endif; ?>
    </td>
  </tr>
</table>
<?php endif; ?>

<table style="width:100%; margin: 12px 0 4px"><tr><td class="rx">Rx</td><td class="right muted">Medicamentos por denominación común internacional</td></tr></table>

<table class="grid">
  <tr>
    <th style="width:4%">#</th>
    <th style="width:30%">Medicamento, concentración y forma</th>
    <th style="width:9%">Vía</th>
    <th>Dosis, frecuencia y duración</th>
    <th style="width:20%" class="right">Cantidad total</th>
  </tr>
  <?php foreach ($items as $i => $it): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><strong style="font-size:9.4px"><?= e($it['medicamento']) ?></strong><br><?= e($it['concentracion']) ?> <?= $it['forma_farmaceutica'] ? '· ' . e($it['forma_farmaceutica']) : '' ?></td>
      <td><?= e($it['via'] ?: '—') ?></td>
      <td>
        <?= e(implode(' · ', array_filter([$it['dosis'], $it['frecuencia'], $it['duracion']])) ?: '—') ?>
        <?php if ($it['indicaciones']): ?><br><span class="muted"><?= e($it['indicaciones']) ?></span><?php endif; ?>
      </td>
      <td class="right"><strong style="font-size:10px"><?= (int) $it['cantidad'] ?></strong><br><span class="muted">(<?= e($it['cantidad_letras']) ?>)</span></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php if (trim((string) $formula['observaciones']) !== ''): ?>
  <h2 class="sec">Observaciones</h2>
  <p class="txt"><?= nl2br(e($formula['observaciones'])) ?></p>
<?php endif; ?>

<?php if (trim((string) $c['recomendaciones']) !== ''): ?>
  <h2 class="sec">Recomendaciones y signos de alarma</h2>
  <p class="txt"><?= nl2br(e($c['recomendaciones'])) ?></p>
<?php endif; ?>

<p class="muted" style="margin-top:10px">Fórmula válida únicamente con la firma del médico tratante. No automedicarse ni suspender el tratamiento sin indicación médica.</p>

<?php require __DIR__ . '/_firma.php'; ?>
