<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = DB::select('SHOW COLUMNS FROM stock_request_items WHERE Field = ?', ['status']);

foreach($columns as $column) {
    echo "Field: {$column->Field}\n";
    echo "Type: {$column->Type}\n";
    echo "Null: {$column->Null}\n";
    echo "Key: {$column->Key}\n";
    echo "Default: {$column->Default}\n";
    echo "Extra: {$column->Extra}\n";
}
