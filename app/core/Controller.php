<?php
declare(strict_types=1);

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        $content = render_template(BASE_PATH . "/app/views/{$view}.php", $data);
        echo render_template(BASE_PATH . "/app/views/layouts/{$layout}.php", $data + ['content' => $content]);
    }

    protected function id(string $key = 'id'): int
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? 0;
        return is_numeric($v) ? max(0, (int) $v) : 0;
    }

    protected function requirePost(): void
    {
        if (!is_post()) {
            abort(405, 'Esta acción solo se puede ejecutar desde un formulario.');
        }
    }
}
