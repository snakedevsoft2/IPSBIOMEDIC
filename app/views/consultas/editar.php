<?php
$v = static fn (string $k): string => e($c[$k] ?? '');
$alergias = trim((string) ($ant['alergicos'] ?? ''));
push_script('<script>window.MEDICAMENTOS = ' . json_encode($medicamentos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . ';</script>');

$sec = static fn (int $n, string $titulo, string $extra = ''): string =>
    '<div class="card-header"><h2><span class="sec-num">' . $n . '</span>' . e($titulo) . '</h2>' . $extra . '</div>';

$vital = static function (string $name, string $label, string $unit, string $value, string $cols = 'col-6 col-md-3 col-xl-2'): void { ?>
  <div class="<?= $cols ?> vital">
    <label class="form-label" for="f-<?= $name ?>"><?= e($label) ?></label>
    <input class="form-control" id="f-<?= $name ?>" name="<?= $name ?>" value="<?= $value ?>" inputmode="decimal" autocomplete="off">
    <span class="unit"><?= e($unit) ?></span>
  </div>
<?php };

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

$ordRow = static function (string $i, array $r): void { ?>
  <div class="dyn-row" data-row>
    <div class="row g-2 align-items-end">
      <div class="col-auto pb-1"><span class="row-num" data-row-num>1</span></div>
      <div class="col-md-3">
        <label class="form-label">Tipo</label>
        <select class="form-select form-select-sm" name="ord[<?= $i ?>][tipo]"><?= options(catalogo('tipo_orden'), $r['tipo'] ?? 'Laboratorio clínico', '') ?></select>
      </div>
      <div class="col-4 col-md-2">
        <label class="form-label">Código CUPS</label>
        <input class="form-control form-control-sm" name="ord[<?= $i ?>][codigo]" value="<?= e($r['codigo'] ?? '') ?>" maxlength="12">
      </div>
      <div class="col">
        <label class="form-label">Descripción<span class="req">*</span></label>
        <input class="form-control form-control-sm" name="ord[<?= $i ?>][descripcion]" value="<?= e($r['descripcion'] ?? '') ?>" maxlength="255" placeholder="Ej. Hemograma completo">
      </div>
      <div class="col-3 col-md-1">
        <label class="form-label">Cant.</label>
        <input type="number" min="1" max="999" class="form-control form-control-sm" name="ord[<?= $i ?>][cantidad]" value="<?= e($r['cantidad'] ?? 1) ?>">
      </div>
      <div class="col-auto"><button type="button" class="btn-remove" data-remove-row title="Quitar orden"><i class="bi bi-trash3"></i></button></div>
      <div class="col-12">
        <input class="form-control form-control-sm" name="ord[<?= $i ?>][observacion]" value="<?= e($r['observacion'] ?? '') ?>" placeholder="Justificación u observación (opcional)">
      </div>
    </div>
  </div>
<?php };

$secciones = [
    'sec-motivo' => 'Motivo y enfermedad actual', 'sec-antecedentes' => 'Antecedentes', 'sec-sistemas' => 'Revisión por sistemas',
    'sec-examen' => 'Examen físico', 'sec-diagnosticos' => 'Diagnósticos', 'sec-plan' => 'Análisis y plan',
    'sec-formula' => 'Fórmula médica', 'sec-ordenes' => 'Órdenes', 'sec-incapacidad' => 'Incapacidad',
];
?>
<?= page_head('Atención médica', 'Historia clínica · Iniciada ' . fecha($c['creado_en'], true) . ' · Estado: borrador') ?>

<div class="patient-banner">
  <span class="avatar avatar-lg"><?= e(iniciales(nombre_paciente($p))) ?></span>
  <div class="flex-grow-1">
    <div class="pb-name"><?= e(nombre_paciente($p)) ?></div>
    <div class="pb-meta">
      <span><i class="bi bi-person-vcard"></i><?= e($p['tipo_documento'] . ' ' . $p['numero_documento']) ?></span>
      <span><i class="bi bi-calendar-heart"></i><?= e(edad($p['fecha_nacimiento'])) ?> · <?= e(catalogo('sexo')[$p['sexo']] ?? '') ?></span>
      <span><i class="bi bi-hospital"></i><?= e($c['adm_eps'] ?? 'Sin EPS') ?> · <?= e($c['adm_regimen']) ?></span>
      <span><i class="bi bi-clipboard2-pulse"></i><?= e($c['tipo_consulta']) ?> · <?= e($c['causa_externa']) ?></span>
    </div>
  </div>
  <?php if ($alergias !== ''): ?>
    <div class="alert-alergia"><i class="bi bi-exclamation-triangle-fill"></i> Alergias: <?= e($alergias) ?></div>
  <?php endif; ?>
</div>

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

<form id="hc-form" method="post" action="<?= url('consultas/editar', ['id' => $c['id']]) ?>" data-dirty-guard autocomplete="off">
  <?= csrf_field() ?>
  <button type="submit" name="accion" value="guardar" hidden tabindex="-1" aria-hidden="true"></button>

  <div class="row g-3">
    <div class="col-lg-9">

      <div class="card hc-section mb-3" id="sec-motivo">
        <?= $sec(1, 'Motivo de consulta y enfermedad actual') ?>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="motivo_consulta">Motivo de consulta<span class="req">*</span></label>
            <textarea class="form-control" id="motivo_consulta" name="motivo_consulta" rows="2"><?= $v('motivo_consulta') ?></textarea>
            <?php if ($c['adm_motivo']): ?><div class="form-text">Registrado en recepción: «<?= e($c['adm_motivo']) ?>»</div><?php endif; ?>
          </div>
          <div>
            <label class="form-label" for="enfermedad_actual">Enfermedad actual<span class="req">*</span></label>
            <textarea class="form-control" id="enfermedad_actual" name="enfermedad_actual" rows="5" placeholder="Inicio, evolución, características, síntomas asociados, tratamientos recibidos..."><?= $v('enfermedad_actual') ?></textarea>
          </div>
        </div>
      </div>

      <div class="card hc-section mb-3" id="sec-antecedentes">
        <?= $sec(2, 'Antecedentes', '<span class="text-muted small">Se actualizan en la ficha del paciente</span>') ?>
        <div class="card-body">
          <div class="row g-3">
            <?php foreach (Clinica::ANTECEDENTES as $k => $label):
                if ($k === 'gineco_obstetricos' && $p['sexo'] === 'M') continue; ?>
              <div class="col-md-6">
                <label class="form-label" for="ant_<?= $k ?>"><?= e($label) ?></label>
                <textarea class="form-control" id="ant_<?= $k ?>" name="ant_<?= $k ?>" rows="2" placeholder="<?= $k === 'gineco_obstetricos' ? 'G_P_C_A_V_ · FUM · planificación' : 'No refiere' ?>"><?= e($ant[$k] ?? '') ?></textarea>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="card hc-section mb-3" id="sec-sistemas">
        <?= $sec(3, 'Revisión por sistemas') ?>
        <div class="card-body">
          <textarea class="form-control" name="revision_sistemas" rows="3" placeholder="Síntomas referidos por sistemas. Ej.: niega fiebre, disnea, dolor torácico..."><?= $v('revision_sistemas') ?></textarea>
        </div>
      </div>

      <div class="card hc-section mb-3" id="sec-examen">
        <?= $sec(4, 'Signos vitales y examen físico') ?>
        <div class="card-body">
          <div class="row g-3 mb-4">
            <?php
            $vital('ta_sistolica', 'TA sistólica', 'mmHg', $v('ta_sistolica'));
            $vital('ta_diastolica', 'TA diastólica', 'mmHg', $v('ta_diastolica'));
            $vital('frecuencia_cardiaca', 'Frec. cardíaca', 'lpm', $v('frecuencia_cardiaca'));
            $vital('frecuencia_respiratoria', 'Frec. respiratoria', 'rpm', $v('frecuencia_respiratoria'));
            $vital('temperatura', 'Temperatura', '°C', $v('temperatura'));
            $vital('saturacion', 'Saturación O₂', '%', $v('saturacion'));
            $vital('peso', 'Peso', 'kg', $v('peso'));
            $vital('talla', 'Talla', 'cm', $v('talla'));
            ?>
            <div class="col-6 col-md-3 col-xl-2 vital">
              <label class="form-label" for="f-imc">IMC</label>
              <input class="form-control bg-light" id="f-imc" name="imc" value="<?= $v('imc') ?>" readonly tabindex="-1">
              <span class="unit">kg/m²</span>
              <div class="form-text" id="imc-label"></div>
            </div>
            <?php
            $vital('perimetro_abdominal', 'Perímetro abdominal', 'cm', $v('perimetro_abdominal'));
            $vital('glucometria', 'Glucometría', 'mg/dL', $v('glucometria'));
            ?>
          </div>
          <div class="row g-3">
            <?php foreach (Clinica::EXAMEN as $k => $label): ?>
              <div class="col-md-6">
                <label class="form-label" for="<?= $k ?>"><?= e($label) ?></label>
                <textarea class="form-control" id="<?= $k ?>" name="<?= $k ?>" rows="2"><?= $v($k) ?></textarea>
              </div>
            <?php endforeach; ?>
            <div class="col-12">
              <label class="form-label" for="paraclinicos">Resultados de paraclínicos aportados</label>
              <textarea class="form-control" id="paraclinicos" name="paraclinicos" rows="2" placeholder="Laboratorios o imágenes que trae el paciente, con fecha"><?= $v('paraclinicos') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card hc-section mb-3" id="sec-diagnosticos">
        <?= $sec(5, 'Diagnósticos (CIE-10)', '<button type="button" class="btn btn-sm btn-soft" data-add-row="dx-rows"><i class="bi bi-plus-lg"></i> Agregar</button>') ?>
        <div class="card-body">
          <div id="dx-rows" data-rows data-template="tpl-dx">
            <?php foreach ($dx as $i => $row) { $dxRow((string) $i, $row); } ?>
          </div>
          <div class="text-muted small" data-empty-for="dx-rows" <?= $dx ? 'hidden' : '' ?>>Aún no hay diagnósticos. El primero que agregue será el diagnóstico principal.</div>
        </div>
      </div>

      <div class="card hc-section mb-3" id="sec-plan">
        <?= $sec(6, 'Análisis y plan de manejo') ?>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="analisis">Análisis</label>
            <textarea class="form-control" id="analisis" name="analisis" rows="3"><?= $v('analisis') ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="plan_manejo">Plan de manejo / conducta<span class="req">*</span></label>
            <textarea class="form-control" id="plan_manejo" name="plan_manejo" rows="3"><?= $v('plan_manejo') ?></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label" for="recomendaciones">Recomendaciones y signos de alarma</label>
              <textarea class="form-control" id="recomendaciones" name="recomendaciones" rows="2"><?= $v('recomendaciones') ?></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="proximo_control">Próximo control</label>
              <input class="form-control" id="proximo_control" name="proximo_control" value="<?= $v('proximo_control') ?>" maxlength="120" placeholder="Ej. En 1 mes con resultados">
            </div>
          </div>
        </div>
      </div>

      <div class="card hc-section mb-3" id="sec-formula">
        <?= $sec(7, 'Fórmula médica', '<button type="button" class="btn btn-sm btn-soft" data-add-row="med-rows"><i class="bi bi-plus-lg"></i> Agregar medicamento</button>') ?>
        <div class="card-body">
          <div id="med-rows" data-rows data-template="tpl-med">
            <?php foreach ($items as $i => $row) { $medRow((string) $i, $row); } ?>
          </div>
          <div class="text-muted small mb-3" data-empty-for="med-rows" <?= $items ? 'hidden' : '' ?>>Sin medicamentos formulados.</div>
          <label class="form-label" for="formula_observaciones">Observaciones para el paciente (aparecen en la fórmula)</label>
          <textarea class="form-control" id="formula_observaciones" name="formula_observaciones" rows="2"><?= e($formula['observaciones'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="card hc-section mb-3" id="sec-ordenes">
        <?= $sec(8, 'Órdenes de exámenes, procedimientos y remisiones', '<button type="button" class="btn btn-sm btn-soft" data-add-row="ord-rows"><i class="bi bi-plus-lg"></i> Agregar</button>') ?>
        <div class="card-body">
          <div id="ord-rows" data-rows data-template="tpl-ord">
            <?php foreach ($ordenes as $i => $row) { $ordRow((string) $i, $row); } ?>
          </div>
          <div class="text-muted small" data-empty-for="ord-rows" <?= $ordenes ? 'hidden' : '' ?>>Sin órdenes.</div>
        </div>
      </div>

      <div class="card hc-section mb-3" id="sec-incapacidad">
        <?= $sec(9, 'Incapacidad médica') ?>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label" for="incapacidad_dias">Días de incapacidad</label>
              <input type="number" min="1" max="180" class="form-control" id="incapacidad_dias" name="incapacidad_dias" value="<?= $v('incapacidad_dias') ?>" placeholder="Sin incapacidad">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="incapacidad_desde">A partir de</label>
              <input type="date" class="form-control" id="incapacidad_desde" name="incapacidad_desde" value="<?= e($c['incapacidad_desde'] ?? date('Y-m-d')) ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="hc-aside">
        <div class="card mb-3">
          <div class="card-body d-grid gap-2">
            <button class="btn btn-primary" type="submit" name="accion" value="finalizar"
                    data-confirm-click="¿Finalizar la atención? La historia clínica quedará cerrada y no podrá modificarse; solo se podrán agregar notas aclaratorias.">
              <i class="bi bi-check2-circle"></i> Finalizar atención
            </button>
            <button class="btn btn-light border" type="submit" name="accion" value="guardar"><i class="bi bi-floppy"></i> Guardar borrador</button>
            <button class="btn btn-link btn-sm text-rojo" type="submit" name="accion" value="liberar"
                    data-confirm-click="¿Devolver el paciente a la sala de espera? Se descartará lo diligenciado en esta atención.">
              Devolver a sala de espera
            </button>
            <div class="form-text">Para finalizar se requiere: motivo, enfermedad actual, diagnóstico CIE-10 y plan de manejo.</div>
          </div>
        </div>

        <div class="card mb-3 d-none d-lg-block">
          <div class="card-body p-2 hc-nav">
            <?php $n = 1; foreach ($secciones as $id => $label): ?>
              <a href="#<?= $id ?>"><span class="text-muted small"><?= $n++ ?>.</span><?= e($label) ?></a>
            <?php endforeach; ?>
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
<template id="tpl-ord"><?php $ordRow('__i__', []); ?></template>
