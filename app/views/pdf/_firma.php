<?php // Firma del médico tratante. Variables: $c, $firma ?>
<table class="firma-wrap" style="width:100%">
  <tr>
    <td style="width:60%; vertical-align:bottom">
      <?php if (!empty($firma)): ?>
        <img src="<?= $firma ?>" class="firma-img" alt=""><br>
      <?php else: ?>
        <div style="height:58px"></div>
      <?php endif; ?>
      <div class="firma-linea">
        <strong><?= e($c['med_nombres'] . ' ' . $c['med_apellidos']) ?></strong><br>
        Médico<?= $c['especialidad'] ? ' · ' . e($c['especialidad']) : ' general' ?><br>
        <?= e($c['med_tipo_doc'] . ' ' . $c['med_documento']) ?> · Registro médico <?= e($c['registro_medico'] ?: '—') ?>
      </div>
    </td>
    <td style="vertical-align:bottom" class="right muted">
      Documento firmado electrónicamente<br>
      <?= e(fecha($c['cerrada_en'], true)) ?>
    </td>
  </tr>
</table>
</body>
</html>
