<?php
declare(strict_types=1);

/** Registro de trazabilidad de accesos y acciones sobre la historia clínica. */
final class Audit
{
    public static function log(string $accion, ?string $entidad = null, ?int $entidadId = null, string $detalle = '', ?int $usuarioId = null): void
    {
        try {
            DB::insert('auditoria', [
                'usuario_id' => $usuarioId ?? (isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null),
                'accion'     => $accion,
                'entidad'    => $entidad,
                'entidad_id' => $entidadId,
                'detalle'    => mb_substr($detalle, 0, 2000),
                'ip'         => client_ip(),
            ]);
        } catch (Throwable $e) {
            error_log('Auditoría: ' . $e->getMessage());
        }
    }
}
