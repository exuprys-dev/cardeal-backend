<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * Afficher la liste des véhicules (avec filtres de recherche)
     * Pour le catalogue (Écran 2)
     */
    public function index(Request $request)
    {
        // 1. On initialise la requête Eloquent en demandant de charger aussi les images associées (Eager Loading)
        // Seuls les véhicules "Disponibles" doivent être affichés sur le site public
        $query = Vehicle::with('images')->where('status', 'Disponible');

        // 2. Filtre par Marque (ex: /api/vehicles?brand=Toyota)
        if ($request->has('brand') && $request->brand != '') {
            $query->where('brand', $request->brand);
        }

        // 3. Filtre par Type de Carburant (ex: /api/vehicles?fuel_type=Essence)
        if ($request->has('fuel_type') && $request->fuel_type != '') {
            $query->where('fuel_type', $request->fuel_type);
        }

        // 4. Filtre par État (ex: /api/vehicles?condition=Occasion)
        if ($request->has('condition') && $request->condition != '') {
            $query->where('condition', $request->condition);
        }

        // 5. Filtre par Prix Maximum (ex: /api/vehicles?max_price=10000000)
        if ($request->has('max_price') && $request->max_price != '') {
            $query->where('price', '<=', $request->max_price);
        }

        // 6. On trie par les plus récents ajoutés et on pagine (9 par page pour le design de grille)
        $vehicles = $query->latest()->paginate(9);

        // 7. On retourne une réponse JSON propre avec un code HTTP 200 (Success)
        return response()->json([
            'success' => true,
            'message' => 'Catalogue récupéré avec succès',
            'data' => $vehicles
        ], 200);
    }
}