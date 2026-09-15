<?php
// ruta => [Controlador@metodo, roles permitidos]
// null = pública, '*' = cualquier usuario autenticado
const ADM = 'administrador';
const MED = 'medico';
const REC = 'recepcion';
const AUX = 'auxiliar';

return [
    // Autenticación
    'login'                 => ['Auth@login', null],
    'recuperar'             => ['Auth@forgot', null],
    'restablecer'           => ['Auth@reset', null],
    'logout'                => ['Auth@logout', '*'],

    'dashboard'             => ['Dashboard@index', '*'],
    'perfil'                => ['Perfil@index', '*'],

    // Pacientes
    'pacientes'             => ['Pacientes@index', '*'],
    'pacientes/crear'       => ['Pacientes@crear', [ADM, REC]],
    'pacientes/editar'      => ['Pacientes@editar', [ADM, REC]],
    'pacientes/ver'         => ['Pacientes@ver', '*'],
    'pacientes/buscar'      => ['Pacientes@buscar', '*'],

    // Admisiones / sala de espera
    'admisiones'            => ['Admisiones@index', '*'],
    'admisiones/crear'      => ['Admisiones@crear', [ADM, REC]],
    'admisiones/cancelar'   => ['Admisiones@cancelar', [ADM, REC]],

    // Historia clínica
    'consultas'             => ['Consultas@index', [ADM, MED, AUX]],
    'consultas/atender'     => ['Consultas@atender', [MED]],
    'consultas/editar'      => ['Consultas@editar', [MED]],
    'consultas/ver'         => ['Consultas@ver', [ADM, MED, AUX]],
    'consultas/nota'        => ['Consultas@nota', [MED]],

    // PDF
    'pdf/historia'          => ['Pdf@historia', [ADM, MED, AUX]],
    'pdf/formula'           => ['Pdf@formula', [ADM, MED, AUX]],
    'pdf/ordenes'           => ['Pdf@ordenes', [ADM, MED, AUX]],
    'pdf/incapacidad'       => ['Pdf@incapacidad', [ADM, MED, AUX]],

    // Reportes
    'reportes'              => ['Reportes@index', [ADM, MED]],
    'reportes/excel'        => ['Reportes@excel', [ADM, MED]],
    'reportes/pdf'          => ['Reportes@pdf', [ADM, MED]],

    // CIE-10
    'cie10/buscar'          => ['Cie10@buscar', '*'],
    'cie10'                 => ['Cie10@index', [ADM]],
    'cie10/guardar'         => ['Cie10@guardar', [ADM]],
    'cie10/importar'        => ['Cie10@importar', [ADM]],

    // Administración
    'usuarios'              => ['Usuarios@index', [ADM]],
    'usuarios/crear'        => ['Usuarios@crear', [ADM]],
    'usuarios/editar'       => ['Usuarios@editar', [ADM]],
    'usuarios/estado'       => ['Usuarios@estado', [ADM]],
    'configuracion'         => ['Configuracion@index', [ADM]],
    'configuracion/guardar' => ['Configuracion@guardar', [ADM]],
    'configuracion/probar'  => ['Configuracion@probarCorreo', [ADM]],
    'configuracion/eps'     => ['Configuracion@eps', [ADM]],
    'auditoria'             => ['Auditoria@index', [ADM]],
    'respaldo'              => ['Respaldo@index', [ADM]],
    'respaldo/descargar'    => ['Respaldo@descargar', [ADM]],
];
