<?php
declare(strict_types=1);

final class RespaldoController extends Controller
{
    public function index(): void
    {
        $dbName = (string) config('db.name');
        $tablas = DB::all(
            'SELECT TABLE_NAME AS tabla, TABLE_ROWS AS filas, (DATA_LENGTH + INDEX_LENGTH) AS bytes
             FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
            [$dbName]
        );
        $conteos = [
            'Pacientes'          => (int) DB::value('SELECT COUNT(*) FROM pacientes'),
            'Atenciones'         => (int) DB::value('SELECT COUNT(*) FROM consultas'),
            'Fórmulas médicas'   => (int) DB::value('SELECT COUNT(*) FROM formulas'),
            'Usuarios'           => (int) DB::value('SELECT COUNT(*) FROM usuarios'),
        ];
        $ultimos = DB::all(
            "SELECT a.creado_en, u.nombres, u.apellidos FROM auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.accion = 'respaldo_descargado' ORDER BY a.creado_en DESC LIMIT 5"
        );

        $this->view('respaldo/index', [
            'title' => 'Respaldo de la base de datos',
            'tablas' => $tablas, 'conteos' => $conteos, 'ultimos' => $ultimos,
            'tamano' => array_sum(array_column($tablas, 'bytes')),
        ]);
    }

    public function descargar(): void
    {
        $this->requirePost();
        @set_time_limit(0);
        Audit::log('respaldo_descargado', null, null, 'Descarga manual de respaldo SQL');

        $pdo = DB::pdo();
        $nombre = 'respaldo-ipsbiomed-' . date('Ymd-His') . '.sql';

        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Cache-Control: no-store');

        echo "-- Respaldo de la base de datos · " . setting('ips_nombre', 'IPS BIOMED') . "\n";
        echo '-- Generado el ' . date('d/m/Y H:i:s') . ' por ' . nombre_usuario(user()) . "\n";
        echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tablas as $tabla) {
            $create = $pdo->query("SHOW CREATE TABLE `{$tabla}`")->fetch(PDO::FETCH_NUM)[1];
            echo "DROP TABLE IF EXISTS `{$tabla}`;\n{$create};\n\n";

            $stmt = $pdo->query("SELECT * FROM `{$tabla}`");
            $lote = [];
            while ($fila = $stmt->fetch(PDO::FETCH_NUM)) {
                $valores = array_map(static fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $fila);
                $lote[] = '(' . implode(',', $valores) . ')';
                if (count($lote) >= 200) {
                    echo "INSERT INTO `{$tabla}` VALUES\n" . implode(",\n", $lote) . ";\n";
                    $lote = [];
                    flush();
                }
            }
            if ($lote) {
                echo "INSERT INTO `{$tabla}` VALUES\n" . implode(",\n", $lote) . ";\n";
            }
            echo "\n";
        }
        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        exit;
    }
}
