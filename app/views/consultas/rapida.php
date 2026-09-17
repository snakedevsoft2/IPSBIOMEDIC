<?php
$val = static fn (string $k): string => e($data[$k] ?? '');
$inv = static fn (string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$fb = static fn (string $k): string => isset($errors[$k]) ? '<div class="invalid-feedback">' . e($errors[$k]) . '</div>' : '';

$sec = static fn (int $n, string $titulo, string $extra = ''): string =>
    '<div class="card-header"><h2><span class="sec-num">' . $n . '</span>' . e($titulo) . '</h2>' . $extra . '</div>';

$dxRow = static function (string $i, array $r): void { ?>
  <div class="dyn-row" data-row>
    <div class="row g-2 align-items-end">
      <div class="col-auto pb-1"><span class="row-num" data-row-num>1</span></div>
      <div class="col-4 col-md-2">
        <label class="form-label">Código <span class="dx-tipo" data-dx-tipo>Principal</span></label>
        <input class="form-control form-control-sm text-uppercase" name="dx[<?= $i ?>][codigo]" value="<?= e($r['codigo'] ?? '') ?>" data-field="codigo" data-cie maxlength="10" placeholder="J069">
      </div>
      <div class="col">
        <label class="form-label">Diagnóstico (buscar por código o nombre)</label>
        <input class="form-control form-control-sm" name="dx[<?= $i ?>][descripcion]" value="<?= e($r['descripcion'] ?? '') ?>" data-field="descripcion" data-cie maxlength="255">
      </div>
      <div class="col-10 col-md-3">
        <label class="form-label">Tipo de diagnóstico</label>
        <select class="form-select form-select-sm" name="dx[<?= $i ?>][clase]"><?= options(catalogo('clase_diagnostico'), $r['clase'] ?? 'Impresión diagnóstica', '') ?></select>
      </div>
      <div class="col-auto"><button type="button" class="btn-remove" data-remove-row title="Quitar diagnóstico"><i class="bi bi-trash3"></i></button></div>
    </div>
  </div>
<?php };

$medRow = static function (string $i, array $r): void { ?>
  <div class="dyn-row" data-row>
    <div class="d-flex justify-content-between align-items-center mb-2">
      <span class="row-num" data-row-num>1</span>
      <button type="button" class="btn-remove" data-remove-row title="Quitar medicamento"><i class="bi bi-trash3"></i></button>
    </div>
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Medicamento (nombre genérico)<span class="req">*</span></label>
        <input class="form-control form-control-sm" name="med[<?= $i ?>][medicamento]" value="<?= e($r['medicamento'] ?? '') ?>" data-med maxlength="200" placeholder="Escriba para buscar">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Concentración</label>
        <input class="form-control form-control-sm" name="med[<?= $i ?>][concentracion]" value="<?= e($r['concentracion'] ?? '') ?>" data-field="concentracion" maxlength="60">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Forma farmacéutica</label>
        <select class="form-select form-select-sm" name="med[<?= $i ?>][forma_farmaceutica]" data-field="forma_farmaceutica"><?= options(catalogo('forma_farmaceutica'), $r['forma_farmaceutica'] ?? null, '—') ?></select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Vía</label>
        <select class="form-select form-select-sm" name="med[<?= $i ?>][via]"><?= options(catalogo('via'), array_key_exists('via', $r) ? $r['via'] : 'Oral', '—') ?></select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Cantidad total</label>
        <input type="number" min="1" max="9999" class="form-control form-control-sm" name="med[<?= $i ?>][cantidad]" value="<?= e($r['cantidad'] ?? 1) ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Dosis</label>
        <input class="form-control form-control-sm" name="med[<?= $i ?>][dosis]" value="<?= e($r['dosis'] ?? '') ?>" maxlength="80" placeholder="1 tableta">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Frecuencia</label>
        <input class="form-control form-control-sm" name="med[<?= $i ?>][frecuencia]" value="<?= e($r['frecuencia'] ?? '') ?>" list="dl-frecuencia" maxlength="80">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Duración</label>
        <input class="form-control form-control-sm" name="med[<?= $i ?>][duracion]" value="<?= e($r['duracion'] ?? '') ?>" list="dl-duracion" maxlength="60">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Indicaciones</label>
        <input class="form-control form-control-sm" name="med[<?= $i ?>][indicaciones]" value="<?= e($r['indicaciones'] ?? '') ?>" placeholder="Después de las comidas">
      </div>
    </div>
  </div>
<?php };

push_script('<script>window.MEDICAMENTOS = ' . json_encode($medicamentos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . ';</script>');
echo page_head('Evolución y fórmula rápida', $paciente ? nombre_paciente($paciente) : 'Paso 1 de 2 · Identificar al paciente');
?>

<?php if (!$paciente): ?>
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body p-4">
          <h2 class="card-title-b mb-1"><i class="bi bi-search text-turquesa"></i>Buscar paciente</h2>
          <p class="text-muted mb-3">Para una evolución o fórmula rápida (control corto, sin diligenciar toda la historia clínica). Escriba el número de documento o el nombre.</p>
          <form method="get" class="d-flex gap-2">
            <input type="hidden" name="r" value="consultas/rapida">
            <div class="flex-grow-1">
              <input type="text" name="documento" class="form-control form-control-lg" placeholder="Documento o nombre del paciente"
                     value="<?= e($noEncontrado) ?>" data-paciente-search="index.php?r=consultas/rapida&amp;paciente_id=__id__" autofocus>
            </div>
            <button class="btn btn-primary px-4" type="submit">Buscar</button>
          </form>

          <?php if ($noEncontrado !== ''): ?>
            <div class="alert alert-warning mt-3 mb-0"><i class="bi bi-person-exclamation me-1"></i>No hay un paciente con documento <strong><?= e($noEncontrado) ?></strong>.</div>
          <?php endif; ?>
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
        <span><i class="bi bi-hospital"></i><?= e($paciente['eps_nombre'] ?? 'Sin EPS') ?></span>
      </div>
    </div>
    <a class="btn btn-sm btn-light border" href="<?= url('consultas/rapida') ?>">Cambiar paciente</a>
  </div>

  <?php if ($activa): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-circle me-1"></i>Este paciente ya tiene una admisión activa hoy (en espera o en atención).
      Atiéndalo desde <a href="<?= url('admisiones') ?>">Sala de espera</a> para no duplicar la atención.
    </div>
  <?php else: ?>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i>Revise los campos marcados en rojo.</div>
  <?php endif; ?>

  <datalist id="dl-frecuencia">
    <?php foreach (['Cada 4 horas', 'Cada 6 horas', 'Cada 8 horas', 'Cada 12 horas', 'Cada 24 horas', 'Una vez al día', 'Dos veces al día', 'Antes de dormir', 'Si hay dolor o fiebre', 'Dosis única'] as $o): ?>
      <option value="<?= e($o) ?>">
    <?php endforeach; ?>
  </datalist>
  <datalist id="dl-duracion">
    <?php foreach (['1 día', '3 días', '5 días', '7 días', '10 días', '14 días', '30 días', '3 meses', 'Uso continuo'] as $o): ?>
      <option value="<?= e($o) ?>">
    <?php endforeach; ?>
  </datalist>

  <form method="post" action="<?= url('consultas/rapida', ['paciente_id' => $paciente['id']]) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-lg-9">

        <div class="card hc-section mb-3">
          <?= $sec(1, 'Motivo y evolución') ?>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label" for="motivo_consulta">Motivo de consulta<span class="req">*</span></label>
              <textarea class="form-control<?= $inv('motivo_consulta') ?>" id="motivo_consulta" name="motivo_consulta" rows="2"><?= $val('motivo_consulta') ?></textarea>
              <?= $fb('motivo_consulta') ?>
            </div>
            <div>
              <label class="form-label" for="enfermedad_actual">Evolución<span class="req">*</span></label>
              <textarea class="form-control<?= $inv('enfermedad_actual') ?>" id="enfermedad_actual" name="enfermedad_actual" rows="4" placeholder="Cómo ha evolucionado el paciente desde la última atención..."><?= $val('enfermedad_actual') ?></textarea>
              <?= $fb('enfermedad_actual') ?>
            </div>
          </div>
        </div>

        <div class="card hc-section mb-3">
          <?= $sec(2, 'Diagnósticos (CIE-10)', '<button type="button" class="btn btn-sm btn-soft" data-add-row="dx-rows"><i class="bi bi-plus-lg"></i> Agregar</button>') ?>
          <div class="card-body">
            <div id="dx-rows" data-rows data-template="tpl-dx">
              <?php foreach ($dx as $i => $row) { $dxRow((string) $i, $row); } ?>
            </div>
            <div class="text-muted small" data-empty-for="dx-rows" <?= $dx ? 'hidden' : '' ?>>Aún no hay diagnósticos. El primero que agregue será el diagnóstico principal.</div>
            <?php if (isset($errors['dx'])): ?><div class="text-danger small mt-1"><?= e($errors['dx']) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="card hc-section mb-3">
          <?= $sec(3, 'Plan de manejo') ?>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label" for="plan_manejo">Plan de manejo / conducta<span class="req">*</span></label>
              <textarea class="form-control<?= $inv('plan_manejo') ?>" id="plan_manejo" name="plan_manejo" rows="3"><?= $val('plan_manejo') ?></textarea>
              <?= $fb('plan_manejo') ?>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="proximo_control">Próximo control</label>
              <input class="form-control" id="proximo_control" name="proximo_control" value="<?= $val('proximo_control') ?>" maxlength="120" placeholder="Ej. En 1 mes con resultados">
            </div>
          </div>
        </div>

        <div class="card hc-section mb-3">
          <?= $sec(4, 'Fórmula médica', '<button type="button" class="btn btn-sm btn-soft" data-add-row="med-rows"><i class="bi bi-plus-lg"></i> Agregar medicamento</button>') ?>
          <div class="card-body">
            <div id="med-rows" data-rows data-template="tpl-med">
              <?php foreach ($items as $i => $row) { $medRow((string) $i, $row); } ?>
            </div>
            <div class="text-muted small mb-3" data-empty-for="med-rows" <?= $items ? 'hidden' : '' ?>>Sin medicamentos formulados.</div>
            <label class="form-label" for="formula_observaciones">Observaciones para el paciente (aparecen en la fórmula)</label>
            <textarea class="form-control" id="formula_observaciones" name="formula_observaciones" rows="2"><?= e(input('formula_observaciones')) ?></textarea>
          </div>
        </div>
      </div>

      <div class="col-lg-3">
        <div class="hc-aside">
          <div class="card mb-3">
            <div class="card-body d-grid gap-2">
              <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Guardar y cerrar atención</button>
              <div class="form-text">Se registra como una atención cerrada de una sola vez, con evolución, diagnóstico y fórmula. Para examen físico completo, antecedentes u órdenes use la atención desde sala de espera.</div>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><h2 class="fs-6"><i class="bi bi-clock-history"></i>Atenciones anteriores</h2></div>
            <?php if (!$previas): ?>
              <div class="card-body text-muted small">Primera atención del paciente en la IPS.</div>
            <?php else: ?>
              <div class="list-group list-group-flush">
                <?php foreach ($previas as $pr): ?>
                  <a class="list-group-item list-group-item-action small" target="_blank" href="<?= url('consultas/ver', ['id' => $pr['id']]) ?>">
                    <div class="fw-semibold"><?= e(fecha($pr['cerrada_en'])) ?></div>
                    <div class="text-truncate"><?= e($pr['dx'] ?? 'Sin diagnóstico') ?></div>
                    <div class="text-muted"><?= e($pr['nombres'] . ' ' . $pr['apellidos']) ?></div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </form>

  <template id="tpl-dx"><?php $dxRow('__i__', []); ?></template>
  <template id="tpl-med"><?php $medRow('__i__', []); ?></template>
  <?php endif; ?>
<?php endif; ?>
