<?php

return [
    // IDs de roles por defecto. Ajusta en .env si es necesario.
    'admin' => env('ROLE_ADMIN_ID', 1),
    'soporte' => env('ROLE_SOPORTE_ID', 2),
    'director' => env('ROLE_DIRECTOR_ID', 3),
];
