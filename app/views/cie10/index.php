<?= page_head('Diagnósticos CIE-10', number_format($totalTabla, 0, ',', '.') . ' códigos disponibles para la historia clínica') ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-plus-circle"></i>Agregar o corregir</h2></div>
      <div class="card-body">
        <form method="post" action="<?= url('cie10/guardar') ?>">
          <?= csrf_field() ?>
          <label class="form-label">Código</label>
          <input name="codigo" class="form-control mb-2 text-uppercase" maxlength="10" placeholder="J069" required>
          <label class="form-label">Descripción</label>
          <input name="descripcion" class="form-control mb-3" maxlength="255" required>
          <button class="btn btn-primary w-100">Guardar diagnóstico</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2><i class="bi bi-upload"></i>Importar tabla oficial</h2></div>
      <div class="card-body">
        <p class="text-muted small">Cargue el archivo CSV de la tabla de referencia CIE-10 del Ministerio de Salud (SISPRO). Debe tener una columna <strong>Codigo</strong> y otra <strong>Nombre</strong> o <strong>Descripcion</strong>; acepta separador «;» o «,».</p>
        <form method="post" action="<?= url('cie10/importar') ?>" enctype="multipart/form-data" data-confirm="La importación actualizará las descripciones de los códigos existentes. ¿Continuar?">
          <?= csrf_field() ?>
          <input type="file" name="archivo" class="form-control mb-2" accept=".csv,text/csv" required>
          <button class="btn btn-outline-primary w-100"><i class="bi bi-cloud-upload"></i> Importar CSV</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <form class="filter-bar w-100" method="get">
          <input type="hidden" name="r" value="cie10">
          <div class="input-group" style="max-width: 420px">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Buscar por código o descripción">
          </div>
          <button class="btn btn-light border">Buscar</button>
          <?php if ($q !== ''): ?><a class="btn btn-link" href="<?= url('cie10') ?>">Limpiar</a><?php endif; ?>
        </form>
      </div>
      <?php if (!$rows): ?>
        <div class="empty-state"><i class="bi bi-search"></i>No se encontraron códigos.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th style="width:100px">Código</th><th>Descripción</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr class="<?= (int) $r['activo'] ? '' : 'text-muted' ?>">
                <td class="fw-semibold"><?= e($r['codigo']) ?></td>
                <td><?= e($r['descripcion']) ?></td>
                <td><?= (int) $r['activo'] ? '<span class="badge-status badge-status-success"><i class="bi bi-check2"></i>Activo</span>' : '<span class="badge-status badge-status-muted">Inactivo</span>' ?></td>
                <td class="table-actions">
                  <form method="post" action="<?= url('cie10/guardar') ?>" class="d-inline">
                    <?= csrf_field() ?><input type="hidden" name="accion" value="estado"><input type="hidden" name="codigo" value="<?= e($r['codigo']) ?>">
                    <button class="btn btn-sm btn-light border"><?= (int) $r['activo'] ? 'Desactivar' : 'Activar' ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?= paginacion($pg) ?>
      <?php endif; ?>
    </div>
  </div>
</div>
