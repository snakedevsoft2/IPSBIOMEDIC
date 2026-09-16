<?php
$val = static fn (string $k): string => e($data[$k] ?? '');
$sel = static fn (string $k) => $data[$k] ?? null;
$inv = static fn (string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$fb = static fn (string $k): string => isset($errors[$k]) ? '<div class="invalid-feedback">' . e($errors[$k]) . '</div>' : '';
$editando = $paciente !== null;
$params = array_filter(['id' => $paciente['id'] ?? null, 'volver' => $volver]);
$cancelar = $volver === 'admision' ? url('admisiones/crear') : ($editando ? url('pacientes/ver', ['id' => $paciente['id']]) : url('pacientes'));

echo page_head(
    $editando ? 'Editar paciente' : 'Registrar paciente',
    $editando ? nombre_paciente($paciente) : 'Datos de identificación y afiliación (recepción)'
);
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i>Revise los campos marcados en rojo.</div>
<?php endif; ?>

<form method="post" action="<?= url($editando ? 'pacientes/editar' : 'pacientes/crear', $params) ?>" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>

  <div class="card mb-3">
    <div class="card-header"><h2><i class="bi bi-person-vcard"></i>Identificación</h2></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Tipo de documento<span class="req">*</span></label>
          <select name="tipo_documento" class="form-select<?= $inv('tipo_documento') ?>"><?= options(catalogo('tipo_documento'), $sel('tipo_documento')) ?></select>
          <?= $fb('tipo_documento') ?>
        </div>
        <div class="col-md-4">
          <label class="form-label">Número de documento<span class="req">*</span></label>
          <input name="numero_documento" value="<?= $val('numero_documento') ?>" class="form-control<?= $inv('numero_documento') ?>" maxlength="20" autofocus>
          <?= $fb('numero_documento') ?>
        </div>
        <div class="col-md-4">
          <label class="form-label">Nacionalidad</label>
          <input name="nacionalidad" value="<?= $val('nacionalidad') ?>" class="form-control" maxlength="60">
        </div>
        <div class="col-md-3">
          <label class="form-label">Primer nombre<span class="req">*</span></label>
          <input name="primer_nombre" value="<?= $val('primer_nombre') ?>" class="form-control<?= $inv('primer_nombre') ?>" maxlength="60">
          <?= $fb('primer_nombre') ?>
        </div>
        <div class="col-md-3">
          <label class="form-label">Segundo nombre</label>
          <input name="segundo_nombre" value="<?= $val('segundo_nombre') ?>" class="form-control" maxlength="60">
        </div>
        <div class="col-md-3">
          <label class="form-label">Primer apellido<span class="req">*</span></label>
          <input name="primer_apellido" value="<?= $val('primer_apellido') ?>" class="form-control<?= $inv('primer_apellido') ?>" maxlength="60">
          <?= $fb('primer_apellido') ?>
        </div>
        <div class="col-md-3">
          <label class="form-label">Segundo apellido</label>
          <input name="segundo_apellido" value="<?= $val('segundo_apellido') ?>" class="form-control" maxlength="60">
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><h2><i class="bi bi-camera"></i>Foto del paciente</h2></div>
    <div class="card-body">
      <div class="row g-3 align-items-center">
        <?php if ($editando && !empty($paciente['foto'])): ?>
          <div class="col-auto">
            <img src="<?= e(image_data_uri($paciente['foto'])) ?>" alt="Foto actual" class="rounded border" style="width:90px;height:90px;object-fit:cover">
          </div>
          <div class="col-auto form-check">
            <input type="checkbox" class="form-check-input" id="quitar_foto" name="quitar_foto" value="1">
            <label class="form-check-label small" for="quitar_foto">Quitar la foto actual</label>
          </div>
          <div class="w-100"></div>
        <?php endif; ?>
        <div class="col-md-6">
          <label class="form-label" for="foto">
            <?= $editando && !empty($paciente['foto']) ? 'Reemplazar foto' : 'Tomar o cargar foto (opcional)' ?>
          </label>
          <input type="file" id="foto" name="foto" class="form-control" accept="image/png,image/jpeg" capture="environment">
          <div class="form-text">En un celular o tableta abre la cámara directamente. Se usa para identificar al paciente en la historia clínica y aparece en los PDF.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><h2><i class="bi bi-person"></i>Datos personales</h2></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Fecha de nacimiento<span class="req">*</span></label>
          <input type="date" name="fecha_nacimiento" value="<?= $val('fecha_nacimiento') ?>" max="<?= date('Y-m-d') ?>" class="form-control<?= $inv('fecha_nacimiento') ?>">
          <?= $fb('fecha_nacimiento') ?>
        </div>
        <div class="col-md-3">
          <label class="form-label">Sexo<span class="req">*</span></label>
          <select name="sexo" class="form-select<?= $inv('sexo') ?>"><?= options(catalogo('sexo'), $sel('sexo')) ?></select>
          <?= $fb('sexo') ?>
        </div>
        <div class="col-md-3">
          <label class="form-label">Estado civil</label>
          <select name="estado_civil" class="form-select"><?= options(catalogo('estado_civil'), $sel('estado_civil')) ?></select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Grupo sanguíneo</label>
          <select name="grupo_sanguineo" class="form-select"><?= options(catalogo('grupo_sanguineo'), $sel('grupo_sanguineo')) ?></select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Escolaridad</label>
          <select name="escolaridad" class="form-select"><?= options(catalogo('escolaridad'), $sel('escolaridad')) ?></select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Ocupación</label>
          <input name="ocupacion" value="<?= $val('ocupacion') ?>" class="form-control" maxlength="100">
        </div>
        <div class="col-md-4">
          <label class="form-label">Pertenencia étnica</label>
          <select name="etnia" class="form-select"><?= options(catalogo('etnia'), $sel('etnia')) ?></select>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><h2><i class="bi bi-geo-alt"></i>Residencia y contacto</h2></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Dirección</label>
          <input name="direccion" value="<?= $val('direccion') ?>" class="form-control" maxlength="150">
        </div>
        <div class="col-md-6">
          <label class="form-label">Barrio / vereda</label>
          <input name="barrio" value="<?= $val('barrio') ?>" class="form-control" maxlength="100">
        </div>
        <div class="col-md-4">
          <label class="form-label">Departamento</label>
          <select name="departamento" class="form-select"><?= options(departamentos(), $sel('departamento')) ?></select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Municipio</label>
          <input name="municipio" value="<?= $val('municipio') ?>" class="form-control" maxlength="100">
        </div>
        <div class="col-md-4">
          <label class="form-label">Zona</label>
          <select name="zona" class="form-select"><?= options(catalogo('zona'), $sel('zona')) ?></select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Teléfono / celular<span class="req">*</span></label>
          <input name="telefono" value="<?= $val('telefono') ?>" class="form-control<?= $inv('telefono') ?>" maxlength="30" inputmode="tel">
          <?= $fb('telefono') ?>
        </div>
        <div class="col-md-4">
          <label class="form-label">Teléfono alterno</label>
          <input name="telefono2" value="<?= $val('telefono2') ?>" class="form-control" maxlength="30" inputmode="tel">
        </div>
        <div class="col-md-4">
          <label class="form-label">Correo electrónico</label>
          <input type="email" name="email" value="<?= $val('email') ?>" class="form-control<?= $inv('email') ?>" maxlength="150">
          <?= $fb('email') ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><h2><i class="bi bi-shield-plus"></i>Aseguramiento</h2></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">EPS / entidad responsable del pago</label>
          <select name="eps_id" class="form-select<?= $inv('eps_id') ?>"><?= options($epsLista, $sel('eps_id')) ?></select>
          <?= $fb('eps_id') ?>
        </div>
        <div class="col-md-3">
          <label class="form-label">Régimen<span class="req">*</span></label>
          <select name="regimen" class="form-select<?= $inv('regimen') ?>"><?= options(catalogo('regimen'), $sel('regimen')) ?></select>
          <?= $fb('regimen') ?>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo de afiliado</label>
          <select name="tipo_afiliado" class="form-select"><?= options(catalogo('tipo_afiliado'), $sel('tipo_afiliado')) ?></select>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><h2><i class="bi bi-people"></i>Acompañante y responsable</h2></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label">Nombre del acompañante</label>
          <input name="acompanante_nombre" value="<?= $val('acompanante_nombre') ?>" class="form-control" maxlength="120">
        </div>
        <div class="col-md-4">
          <label class="form-label">Teléfono del acompañante</label>
          <input name="acompanante_telefono" value="<?= $val('acompanante_telefono') ?>" class="form-control" maxlength="30">
        </div>
        <div class="col-md-3">
          <label class="form-label">Parentesco</label>
          <select name="acompanante_parentesco" class="form-select"><?= options(catalogo('parentesco'), $sel('acompanante_parentesco')) ?></select>
        </div>
        <div class="col-md-5">
          <label class="form-label">Nombre del responsable</label>
          <input name="responsable_nombre" value="<?= $val('responsable_nombre') ?>" class="form-control" maxlength="120">
        </div>
        <div class="col-md-4">
          <label class="form-label">Teléfono del responsable</label>
          <input name="responsable_telefono" value="<?= $val('responsable_telefono') ?>" class="form-control" maxlength="30">
        </div>
        <div class="col-md-3">
          <label class="form-label">Parentesco</label>
          <select name="responsable_parentesco" class="form-select"><?= options(catalogo('parentesco'), $sel('responsable_parentesco')) ?></select>
        </div>
        <div class="col-12">
          <label class="form-label">Observaciones administrativas</label>
          <textarea name="observaciones" class="form-control" rows="2"><?= $val('observaciones') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="<?= $cancelar ?>" class="btn btn-light border">Cancelar</a>
    <button class="btn btn-primary" type="submit">
      <i class="bi bi-check2"></i> <?= $volver === 'admision' ? 'Guardar y continuar con la admisión' : 'Guardar paciente' ?>
    </button>
  </div>
</form>
