<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Serving welfare schemes, transparently.');
})->purpose('Display an inspiring quote');
