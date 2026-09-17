<?php
$puedeEditar = has_role('administrador', 'recepcion');
$acciones = '';
if ($puedeEditar) {
    $acciones = '<a class="btn btn-light border" href="' . url('pacientes/editar', ['id' => $p['id']]) . '"><i class="bi bi-pencil"></i> Editar datos</a>'
        . '<a class="btn btn-primary" href="' . url('admisiones/crear', ['paciente_id' => $p['id']]) . '"><i class="bi bi-box-arrow-in-right"></i> Admitir a sala de espera</a>';
}
if (has_role('administrador', 'medico')) {
    $acciones .= '<a class="btn btn-outline-primary" href="' . url('consultas/rapida', ['paciente_id' => $p['id']]) . '"><i class="bi bi-capsule"></i> Evolución / fórmula rápida</a>';
}
$dato = static fn ($v): string => ($v === null || $v === '') ? '<span class="text-muted">—</span>' : e($v);
echo page_head('Ficha del paciente', 'Registro desde ' . fecha($p['creado_en']), $acciones);
?>
<div class="patient-banner">
  <?php if ($foto): ?>
    <img src="<?= e($foto) ?>" alt="Foto de <?= e(nombre_paciente($p)) ?>" class="avatar avatar-lg" style="object-fit:cover">
  <?php else: ?>
    <span class="avatar avatar-lg"><?= e(iniciales(nombre_paciente($p))) ?></span>
  <?php endif; ?>
  <div class="flex-grow-1">
    <div class="pb-name"><?= e(nombre_paciente($p)) ?></div>
    <div class="pb-meta">
      <span><i class="bi bi-person-vcard"></i><?= e($p['tipo_documento'] . ' ' . $p['numero_documento']) ?></span>
      <span><i class="bi bi-calendar-heart"></i><?= e(edad($p['fecha_nacimiento'])) ?> · <?= e(catalogo('sexo')[$p['sexo']] ?? '') ?></span>
      <span><i class="bi bi-hospital"></i><?= e($p['eps_nombre'] ?? 'Sin EPS') ?> · <?= e($p['regimen']) ?></span>
      <span><i class="bi bi-telephone"></i><?= e($p['telefono']) ?></span>
    </div>
  </div>
  <?php if ($ant && trim((string) $ant['alergicos']) !== ''): ?>
    <div class="alert-alergia"><i class="bi bi-exclamation-triangle-fill"></i> Alergias: <?= e($ant['alergicos']) ?></div>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-lg-<?= $clinico ? '5' : '12' ?>">
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-person"></i>Datos personales</h2></div>
      <div class="card-body">
        <dl class="dl-grid">
          <div><dt>Fecha de nacimiento</dt><dd><?= e(fecha($p['fecha_nacimiento'])) ?></dd></div>
          <div><dt>Estado civil</dt><dd><?= $dato($p['estado_civil']) ?></dd></div>
          <div><dt>Ocupación</dt><dd><?= $dato($p['ocupacion']) ?></dd></div>
          <div><dt>Escolaridad</dt><dd><?= $dato($p['escolaridad']) ?></dd></div>
          <div><dt>Grupo sanguíneo</dt><dd><?= $dato($p['grupo_sanguineo']) ?></dd></div>
          <div><dt>Pertenencia étnica</dt><dd><?= $dato($p['etnia']) ?></dd></div>
          <div><dt>Nacionalidad</dt><dd><?= $dato($p['nacionalidad']) ?></dd></div>
        </dl>
      </div>
    </div>
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-geo-alt"></i>Contacto y aseguramiento</h2></div>
      <div class="card-body">
        <dl class="dl-grid">
          <div><dt>Dirección</dt><dd><?= $dato(trim($p['direccion'] . ' ' . ($p['barrio'] ? '· ' . $p['barrio'] : ''))) ?></dd></div>
          <div><dt>Municipio</dt><dd><?= $dato(trim($p['municipio'] . ($p['departamento'] ? ', ' . $p['departamento'] : ''), ', ')) ?></dd></div>
          <div><dt>Zona</dt><dd><?= $dato(catalogo('zona')[$p['zona']] ?? null) ?></dd></div>
          <div><dt>Teléfono alterno</dt><dd><?= $dato($p['telefono2']) ?></dd></div>
          <div><dt>Correo</dt><dd><?= $dato($p['email']) ?></dd></div>
          <div><dt>Tipo de afiliado</dt><dd><?= $dato($p['tipo_afiliado']) ?></dd></div>
          <div><dt>Acompañante</dt><dd><?= $dato(trim($p['acompanante_nombre'] . ($p['acompanante_parentesco'] ? ' (' . $p['acompanante_parentesco'] . ')' : '') . ' ' . $p['acompanante_telefono'])) ?></dd></div>
          <div><dt>Responsable</dt><dd><?= $dato(trim($p['responsable_nombre'] . ($p['responsable_parentesco'] ? ' (' . $p['responsable_parentesco'] . ')' : '') . ' ' . $p['responsable_telefono'])) ?></dd></div>
        </dl>
        <?php if ($p['observaciones']): ?>
          <hr><div class="hc-field-label">Observaciones</div><div class="readonly-block"><?= e($p['observaciones']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($clinico): ?>
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-journal-medical"></i>Historial de atenciones</h2><span class="text-muted small"><?= count($consultas) ?> registro(s)</span></div>
      <?php if (!$consultas): ?>
        <div class="empty-state"><i class="bi bi-journal"></i>El paciente aún no tiene atenciones médicas.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>Fecha</th><th>Diagnóstico principal</th><th>Médico</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($consultas as $c): ?>
              <tr>
                <td class="text-nowrap"><?= e(fecha($c['cerrada_en'] ?? $c['creado_en'], true)) ?><div class="cell-sub"><?= e($c['tipo_consulta']) ?></div></td>
                <td><?= $c['dx'] ? e($c['dx']) : badge_estado($c['estado']) ?></td>
                <td class="small"><?= e($c['nombres'] . ' ' . $c['apellidos']) ?></td>
                <td class="table-actions">
                  <a class="btn btn-sm btn-light border" href="<?= url('consultas/ver', ['id' => $c['id']]) ?>" title="Ver historia"><i class="bi bi-eye"></i></a>
                  <?php if ($c['estado'] === 'cerrada'): ?>
                    <a class="btn btn-sm btn-light border" target="_blank" href="<?= url('pdf/historia', ['id' => $c['id']]) ?>" title="PDF historia clínica"><i class="bi bi-file-earmark-pdf"></i></a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-clipboard2-heart"></i>Antecedentes registrados</h2></div>
      <div class="card-body">
        <?php $hay = false; foreach (Clinica::ANTECEDENTES as $k => $label): if (!$ant || trim((string) $ant[$k]) === '') continue; $hay = true; ?>
          <div class="hc-field"><div class="hc-field-label"><?= e($label) ?></div><div class="readonly-block"><?= e($ant[$k]) ?></div></div>
        <?php endforeach; ?>
        <?php if (!$hay): ?><p class="text-muted mb-0">Sin antecedentes registrados. El médico los diligencia durante la consulta.</p><?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header"><h2><i class="bi bi-door-open"></i>Admisiones recientes</h2></div>
  <?php if (!$admisiones): ?>
    <div class="empty-state"><i class="bi bi-door-closed"></i>Sin admisiones registradas.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Fecha</th><th>Tipo de consulta</th><th>Motivo (recepción)</th><th>Médico</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($admisiones as $a): ?>
          <tr>
            <td class="text-nowrap"><?= e(fecha($a['fecha_hora'], true)) ?></td>
            <td><?= e($a['tipo_consulta']) ?></td>
            <td class="small"><?= e(mb_strimwidth((string) $a['motivo'], 0, 80, '…')) ?></td>
            <td class="small"><?= $a['nombres'] ? e($a['nombres'] . ' ' . $a['apellidos']) : '—' ?></td>
            <td><?= badge_estado($a['estado']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
