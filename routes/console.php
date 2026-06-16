<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Fluffy Enigma — simple, transparent, fast.');
})->purpose('Display an inspiring quote');
