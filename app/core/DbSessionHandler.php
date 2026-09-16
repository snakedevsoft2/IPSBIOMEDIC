<?php
declare(strict_types=1);

/**
 * Guarda la sesión PHP en la base de datos en vez de en disco.
 *
 * Solo se usa en Vercel (ver bootstrap.php): cada invocación serverless
 * puede correr en un contenedor distinto, así que las sesiones de archivo
 * (session.save_path) no sobreviven entre peticiones y el login no
 * funcionaría. En el hosting compartido se sigue usando el manejador nativo
 * de PHP, que ahí sí es un servidor persistente.
 */
final class DbSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $datos = DB::value('SELECT datos FROM sesiones WHERE id = ?', [$id]);
        return $datos !== null ? (string) $datos : '';
    }

    public function write(string $id, string $data): bool
    {
        DB::run(
            'INSERT INTO sesiones (id, datos, actualizado_en) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE datos = VALUES(datos), actualizado_en = NOW()',
            [$id, $data]
        );
        return true;
    }

    public function destroy(string $id): bool
    {
        DB::run('DELETE FROM sesiones WHERE id = ?', [$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return DB::run('DELETE FROM sesiones WHERE actualizado_en < ?', [date('Y-m-d H:i:s', time() - $max_lifetime)])->rowCount();
    }
}
