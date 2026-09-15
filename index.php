<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$routes = require BASE_PATH . '/app/routes.php';
$route  = isset($_GET['r']) && is_string($_GET['r']) ? trim($_GET['r'], '/') : '';

if ($route === '') {
    $route = Auth::user() ? 'dashboard' : 'login';
}

if (!isset($routes[$route])) {
    abort(404, 'La página solicitada no existe.');
}

[$handler, $roles] = $routes[$route];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
    abort(419, 'El formulario expiró por seguridad. Recargue la página e intente de nuevo.');
}

if ($roles !== null) {
    if (!Auth::user()) {
        if (request_is_ajax()) {
            json_response(['error' => 'Sesión expirada'], 401);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $_SESSION['_intended'] = $_SERVER['QUERY_STRING'] ?? '';
        }
        flash('warning', 'Inicie sesión para continuar.');
        redirect('login');
    }
    if ($roles !== '*' && !in_array(Auth::user()['rol'], $roles, true)) {
        abort(403, 'Su perfil no tiene permisos para acceder a esta sección.');
    }
}

[$controller, $method] = explode('@', $handler);
$class = $controller . 'Controller';
require_once BASE_PATH . '/app/controllers/' . $class . '.php';
(new $class())->$method();
