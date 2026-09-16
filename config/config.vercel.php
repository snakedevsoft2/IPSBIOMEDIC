<?php
// Configuración usada SOLO cuando la app corre en Vercel (variable VERCEL=1
// puesta automáticamente por la plataforma). No contiene credenciales: las
// toma de las variables de entorno del proyecto en el dashboard de Vercel.
//
// En el hosting compartido definitivo (ipsbiomed.com) se sigue usando
// config/config.php generado por install.php; este archivo no aplica ahí.
//
// Variables a definir en Vercel (Project Settings → Environment Variables):
//   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
//   APP_URL, APP_KEY, APP_DEBUG (true/false)
return [
    'db' => [
        'host' => (string) getenv('DB_HOST'),
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => (string) getenv('DB_NAME'),
        'user' => (string) getenv('DB_USER'),
        'pass' => (string) getenv('DB_PASS'),
    ],
    'app' => [
        'url'      => (string) (getenv('APP_URL') ?: 'https://localhost'),
        'timezone' => 'America/Bogota',
        'debug'    => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'key'      => (string) getenv('APP_KEY'),
    ],
];
