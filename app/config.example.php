<?php
// app/config.example.php
// Salin file ini ke app/config.local.php dan isi dengan kredensial database Anda.
// JANGAN commit app/config.local.php ke version control.

return [
    'database' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'katalog_store',
        'username' => 'katalog_app',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
];
