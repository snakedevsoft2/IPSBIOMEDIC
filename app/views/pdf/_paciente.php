<?php
// Bloque compacto de identificación del paciente. Variables: $p, $c
$sexo = catalogo('sexo')[$p['sexo']] ?? $p['sexo'];
$fotoUri = !empty($p['foto']) ? image_data_uri($p['foto']) : '';
?>
<table style="width:100%;border-collapse:collapse">
  <tr>
    <?php if ($fotoUri): ?>
    <td style="width:56px;padding:0 8px 0 0;vertical-align:top"><img src="<?= $fotoUri ?>" class="foto-paciente" alt="Foto del paciente"></td>
    <?php endif; ?>
    <td style="padding:0">
      <table class="grid">
        <tr>
          <td style="width:40%" colspan="2"><span class="lbl">Paciente</span><span class="big"><?= e(nombre_paciente($p)) ?></span></td>
          <td style="width:22%"><span class="lbl">Documento</span><?= e($p['tipo_documento'] . ' ' . $p['numero_documento']) ?></td>
          <td style="width:19%"><span class="lbl">Edad</span><?= e(edad($p['fecha_nacimiento'], $c['cerrada_en'] ?? $c['creado_en'])) ?></td>
          <td style="width:19%"><span class="lbl">Sexo</span><?= e($sexo) ?></td>
        </tr>
        <tr>
          <td colspan="2"><span class="lbl">EPS / entidad</span><?= e($c['adm_eps'] ?? $p['eps_nombre'] ?? 'Sin EPS') ?></td>
          <td><span class="lbl">Régimen</span><?= e($c['adm_regimen'] ?? $p['regimen']) ?></td>
          <td><span class="lbl">Teléfono</span><?= e($p['telefono']) ?></td>
          <td><span class="lbl">Fecha de atención</span><?= e(fecha($c['cerrada_en'] ?? $c['creado_en'])) ?></td>
        </tr>
      </table>
    </td>
  </tr>
</table>
