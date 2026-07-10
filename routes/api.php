<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Response;


// Routes API publiques pour les véhicules et les demandes de contact

Route::get('/vehicles', [VehicleController::class, 'index']);
Route::get('/vehicles/{id}', [VehicleController::class, 'show']);
Route::post('/contact', [ContactController::class, 'store']);
Route::post('/test-drive', [ContactController::class, 'storeTestDrive']);

// Route publique pour les statistiques de la page d'accueil
Route::get('/global-stats', function () {
    // 1. On compte les vrais véhicules disponibles en base de données
    $realAvailable = Vehicle::where('status', 'Disponible')->count();

    // 2. On retourne les stats (les vraies + les constantes métiers de l'agence)
    return response::json([
        'success' => true,
        'data' => [
            'vehicles_count' => $realAvailable,
            'sales_count' => 70 + $realAvailable, // Exemple de calcul dynamique ou fixe
            'satisfaction_rate' => 98
        ]
    ], 200);
});

// Route publique pour la connexion
Route::post('/login', [AuthController::class, 'login']);

// Groupe de routes protégées par Laravel Sanctum (L'utilisateur doit fournir un Token valide)
Route::middleware('auth:sanctum')->group(function () {

    // Route pour se déconnecter
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- GESTION DU PARC AUTOMOBILE (Dashboard) ---
    // Ces routes correspondent exactement aux appels du composant VehiclesView
    Route::get('/dashboard/vehicles', [VehicleController::class, 'dashboardIndex']); // <-- MANQUANT CORRIGÉ
    Route::post('/vehicles', [VehicleController::class, 'store']);
    Route::put('/vehicles/{id}', [VehicleController::class, 'update']);
    Route::delete('/dashboard/vehicles/{id}', [VehicleController::class, 'destroy']); // <-- ADAPTÉ POUR REACT

    // --- DEMANDES DE CONTACT ---
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::put('/contacts/{id}', [ContactController::class, 'toggleRead']);
    Route::delete('/contacts/{id}', [ContactController::class, 'destroy']);

    // --- DEMANDES DE TEST DRIVE & RENDEZ-VOUS ---
    Route::post('/test-drives', [ContactController::class, 'storeTestDrive']);
    Route::get('/test-drives', [ContactController::class, 'indexTestDrives']);
    Route::get('/test-drives/{id}', [ContactController::class, 'showTestDrive']);
    Route::put('/test-drives/{id}', [ContactController::class, 'updateTestDriveStatus']);
    Route::put('/dashboard/appointments/{id}', [DashboardController::class, 'updateAppointment']); // <-- AJOUTÉ
    Route::delete('/test-drives/{id}', [ContactController::class, 'destroyTestDrive']);

    // --- ENREGISTREMENT DES VENTES (Admin) ---
    Route::post('/dashboard/sales', [DashboardController::class, 'storeSale']); // <-- AJOUTÉ

    // --- GESTION DES UTILISATEURS ---
    Route::get('/users', [AuthController::class, 'index']);
    Route::get('/users/{id}', [AuthController::class, 'show']);
    Route::post('/users', [AuthController::class, 'store']);
    Route::put('/users/{id}', [AuthController::class, 'update']);
    Route::delete('/users/{id}', [AuthController::class, 'destroy']);
    Route::put('/users/{id}/change-password', [AuthController::class, 'changePassword']);

    // --- GESTION DES CLIENTS ---
    Route::get('/dashboard/clients', [DashboardController::class, 'getClients']);

    // --- VUE D'ENSEMBLE ---
    Route::get('/dashboard/summary', [DashboardController::class, 'getSummary']);
});
