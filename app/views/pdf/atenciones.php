<?php
$docTitulo = 'INFORME DE ATENCIONES';
$docNumero = 'Medicina general · ' . number_format($total, 0, ',', '.') . ' atención(es)';
$docFecha = 'Del ' . fecha($f['desde']) . ' al ' . fecha($f['hasta']);
require __DIR__ . '/_head.php';
?>

<table class="grid" style="margin-top:0">
  <tr>
    <td style="width:25%"><span class="lbl">Periodo</span><?= e(fecha($f['desde']) . ' a ' . fecha($f['hasta'])) ?></td>
    <td style="width:25%"><span class="lbl">Médico</span><?= e($medicoNombre ?: 'Todos') ?></td>
    <td style="width:25%"><span class="lbl">EPS</span><?= e($epsNombre ?: 'Todas') ?></td>
    <td style="width:25%"><span class="lbl">Régimen</span><?= e($f['regimen'] ?: 'Todos') ?></td>
  </tr>
</table>

<table class="grid" style="margin-top:8px">
  <tr>
    <th style="width:9%">Fecha</th>
    <th style="width:18%">Paciente</th>
    <th style="width:11%">Documento</th>
    <th style="width:4%">Edad</th>
    <th style="width:4%">Sexo</th>
    <th style="width:13%">EPS</th>
    <th style="width:9%">Régimen</th>
    <th style="width:9%">Tipo</th>
    <th>Diagnóstico principal</th>
    <th style="width:12%">Médico</th>
  </tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td class="nowrap"><?= e(date('d/m/Y', strtotime($r['cerrada_en']))) ?></td>
      <td><?= e(nombre_paciente($r)) ?></td>
      <td class="nowrap"><?= e($r['tipo_documento'] . ' ' . $r['numero_documento']) ?></td>
      <td class="center"><?= (int) $r['edad_anios'] ?></td>
      <td class="center"><?= e($r['sexo']) ?></td>
      <td><?= e($r['eps'] ?? '—') ?></td>
      <td><?= e($r['regimen']) ?></td>
      <td><?= e($r['tipo_consulta']) ?></td>
      <td><strong><?= e($r['dx_codigo']) ?></strong> <?= e($r['dx_descripcion']) ?></td>
      <td><?= e($r['med_nombres'] . ' ' . $r['med_apellidos']) ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php if ($total > count($rows)): ?>
  <p class="muted" style="margin-top:8px">Se muestran las primeras <?= count($rows) ?> atenciones de <?= number_format($total, 0, ',', '.') ?>. Para el listado completo use la exportación a Excel.</p>
<?php endif; ?>
</body>
</html>
