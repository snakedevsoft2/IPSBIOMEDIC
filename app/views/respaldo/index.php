<?= page_head('Respaldo de la base de datos', 'Descargue una copia completa de la información clínica') ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><h2><i class="bi bi-database-down"></i>Descargar respaldo</h2></div>
      <div class="card-body">
        <p>Genera un archivo <code>.sql</code> con toda la base de datos: pacientes, historias clínicas, fórmulas, usuarios y auditoría.</p>
        <dl class="dl-grid mb-3">
          <?php foreach ($conteos as $label => $n): ?>
            <div><dt><?= e($label) ?></dt><dd><?= number_format($n, 0, ',', '.') ?></dd></div>
          <?php endforeach; ?>
          <div><dt>Tamaño aproximado</dt><dd><?= number_format($tamano / 1048576, 2, ',', '.') ?> MB</dd></div>
        </dl>
        <form method="post" action="<?= url('respaldo/descargar') ?>">
          <?= csrf_field() ?>
          <button class="btn btn-primary w-100"><i class="bi bi-download"></i> Descargar respaldo ahora</button>
        </form>
        <div class="alert alert-info mt-3 mb-0 small">
          <strong>Recomendación:</strong> descargue el respaldo al menos una vez por semana y guárdelo en un lugar seguro
          (disco externo o nube). Además, active las copias de seguridad automáticas del hosting.
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2><i class="bi bi-clock-history"></i>Últimas descargas</h2></div>
      <?php if (!$ultimos): ?>
        <div class="card-body text-muted small">Todavía no se ha descargado ningún respaldo.</div>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($ultimos as $u): ?>
            <li class="list-group-item small d-flex justify-content-between">
              <span><?= e(fecha($u['creado_en'], true)) ?></span>
              <span class="text-muted"><?= e(trim($u['nombres'] . ' ' . $u['apellidos'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><h2><i class="bi bi-table"></i>Contenido de la base de datos</h2></div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Tabla</th><th class="num">Registros (aprox.)</th><th class="num">Tamaño</th></tr></thead>
          <tbody>
          <?php foreach ($tablas as $t): ?>
            <tr>
              <td><?= e($t['tabla']) ?></td>
              <td class="num"><?= number_format((int) $t['filas'], 0, ',', '.') ?></td>
              <td class="num"><?= number_format(((int) $t['bytes']) / 1024, 0, ',', '.') ?> KB</td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer small text-muted">
        Para restaurar un respaldo: en el panel del hosting abra <strong>phpMyAdmin</strong>, seleccione la base de datos y
        use <strong>Importar</strong> con el archivo <code>.sql</code> descargado.
      </div>
    </div>
  </div>
</div>
