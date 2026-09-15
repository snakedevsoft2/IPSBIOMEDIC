<?php
$val = static fn (string $k): string => e($data[$k] ?? '');
$inv = static fn (string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$fb = static fn (string $k): string => isset($errors[$k]) ? '<div class="invalid-feedback">' . e($errors[$k]) . '</div>' : '';
echo page_head('Nueva admisión', 'Paso ' . ($paciente ? '2 de 2 · Datos de la consulta' : '1 de 2 · Identificar al paciente'));
?>

<?php if (!$paciente): ?>
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body p-4">
          <h2 class="card-title-b mb-1"><i class="bi bi-search text-turquesa"></i>Buscar paciente</h2>
          <p class="text-muted mb-3">Escriba el número de documento o el nombre. Si el paciente no existe, regístrelo primero.</p>
          <form method="get" class="d-flex gap-2">
            <input type="hidden" name="r" value="admisiones/crear">
            <div class="flex-grow-1">
              <input type="text" name="documento" class="form-control form-control-lg" placeholder="Documento o nombre del paciente"
                     value="<?= e($noEncontrado) ?>" data-paciente-search="index.php?r=admisiones/crear&amp;paciente_id=__id__" autofocus>
            </div>
            <button class="btn btn-primary px-4" type="submit">Buscar</button>
          </form>

          <?php if ($noEncontrado !== ''): ?>
            <div class="alert alert-warning mt-3 mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
              <span><i class="bi bi-person-exclamation me-1"></i>No hay un paciente con documento <strong><?= e($noEncontrado) ?></strong>.</span>
              <a class="btn btn-sm btn-primary" href="<?= url('pacientes/crear', ['documento' => $noEncontrado, 'volver' => 'admision']) ?>"><i class="bi bi-person-plus"></i> Registrar paciente</a>
            </div>
          <?php endif; ?>

          <hr class="my-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="text-muted">¿Paciente nuevo en la IPS?</span>
            <a class="btn btn-outline-primary" href="<?= url('pacientes/crear', ['volver' => 'admision']) ?>"><i class="bi bi-person-plus"></i> Registrar paciente nuevo</a>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="patient-banner">
    <span class="avatar avatar-lg"><?= e(iniciales(nombre_paciente($paciente))) ?></span>
    <div class="flex-grow-1">
      <div class="pb-name"><?= e(nombre_paciente($paciente)) ?></div>
      <div class="pb-meta">
        <span><i class="bi bi-person-vcard"></i><?= e($paciente['tipo_documento'] . ' ' . $paciente['numero_documento']) ?></span>
        <span><i class="bi bi-calendar-heart"></i><?= e(edad($paciente['fecha_nacimiento'])) ?> · <?= e(catalogo('sexo')[$paciente['sexo']] ?? '') ?></span>
        <span><i class="bi bi-telephone"></i><?= e($paciente['telefono']) ?></span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-light border" href="<?= url('pacientes/editar', ['id' => $paciente['id'], 'volver' => 'admision']) ?>"><i class="bi bi-pencil"></i> Actualizar datos</a>
      <a class="btn btn-sm btn-light border" href="<?= url('admisiones/crear') ?>">Cambiar paciente</a>
    </div>
  </div>

  <?php if (isset($errors['general'])): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= e($errors['general']) ?></div>
  <?php elseif ($errors): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i>Revise los campos marcados.</div>
  <?php endif; ?>

  <form method="post" action="<?= url('admisiones/crear', ['paciente_id' => $paciente['id']]) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-clipboard2-plus"></i>Datos de la consulta</h2></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Tipo de consulta<span class="req">*</span></label>
            <select name="tipo_consulta" class="form-select<?= $inv('tipo_consulta') ?>"><?= options(catalogo('tipo_consulta'), $data['tipo_consulta'], '') ?></select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Modalidad<span class="req">*</span></label>
            <select name="modalidad" class="form-select<?= $inv('modalidad') ?>"><?= options(catalogo('modalidad'), $data['modalidad'], '') ?></select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Prioridad</label>
            <select name="prioridad" class="form-select"><?= options(catalogo('prioridad'), $data['prioridad'], '') ?></select>
            <div class="form-text">Prioritaria: embarazadas, adultos mayores, niños, personas con discapacidad o urgencia.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Finalidad de la consulta<span class="req">*</span></label>
            <select name="finalidad" class="form-select<?= $inv('finalidad') ?>"><?= options(catalogo('finalidad'), $data['finalidad'], '') ?></select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Causa externa<span class="req">*</span></label>
            <select name="causa_externa" class="form-select<?= $inv('causa_externa') ?>"><?= options(catalogo('causa_externa'), $data['causa_externa'], '') ?></select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Médico asignado</label>
            <select name="medico_id" class="form-select<?= $inv('medico_id') ?>"><?= options($medicos, $data['medico_id'], 'Cualquier médico disponible') ?></select>
            <?= $fb('medico_id') ?>
          </div>
          <div class="col-12">
            <label class="form-label">Motivo de consulta referido por el paciente<span class="req">*</span></label>
            <textarea name="motivo" class="form-control<?= $inv('motivo') ?>" rows="2" placeholder="Con las palabras del paciente. Ej.: «dolor de cabeza desde hace 3 días»"><?= $val('motivo') ?></textarea>
            <?= $fb('motivo') ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-shield-plus"></i>Facturación y autorización</h2></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">EPS / pagador</label>
            <select name="eps_id" class="form-select<?= $inv('eps_id') ?>"><?= options($epsLista, $data['eps_id']) ?></select>
            <?= $fb('eps_id') ?>
          </div>
          <div class="col-md-3">
            <label class="form-label">Régimen<span class="req">*</span></label>
            <select name="regimen" class="form-select<?= $inv('regimen') ?>"><?= options(catalogo('regimen'), $data['regimen']) ?></select>
          </div>
          <div class="col-md-3">
            <label class="form-label">N.º de autorización</label>
            <input name="numero_autorizacion" value="<?= $val('numero_autorizacion') ?>" class="form-control" maxlength="40">
          </div>
          <div class="col-md-2">
            <label class="form-label">Copago / cuota</label>
            <div class="input-group"><span class="input-group-text">$</span><input name="valor_copago" value="<?= $val('valor_copago') ?>" class="form-control" inputmode="numeric"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
      <a href="<?= url('admisiones') ?>" class="btn btn-light border">Cancelar</a>
      <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Enviar a sala de espera</button>
    </div>
  </form>
<?php endif; ?>
