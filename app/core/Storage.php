<?php
declare(strict_types=1);

/**
 * Abstrae el guardado de archivos (logos subidos, firmas de médicos).
 *
 * En el hosting compartido (destino final) escribe directo a disco, tal como
 * siempre lo hizo la aplicación. En Vercel el sistema de archivos es de solo
 * lectura y efímero entre invocaciones, así que ahí los archivos se guardan
 * como filas en la tabla `archivos` de la misma base de datos MySQL.
 *
 * El resto del código no debe usar file_put_contents/is_file/unlink
 * directamente sobre rutas dentro de storage/ o assets/uploads/: debe pasar
 * siempre por aquí para funcionar en ambos entornos.
 */
final class Storage
{
    public static function enVercel(): bool
    {
        return getenv('VERCEL') !== false;
    }

    public static function put(string $rutaRel, string $contenido, string $mime = 'image/png'): void
    {
        if (self::enVercel()) {
            DB::run(
                'INSERT INTO archivos (ruta, mime, contenido, actualizado_en) VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE mime = VALUES(mime), contenido = VALUES(contenido), actualizado_en = NOW()',
                [$rutaRel, $mime, $contenido]
            );
            return;
        }

        $abs = BASE_PATH . '/' . $rutaRel;
        $dir = dirname($abs);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($abs, $contenido);
    }

    public static function exists(string $rutaRel): bool
    {
        if ($rutaRel === '') {
            return false;
        }
        if (self::enVercel() && !str_starts_with($rutaRel, 'assets/img/')) {
            return (bool) DB::value('SELECT 1 FROM archivos WHERE ruta = ?', [$rutaRel]);
        }
        return is_file(BASE_PATH . '/' . $rutaRel);
    }

    /** @return array{mime: string, contenido: string}|null */
    public static function get(string $rutaRel): ?array
    {
        if ($rutaRel === '') {
            return null;
        }
        if (self::enVercel() && !str_starts_with($rutaRel, 'assets/img/')) {
            $fila = DB::row('SELECT mime, contenido FROM archivos WHERE ruta = ?', [$rutaRel]);
            return $fila ?: null;
        }
        $abs = BASE_PATH . '/' . $rutaRel;
        if (!is_file($abs)) {
            return null;
        }
        $info = @getimagesize($abs);
        return ['mime' => $info['mime'] ?? 'application/octet-stream', 'contenido' => (string) file_get_contents($abs)];
    }

    public static function delete(string $rutaRel): void
    {
        if ($rutaRel === '') {
            return;
        }
        if (self::enVercel()) {
            DB::run('DELETE FROM archivos WHERE ruta = ?', [$rutaRel]);
            return;
        }
        $abs = BASE_PATH . '/' . $rutaRel;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    /** URL pública para mostrar un archivo guardado (logos). No usar con archivos privados como firmas. */
    public static function url(string $rutaRel): string
    {
        if ($rutaRel === '') {
            return '';
        }
        // Los logos por defecto (assets/img/...) son archivos estáticos reales que
        // se despliegan igual en Hostinger y en Vercel; solo lo que un administrador
        // sube a mano (assets/uploads/...) vive en la tabla `archivos` en Vercel.
        if (self::enVercel() && str_starts_with($rutaRel, 'assets/uploads/')) {
            return 'index.php?r=archivo&ruta=' . urlencode($rutaRel);
        }
        return $rutaRel . '?v=' . (int) @filemtime(BASE_PATH . '/' . $rutaRel);
    }
}
