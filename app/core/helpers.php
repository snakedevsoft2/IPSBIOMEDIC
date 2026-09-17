<?php
declare(strict_types=1);

/* ---------------------------------------------------------------------
 * Configuración y utilidades generales
 * ------------------------------------------------------------------- */

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'] ?? [];
    foreach (explode('.', $key) as $k) {
        if (!is_array($value) || !array_key_exists($k, $value)) {
            return $default;
        }
        $value = $value[$k];
    }
    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function request_is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Garantiza UTF-8 válido: evita textos dañados si el navegador envía otra codificación. */
function utf8_limpio(string $v): string
{
    return mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
}

function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim(utf8_limpio($v)) : $default;
}

function input_int(string $key): ?int
{
    $v = input($key);
    return ($v === '' || !is_numeric($v)) ? null : (int) $v;
}

function input_decimal(string $key): ?float
{
    $v = str_replace(',', '.', input($key));
    return ($v === '' || !is_numeric($v)) ? null : (float) $v;
}

function input_array(string $key): array
{
    $v = $_POST[$key] ?? [];
    return is_array($v) ? $v : [];
}

function nullable(mixed $v): ?string
{
    $v = is_string($v) ? trim($v) : '';
    return $v === '' ? null : $v;
}

/* ---------------------------------------------------------------------
 * Rutas, respuestas y mensajes
 * ------------------------------------------------------------------- */

function url(string $route = '', array $params = []): string
{
    $query = $route !== '' ? ['r' => $route] + $params : $params;
    return 'index.php' . ($query ? '?' . http_build_query($query) : '');
}

function absolute_url(string $route = '', array $params = []): string
{
    return rtrim((string) config('app.url'), '/') . '/' . url($route, $params);
}

function redirect(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

function redirect_to(string $relativeUrl): never
{
    header('Location: ' . $relativeUrl);
    exit;
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = [$type, $message];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    if (request_is_ajax()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $titles = [403 => 'Acceso denegado', 404 => 'Página no encontrada', 405 => 'Acción no permitida', 419 => 'Formulario expirado', 500 => 'Error del servidor'];
    $title = $titles[$code] ?? 'Error';
    require BASE_PATH . '/app/views/errors/error.php';
    exit;
}

function render_template(string $__file, array $__data = []): string
{
    extract($__data, EXTR_SKIP);
    ob_start();
    try {
        require $__file;
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
    return (string) ob_get_clean();
}

function push_script(string $html): void
{
    $GLOBALS['_scripts'][] = $html;
}

function page_head(string $title, string $subtitle = '', string $actions = ''): string
{
    return '<div class="page-head"><div><h1>' . e($title) . '</h1>'
        . ($subtitle !== '' ? '<p>' . e($subtitle) . '</p>' : '')
        . '</div>' . ($actions !== '' ? '<div class="page-actions">' . $actions . '</div>' : '') . '</div>';
}

/* ---------------------------------------------------------------------
 * Configuración de la IPS (tabla configuracion)
 * ------------------------------------------------------------------- */

function settings(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        try {
            foreach (DB::all('SELECT clave, valor FROM configuracion') as $row) {
                $cache[$row['clave']] = (string) $row['valor'];
            }
        } catch (Throwable) {
            $cache = [];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $v = settings()[$key] ?? '';
    return $v !== '' ? $v : $default;
}

function set_setting(string $key, string $value): void
{
    DB::run('INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$key, $value]);
    settings(true);
}

function encrypt_value(string $plain): string
{
    if ($plain === '') {
        return '';
    }
    $key = hash('sha256', (string) config('app.key'), true);
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $cipher);
}

function decrypt_value(string $encoded): string
{
    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 29) {
        return '';
    }
    $key = hash('sha256', (string) config('app.key'), true);
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $plain === false ? '' : $plain;
}

/* ---------------------------------------------------------------------
 * Usuarios y personas
 * ------------------------------------------------------------------- */

function user(): ?array
{
    return Auth::user();
}

function has_role(string ...$roles): bool
{
    $u = user();
    return $u !== null && in_array($u['rol'], $roles, true);
}

function nombre_usuario(array $u): string
{
    return trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
}

function nombre_paciente(array $p): string
{
    return implode(' ', array_filter([
        $p['primer_nombre'] ?? '', $p['segundo_nombre'] ?? '', $p['primer_apellido'] ?? '', $p['segundo_apellido'] ?? '',
    ], static fn ($v) => $v !== null && $v !== ''));
}

function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre)) ?: [];
    $ini = mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[count($partes) > 2 ? 2 : 1] ?? '', 0, 1);
    return mb_strtoupper($ini);
}

function password_policy_error(string $password, string $confirm): ?string
{
    if (mb_strlen($password) < 8) {
        return 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'La contraseña debe combinar letras y números.';
    }
    if ($password !== $confirm) {
        return 'Las contraseñas no coinciden.';
    }
    return null;
}

/* ---------------------------------------------------------------------
 * Fechas, edades y números
 * ------------------------------------------------------------------- */

function fecha(?string $value, bool $hora = false): string
{
    if (!$value) {
        return '';
    }
    $t = strtotime($value);
    return $t ? date($hora ? 'd/m/Y h:i a' : 'd/m/Y', $t) : '';
}

function fecha_larga(?string $value = null): string
{
    $t = $value ? strtotime($value) : time();
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $dias[(int) date('w', $t)] . ', ' . date('j', $t) . ' de ' . $meses[(int) date('n', $t)] . ' de ' . date('Y', $t);
}

function nombre_mes(int $mes): string
{
    return ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'][$mes] ?? '';
}

function edad(?string $nacimiento, ?string $referencia = null): string
{
    if (!$nacimiento) {
        return '';
    }
    try {
        $d = (new DateTime($nacimiento))->diff(new DateTime($referencia ?? 'now'));
    } catch (Exception) {
        return '';
    }
    if ($d->y >= 1) {
        return $d->y . ($d->y === 1 ? ' año' : ' años');
    }
    if ($d->m >= 1) {
        return $d->m . ($d->m === 1 ? ' mes' : ' meses');
    }
    return $d->d . ($d->d === 1 ? ' día' : ' días');
}

function numero_letras(int $n): string
{
    if ($n === 0) {
        return 'cero';
    }
    $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce',
        'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte', 'veintiuno',
        'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];
    $decenas = [3 => 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    $centenas = [1 => 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
    $apocope = static fn (string $s): string => preg_replace(['/veintiuno$/', '/uno$/'], ['veintiún', 'un'], $s);

    $f = static function (int $n) use (&$f, $unidades, $decenas, $centenas, $apocope): string {
        if ($n < 30) {
            return $unidades[$n];
        }
        if ($n < 100) {
            return $decenas[intdiv($n, 10)] . ($n % 10 ? ' y ' . $unidades[$n % 10] : '');
        }
        if ($n === 100) {
            return 'cien';
        }
        if ($n < 1000) {
            return $centenas[intdiv($n, 100)] . ($n % 100 ? ' ' . $f($n % 100) : '');
        }
        if ($n < 1000000) {
            $miles = intdiv($n, 1000);
            return ($miles === 1 ? 'mil' : $apocope($f($miles)) . ' mil') . ($n % 1000 ? ' ' . $f($n % 1000) : '');
        }
        $millones = intdiv($n, 1000000);
        return ($millones === 1 ? 'un millón' : $apocope($f($millones)) . ' millones') . ($n % 1000000 ? ' ' . $f($n % 1000000) : '');
    };
    return $f($n);
}

function dinero(mixed $valor): string
{
    return '$ ' . number_format((float) $valor, 0, ',', '.');
}

/* ---------------------------------------------------------------------
 * Catálogos
 * ------------------------------------------------------------------- */

function catalogo(string $nombre): array
{
    static $c = [
        'roles' => ['administrador' => 'Administrador', 'medico' => 'Médico', 'recepcion' => 'Recepción', 'auxiliar' => 'Auxiliar / Enfermería'],
        'tipo_documento' => [
            'CC' => 'CC - Cédula de ciudadanía', 'TI' => 'TI - Tarjeta de identidad', 'RC' => 'RC - Registro civil',
            'CE' => 'CE - Cédula de extranjería', 'PA' => 'PA - Pasaporte', 'PT' => 'PT - Permiso por protección temporal',
            'PE' => 'PE - Permiso especial de permanencia', 'CD' => 'CD - Carné diplomático', 'SC' => 'SC - Salvoconducto',
            'CN' => 'CN - Certificado de nacido vivo', 'AS' => 'AS - Adulto sin identificación', 'MS' => 'MS - Menor sin identificación',
            'DE' => 'DE - Documento extranjero',
        ],
        'sexo' => ['F' => 'Femenino', 'M' => 'Masculino', 'I' => 'Indeterminado / Intersexual'],
        'estado_civil' => ['Soltero(a)', 'Casado(a)', 'Unión libre', 'Separado(a)', 'Divorciado(a)', 'Viudo(a)'],
        'escolaridad' => ['Ninguna', 'Preescolar', 'Básica primaria', 'Básica secundaria', 'Media', 'Técnica', 'Tecnológica', 'Universitaria', 'Posgrado'],
        'grupo_sanguineo' => ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'],
        'etnia' => ['Ninguna', 'Indígena', 'Rom (gitano)', 'Raizal', 'Palenquero', 'Negro(a), mulato(a), afrodescendiente'],
        'zona' => ['U' => 'Urbana', 'R' => 'Rural'],
        'regimen' => ['Contributivo', 'Subsidiado', 'Especial', 'Excepción', 'Particular', 'No asegurado'],
        'tipo_afiliado' => ['Cotizante', 'Beneficiario', 'Adicional', 'No aplica'],
        'parentesco' => ['Madre', 'Padre', 'Hijo(a)', 'Cónyuge / Pareja', 'Hermano(a)', 'Abuelo(a)', 'Tío(a)', 'Otro familiar', 'Amigo(a)', 'Cuidador(a)', 'Otro'],
        'tipo_consulta' => ['Primera vez', 'Control', 'Consulta prioritaria'],
        'modalidad' => ['Intramural', 'Extramural domiciliaria', 'Telemedicina'],
        'finalidad' => ['Atención de enfermedad general', 'Diagnóstico', 'Tratamiento', 'Control y seguimiento', 'Detección temprana', 'Protección específica', 'Valoración integral', 'Otra'],
        'causa_externa' => ['Enfermedad general', 'Enfermedad laboral', 'Accidente de trabajo', 'Accidente de tránsito', 'Accidente rábico', 'Accidente ofídico', 'Otro tipo de accidente', 'Evento catastrófico', 'Lesión por agresión', 'Lesión autoinfligida', 'Sospecha de maltrato', 'Sospecha de violencia sexual', 'Otra'],
        'prioridad' => ['Normal', 'Prioritaria'],
        'clase_diagnostico' => ['Impresión diagnóstica', 'Confirmado nuevo', 'Confirmado repetido'],
        'via' => ['Oral', 'Sublingual', 'Tópica', 'Oftálmica', 'Ótica', 'Nasal', 'Inhalada', 'Intramuscular', 'Intravenosa', 'Subcutánea', 'Rectal', 'Vaginal', 'Transdérmica'],
        'forma_farmaceutica' => ['Tableta', 'Tableta recubierta', 'Cápsula', 'Jarabe', 'Suspensión oral', 'Solución oral', 'Gotas', 'Crema', 'Ungüento', 'Gel', 'Loción', 'Solución inyectable', 'Polvo para reconstituir', 'Inhalador', 'Óvulo', 'Supositorio', 'Parche', 'Sobre'],
        'tipo_orden' => ['Laboratorio clínico', 'Imagenología', 'Procedimiento', 'Interconsulta / Remisión', 'Terapia', 'Otro'],
    ];
    return $c[$nombre] ?? [];
}

function departamentos(): array
{
    return ['Amazonas', 'Antioquia', 'Arauca', 'Atlántico', 'Bogotá D.C.', 'Bolívar', 'Boyacá', 'Caldas', 'Caquetá', 'Casanare',
        'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía', 'Guaviare', 'Huila', 'La Guajira', 'Magdalena', 'Meta',
        'Nariño', 'Norte de Santander', 'Putumayo', 'Quindío', 'Risaralda', 'San Andrés y Providencia', 'Santander', 'Sucre',
        'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada'];
}

function options(array $items, mixed $selected = null, string $placeholder = 'Seleccione...'): string
{
    $html = $placeholder !== '' ? '<option value="">' . e($placeholder) . '</option>' : '';
    $isList = array_is_list($items);
    foreach ($items as $key => $label) {
        $value = $isList ? $label : $key;
        $sel = ($selected !== null && (string) $value === (string) $selected) ? ' selected' : '';
        $html .= '<option value="' . e($value) . '"' . $sel . '>' . e($label) . '</option>';
    }
    return $html;
}

function eps_lista(): array
{
    $rows = DB::all('SELECT id, nombre FROM eps WHERE activo = 1 ORDER BY nombre');
    return array_column($rows, 'nombre', 'id');
}

function medicos_lista(): array
{
    $rows = DB::all("SELECT id, CONCAT(nombres, ' ', apellidos) AS nombre FROM usuarios WHERE rol = 'medico' AND activo = 1 ORDER BY nombres");
    return array_column($rows, 'nombre', 'id');
}

function badge_estado(string $estado): string
{
    $map = [
        'en_espera'   => ['warning', 'En espera', 'bi-hourglass-split'],
        'en_atencion' => ['info', 'En atención', 'bi-activity'],
        'atendida'    => ['success', 'Atendida', 'bi-check2-circle'],
        'cancelada'   => ['muted', 'Cancelada', 'bi-x-circle'],
        'borrador'    => ['warning', 'Borrador', 'bi-pencil'],
        'cerrada'     => ['success', 'Finalizada', 'bi-lock'],
    ];
    [$cls, $label, $icon] = $map[$estado] ?? ['muted', $estado, 'bi-dot'];
    return '<span class="badge-status badge-status-' . $cls . '"><i class="bi ' . $icon . '"></i>' . e($label) . '</span>';
}

/* ---------------------------------------------------------------------
 * Logos e imágenes
 * ------------------------------------------------------------------- */

function logo_path(string $variante = 'claro'): string
{
    $custom = setting($variante === 'oscuro' ? 'logo_oscuro' : 'logo_claro');
    if ($custom !== '' && Storage::exists($custom)) {
        return $custom;
    }
    return $variante === 'oscuro' ? 'assets/img/logo-white.png' : 'assets/img/logo-color.png';
}

function logo_url(string $variante = 'claro'): string
{
    return Storage::url(logo_path($variante));
}

/** @param string $rutaRel ruta relativa al proyecto (no absoluta) */
function image_data_uri(string $rutaRel): string
{
    $archivo = Storage::get($rutaRel);
    if ($archivo === null) {
        return '';
    }
    return 'data:' . $archivo['mime'] . ';base64,' . base64_encode($archivo['contenido']);
}

/**
 * Valida y re-codifica una imagen subida como PNG (elimina contenido embebido).
 * @return array{0: bool, 1: string} [ok, ruta relativa o mensaje de error]
 */
function guardar_imagen(array $file, string $dirRel, string $prefijo, int $maxAncho = 1200, int $maxBytes = 3_000_000): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return [false, 'No se recibió el archivo.'];
    }
    if ($file['size'] > $maxBytes) {
        return [false, 'La imagen supera el tamaño máximo (' . round($maxBytes / 1_000_000, 1) . ' MB).'];
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
        return [false, 'Formato no permitido. Use una imagen PNG o JPG.'];
    }
    $src = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($file['tmp_name']) : @imagecreatefromjpeg($file['tmp_name']);
    if (!$src) {
        return [false, 'No fue posible leer la imagen.'];
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $nw = min($w, $maxAncho);
    $nh = max(1, (int) round($h * $nw / $w));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $name = $prefijo . '_' . bin2hex(random_bytes(6)) . '.png';
    ob_start();
    imagepng($dst, null, 9);
    $bytes = (string) ob_get_clean();
    Storage::put($dirRel . '/' . $name, $bytes, 'image/png');
    return [true, $dirRel . '/' . $name];
}

/* ---------------------------------------------------------------------
 * Paginación
 * ------------------------------------------------------------------- */

function paginar(int $total, int $porPagina = 20): array
{
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min(max(1, (int) ($_GET['page'] ?? 1)), $paginas);
    return ['pagina' => $pagina, 'paginas' => $paginas, 'total' => $total, 'limit' => $porPagina, 'offset' => ($pagina - 1) * $porPagina];
}

function paginacion(array $pg): string
{
    $html = '<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center px-3 py-2 border-top">'
        . '<small class="text-muted">' . number_format($pg['total'], 0, ',', '.') . ' registro(s)</small>';
    if ($pg['paginas'] > 1) {
        $q = $_GET;
        $link = static function (int $p, string $label, bool $disabled = false, bool $active = false) use ($q): string {
            $q['page'] = $p;
            return '<li class="page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '') . '">'
                . '<a class="page-link" href="index.php?' . e(http_build_query($q)) . '">' . $label . '</a></li>';
        };
        $html .= '<ul class="pagination pagination-sm mb-0">' . $link(max(1, $pg['pagina'] - 1), '&laquo;', $pg['pagina'] === 1);
        for ($i = max(1, $pg['pagina'] - 2); $i <= min($pg['paginas'], $pg['pagina'] + 2); $i++) {
            $html .= $link($i, (string) $i, false, $i === $pg['pagina']);
        }
        $html .= $link(min($pg['paginas'], $pg['pagina'] + 1), '&raquo;', $pg['pagina'] === $pg['paginas']) . '</ul>';
    }
    return $html . '</div>';
}

/* ---------------------------------------------------------------------
 * Navegación
 * ------------------------------------------------------------------- */

function menu_items(): array
{
    return [
        ['dashboard', 'Inicio', 'bi-grid-1x2', '*'],
        ['admisiones', 'Sala de espera', 'bi-people', '*'],
        ['pacientes', 'Pacientes', 'bi-person-vcard', '*'],
        ['consultas', 'Historias clínicas', 'bi-journal-medical', ['administrador', 'medico', 'auxiliar']],
        ['consultas/rapida', 'Evolución rápida', 'bi-capsule', ['administrador', 'medico']],
        ['reportes', 'Informes', 'bi-bar-chart-line', ['administrador', 'medico']],
        ['_admin', 'Administración', 'bi-gear', ['administrador'], [
            ['usuarios', 'Usuarios', 'bi-person-gear'],
            ['configuracion', 'Configuración de la IPS', 'bi-building-gear'],
            ['cie10', 'Diagnósticos CIE-10', 'bi-list-columns-reverse'],
            ['auditoria', 'Auditoría de accesos', 'bi-shield-check'],
            ['respaldo', 'Respaldo de la base de datos', 'bi-database-down'],
        ]],
    ];
}

function ruta_actual(): string
{
    $r = isset($_GET['r']) && is_string($_GET['r']) ? $_GET['r'] : 'dashboard';
    return explode('/', $r)[0];
}

/** @return array{0: int, 1: array} */
function notificaciones_espera(): array
{
    $u = user();
    if (!$u) {
        return [0, []];
    }
    $where = "a.estado = 'en_espera' AND a.fecha_hora >= CURDATE()";
    $params = [];
    if ($u['rol'] === 'medico') {
        $where .= ' AND (a.medico_id = ? OR a.medico_id IS NULL)';
        $params[] = $u['id'];
    }
    $total = (int) DB::value("SELECT COUNT(*) FROM admisiones a WHERE {$where}", $params);
    $rows = DB::all(
        "SELECT a.id, a.fecha_hora, a.prioridad, p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido
         FROM admisiones a JOIN pacientes p ON p.id = a.paciente_id
         WHERE {$where} ORDER BY (a.prioridad = 'Prioritaria') DESC, a.fecha_hora LIMIT 6",
        $params
    );
    return [$total, $rows];
}

function fecha_valida(string $value): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d !== false && $d->format('Y-m-d') === $value;
}

/** Lista de barras horizontales accesible (texto + barra). $items = [[etiqueta, cantidad], ...] */
function bar_list(array $items, string $vacio = 'Sin datos en el periodo seleccionado.'): string
{
    $total = array_sum(array_column($items, 1));
    if (!$items || $total === 0) {
        return '<div class="empty-state py-4"><i class="bi bi-bar-chart"></i>' . e($vacio) . '</div>';
    }
    $max = max(array_column($items, 1)) ?: 1;
    $html = '<ul class="bar-list">';
    foreach ($items as [$label, $n]) {
        $html .= '<li><span class="text-truncate" title="' . e($label) . '">' . e($label) . '</span>'
            . '<span class="val">' . number_format((int) $n, 0, ',', '.') . ' <small class="text-muted fw-normal">(' . round($n * 100 / $total) . '%)</small></span>'
            . '<div class="bar-track"><div class="bar-fill" style="width:' . round($n * 100 / $max) . '%"></div></div></li>';
    }
    return $html . '</ul>';
}
