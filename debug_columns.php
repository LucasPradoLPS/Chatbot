<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$empresa = \DB::table('empresas')->first();
if ($empresa) {
    echo "Empresas columns:\n";
    print_r(array_keys(get_object_vars($empresa)));
}

$agente = \DB::table('agentes')->first();
if ($agente) {
    echo "\nAgentes columns:\n";
    print_r(array_keys(get_object_vars($agente)));
}
