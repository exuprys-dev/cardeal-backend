<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-seeder-xyz', function () {
    try {
        // Force l'exécution du DatabaseSeeder
        Artisan::call('db:seed', ['--force' => true]);
        return response()->json([
            'status' => 'success',
            'message' => 'La base de données a été seedée avec succès !'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});