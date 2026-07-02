<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Afficher les détails d'un véhicule spécifique
     * Pour la fiche détaillée (Écran 3)
     */
    public function show($id)
    {
        // On cherche le véhicule par son ID en chargeant ses images et l'agent qui l'a ajouté
        $vehicle = Vehicle::with(['images', 'user'])->find($id);

        // Si le véhicule n'existe pas, on retourne une erreur 404
        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Véhicule introuvable'
            ], 404);
        }

        // On retourne le véhicule trouvé avec un code HTTP 200
        return response()->json([
            'success' => true,
            'message' => 'Détails du véhicule récupérés avec succès',
            'data' => $vehicle
        ], 200);
    }

    /**
     * 1. AJOUTER un véhicule (Create)
     * Accessible uniquement par l'admin connecté
     */
    public function store(Request $request)
    {
        // Validation stricte des caractéristiques de la voiture
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:200',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|between:1900,2027',
            'mileage' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'fuel_type' => 'required|in:Essence,Diesel,Électrique,Hybride',
            'transmission' => 'required|in:Manuelle,Automatique',
            'condition' => 'required|in:Neuf,Occasion',
            'description' => 'required|string',
            'color' => 'required|string|max:50',
            'engine' => 'nullable|string|max:50',
            'doors' => 'required|integer|between:2,5',
            'images' => 'required|array', // On attend un tableau d'images
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048' // Validation de chaque fichier image (max 2Mo)
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // On récupère automatiquement l'ID de l'agent connecté grâce au Token Sanctum
        $data = $request->all();
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'Disponible'; // Statut initial par défaut

        // Création du véhicule en base de données
        $vehicle = Vehicle::create($data);

        // Gestion de l'upload des images physiques
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $imageFile) {
                // On sauvegarde l'image dans le dossier "storage/app/public/vehicles"
                $path = $imageFile->store('vehicles', 'public');

                // On enregistre la liaison en BDD
                $vehicle->images()->create([
                    'path' => $path,
                    'is_main' => ($index === 0) // La première image devient l'image principale
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Véhicule ajouté avec succès au catalogue !',
            'data' => $vehicle->load('images') // On recharge le véhicule avec ses images chargées
        ], 201);
    }

    /**
     * 2. MODIFIER un véhicule (Update)
     */
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);
        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Véhicule introuvable'], 404);
        }

        // Validation (presque identique à store, mais les champs sont parfois optionnels selon la requête)
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:200',
            'price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:Disponible,Réservé,Vendu',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Mise à jour des données textuelles
        $vehicle->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Fiche du véhicule mise à jour avec succès.',
            'data' => $vehicle
        ], 200);
    }

    /**
     * 3. SUPPRIMER un véhicule (Delete)
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::with('images')->find($id);
        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'Véhicule introuvable'], 404);
        }

        // Avant de supprimer de la BDD, on nettoie le disque dur (on supprime les fichiers physiques)
        foreach ($vehicle->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        // Suppression en cascade automatique dans la BDD (grâce à onDelete('cascade') de nos migrations)
        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Véhicule et ses images supprimés avec succès du système.'
        ], 200);
    }

    /**
     * 4. Récupérer les véhicules d'un agent spécifique (pour le tableau de bord)
     */
    public function dashboardIndex(Request $request)
    {
        $user = $request->user(); // L'agent connecté

        // Si l'utilisateur est un admin, on retourne tous les véhicules
        if ($user->role === 'admin') {
            $vehicles = Vehicle::with('images')->latest()->paginate(6); // Pagination pour le tableau de bord
        } else {
            // Sinon, on ne retourne que les véhicules de l'agent connecté
            $vehicles = Vehicle::with('images')->where('user_id', $user->id)->latest()->paginate(6);
        }

        return response()->json([
            'success' => true,
            'message' => 'Véhicules récupérés avec succès pour le tableau de bord.',
            'data' => $vehicles
        ], 200);
    }
}
