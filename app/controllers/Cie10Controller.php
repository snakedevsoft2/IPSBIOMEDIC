<?php
declare(strict_types=1);

final class Cie10Controller extends Controller
{
    public function buscar(): void
    {
        $q = trim(input('q'));
        if (mb_strlen($q) < 2) {
            json_response([]);
        }
        $codigo = strtoupper(str_replace(['.', ' '], '', $q));
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $prefijo = addcslashes($codigo, '%_\\') . '%';
        $rows = DB::all(
            'SELECT codigo, descripcion FROM cie10
             WHERE activo = 1 AND (codigo LIKE ? OR descripcion LIKE ?)
             ORDER BY (codigo LIKE ?) DESC, codigo LIMIT 15',
            [$prefijo, $like, $prefijo]
        );
        json_response($rows);
    }

    public function index(): void
    {
        $q = input('q');
        $where = '1 = 1';
        $params = [];
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where = '(codigo LIKE ? OR descripcion LIKE ?)';
            $params = [addcslashes(strtoupper($q), '%_\\') . '%', $like];
        }
        $total = (int) DB::value("SELECT COUNT(*) FROM cie10 WHERE {$where}", $params);
        $pg = paginar($total, 50);
        $rows = DB::all("SELECT * FROM cie10 WHERE {$where} ORDER BY codigo LIMIT {$pg['limit']} OFFSET {$pg['offset']}", $params);

        $this->view('cie10/index', [
            'title' => 'Diagnósticos CIE-10',
            'q' => $q, 'rows' => $rows, 'pg' => $pg,
            'totalTabla' => (int) DB::value('SELECT COUNT(*) FROM cie10'),
        ]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $accion = input('accion');

        if ($accion === 'estado') {
            $codigo = input('codigo');
            $actual = DB::value('SELECT activo FROM cie10 WHERE codigo = ?', [$codigo]);
            if ($actual !== null) {
                DB::update('cie10', ['activo' => (int) $actual === 1 ? 0 : 1], 'codigo = ?', [$codigo]);
                flash('success', 'Diagnóstico actualizado.');
            }
        } else {
            $codigo = strtoupper(str_replace(['.', ' '], '', input('codigo')));
            $descripcion = mb_substr(input('descripcion'), 0, 255);
            if (!preg_match('/^[A-Z]\d{2}[0-9A-Z]{0,2}$/', $codigo) || $descripcion === '') {
                flash('danger', 'Código CIE-10 inválido (ej. J069) o descripción vacía.');
            } else {
                DB::run(
                    'INSERT INTO cie10 (codigo, descripcion, activo) VALUES (?, ?, 1)
                     ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), activo = 1',
                    [$codigo, $descripcion]
                );
                Audit::log('cie10_guardado', 'cie10', null, $codigo);
                flash('success', 'Diagnóstico ' . $codigo . ' guardado.');
            }
        }
        redirect('cie10');
    }

    public function importar(): void
    {
        $this->requirePost();
        $archivo = $_FILES['archivo'] ?? null;
        if (!$archivo || ($archivo['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            flash('danger', 'No se recibió el archivo CSV.');
            redirect('cie10');
        }
        if ($archivo['size'] > 8_000_000) {
            flash('danger', 'El archivo supera los 8 MB.');
            redirect('cie10');
        }

        $handle = fopen($archivo['tmp_name'], 'r');
        if (!$handle) {
            flash('danger', 'No fue posible leer el archivo.');
            redirect('cie10');
        }

        $primera = (string) fgets($handle);
        rewind($handle);
        $delimitador = substr_count($primera, ';') >= substr_count($primera, ',') ? ';' : ',';
        if (substr_count($primera, "\t") > substr_count($primera, $delimitador)) {
            $delimitador = "\t";
        }

        $idxCodigo = 0;
        $idxNombre = 1;
        $importados = 0;
        $errores = 0;
        $primeraFila = true;

        $stmt = DB::pdo()->prepare(
            'INSERT INTO cie10 (codigo, descripcion, activo) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)'
        );
        DB::pdo()->beginTransaction();
        try {
            while (($fila = fgetcsv($handle, 4000, $delimitador)) !== false) {
                if ($fila === [null] || $fila === false) {
                    continue;
                }
                $fila = array_map(static function ($v): string {
                    $v = trim((string) $v);
                    return mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
                }, $fila);

                if ($primeraFila) {
                    $primeraFila = false;
                    $encabezado = array_map(static fn ($v) => mb_strtolower($v), $fila);
                    $posCodigo = array_search('codigo', $encabezado, true);
                    $posNombre = array_search('nombre', $encabezado, true);
                    if ($posNombre === false) {
                        $posNombre = array_search('descripcion', $encabezado, true);
                    }
                    if ($posCodigo !== false && $posNombre !== false) {
                        $idxCodigo = (int) $posCodigo;
                        $idxNombre = (int) $posNombre;
                        continue;
                    }
                }

                $codigo = strtoupper(str_replace(['.', ' '], '', $fila[$idxCodigo] ?? ''));
                $descripcion = mb_substr($fila[$idxNombre] ?? '', 0, 255);
                if (!preg_match('/^[A-Z]\d{2}[0-9A-Z]{0,2}$/', $codigo) || $descripcion === '') {
                    $errores++;
                    continue;
                }
                $stmt->execute([$codigo, $descripcion]);
                $importados++;
            }
            DB::pdo()->commit();
        } catch (Throwable $e) {
            DB::pdo()->rollBack();
            fclose($handle);
            flash('danger', 'Error durante la importación: ' . $e->getMessage());
            redirect('cie10');
        }
        fclose($handle);

        Audit::log('cie10_importado', 'cie10', null, $importados . ' códigos');
        flash('success', "Importación terminada: {$importados} código(s) cargado(s)." . ($errores ? " {$errores} fila(s) ignorada(s) por formato." : ''));
        redirect('cie10');
    }
}
