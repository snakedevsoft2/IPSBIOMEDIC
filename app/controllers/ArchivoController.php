<?php
declare(strict_types=1);

/**
 * Sirve por HTTP los archivos guardados en la tabla `archivos` (ver Storage).
 * Solo tiene sentido en Vercel: en el hosting compartido los logos se sirven
 * como archivos estáticos normales y esta ruta no se usa.
 */
final class ArchivoController extends Controller
{
    public function ver(): void
    {
        $ruta = (string) ($_GET['ruta'] ?? '');
        // Únicamente logos públicos subidos desde Configuración; nunca firmas u otros archivos privados.
        if (!Storage::enVercel() || !str_starts_with($ruta, 'assets/uploads/')) {
            abort(404);
        }
        $archivo = Storage::get($ruta);
        if ($archivo === null) {
            abort(404);
        }
        header('Content-Type: ' . $archivo['mime']);
        header('Cache-Control: public, max-age=3600');
        echo $archivo['contenido'];
        exit;
    }
}
