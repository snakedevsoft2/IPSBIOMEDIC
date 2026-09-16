<?php
/**
 * Encabezado común de los PDF: logo de la IPS en todas las páginas.
 * Variables: $logo, $docTitulo, $docNumero, $docFecha
 */
$ipsNombre = setting('ips_nombre', 'IPS BIOMED');
$lineaLegal = array_filter([
    setting('ips_nit') ? 'NIT ' . setting('ips_nit') : '',
    setting('ips_codigo_habilitacion') ? 'Código de habilitación ' . setting('ips_codigo_habilitacion') : '',
]);
$lineaContacto = array_filter([
    trim(setting('ips_direccion') . (setting('ips_ciudad') ? ', ' . setting('ips_ciudad') : ''), ', '),
    setting('ips_telefono') ? 'Tel. ' . setting('ips_telefono') : '',
    setting('ips_email'),
]);
$landscape = $landscape ?? false;
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= e($docTitulo) ?></title>
<style>
  @page { margin: 112px 36px 58px 36px; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.6px; color: #1f2937; line-height: 1.35; }
  header { position: fixed; top: -94px; left: 0; right: 0; height: 84px; }
  .hdr { width: 100%; border-collapse: collapse; }
  .hdr td { vertical-align: middle; padding: 0; }
  .logo { height: 44px; }
  .ips { font-size: 7.6px; color: #4b5563; line-height: 1.5; padding-left: 8px !important; }
  .ips strong { font-size: 10px; color: #1f2937; }
  .doc-title { text-align: right; }
  .doc-title .t { font-size: 12.5px; font-weight: bold; color: #00737A; }
  .doc-title .n { font-size: 7.6px; color: #6b7280; }
  .hdr-line { margin-top: 7px; height: 3px; background: #00A3AD; position: relative; }
  .hdr-line .r { position: absolute; left: 0; top: 0; width: 90px; height: 3px; background: #D91424; }

  h2.sec { font-size: 8.4px; background: #E8F7F8; color: #00606A; padding: 4px 7px; margin: 11px 0 5px; text-transform: uppercase; letter-spacing: .3px; border-left: 3px solid #00A3AD; }
  table.grid { width: 100%; border-collapse: collapse; }
  table.grid td, table.grid th { border: 0.6px solid #d9dee4; padding: 3.5px 5px; vertical-align: top; }
  table.grid th { background: #f5f7fa; text-align: left; font-size: 7px; text-transform: uppercase; color: #4b5563; font-weight: bold; }
  .lbl { display: block; font-size: 6.4px; color: #6b7280; text-transform: uppercase; margin-bottom: 1px; }
  .txt { margin: 0 0 6px; }
  .txt .lbl { font-size: 6.8px; }
  .muted { color: #6b7280; }
  .right { text-align: right; }
  .center { text-align: center; }
  .big { font-size: 10px; font-weight: bold; }
  .nowrap { white-space: nowrap; }
  .foto-paciente { width: 52px; border: 0.6px solid #d9dee4; border-radius: 3px; }
  .firma-wrap { margin-top: 24px; page-break-inside: avoid; }
  .firma-img { height: 58px; }
  .firma-linea { border-top: 0.8px solid #1f2937; width: 240px; padding-top: 3px; }
  .nota { border-left: 2px solid #D91424; padding: 3px 6px; margin-bottom: 4px; background: #fff8f8; }
  .rx { font-size: 20px; font-weight: bold; color: #00A3AD; }
</style>
</head>
<body>
<header>
  <table class="hdr">
    <tr>
      <td style="width: <?= $landscape ? '24%' : '33%' ?>"><?php if ($logo): ?><img src="<?= $logo ?>" class="logo" alt=""><?php endif; ?></td>
      <td class="ips" style="width: <?= $landscape ? '46%' : '40%' ?>">
        <strong><?= e($ipsNombre) ?></strong><br>
        <?= e(implode(' · ', $lineaLegal)) ?><?= $lineaLegal ? '<br>' : '' ?>
        <?= e(implode(' · ', $lineaContacto)) ?>
      </td>
      <td class="doc-title">
        <div class="t"><?= e($docTitulo) ?></div>
        <div class="n"><?= e($docNumero) ?></div>
        <div class="n"><?= e($docFecha) ?></div>
      </td>
    </tr>
  </table>
  <div class="hdr-line"><div class="r"></div></div>
</header>
