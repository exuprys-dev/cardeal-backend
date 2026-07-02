<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\TestDriveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Enregistrer une nouvelle demande de contact depuis le site public
     * Pour le formulaire de contact (Écran 4)
     */
    public function store(Request $request)
    {
        // 1. Validation stricte des données envoyées par React
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'nullable|exists:vehicles,id', // Doit exister dans la table vehicles si renseigné
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|max:150',
            'phone'      => 'required|string|max:20',
            'message'    => 'required|string',
        ]);

        // Si la validation échoue, on renvoie les erreurs à React avec un code 422
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Création de la demande dans la base de données
        $contact = ContactRequest::create($request->all());

        // 3. Réponse de succès à React (Code HTTP 201: Created)
        return response()->json([
            'success' => true,
            'message' => 'Votre message a été envoyé avec succès ! Notre équipe vous recontactera.',
            'data'    => $contact
        ], 201);
    }

    /**
     * [Admin] Récupérer les messages avec statistiques
     * S'aligne sur ta colonne 'is_read'
     */
    public function index()
    {
        // 1. On calcule les compteurs à la volée grâce à ton booléen
        $totalContacts = ContactRequest::count();
        $unreadContacts = ContactRequest::where('is_read', false)->count(); // is_read = 0 (false) signifie Non lu

        // 2. On récupère les messages avec le véhicule associé
        $contacts = ContactRequest::with('vehicle')->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'meta' => [
                'total_count' => $totalContacts,
                'unread_count' => $unreadContacts, // Parfait pour tes badges de notification !
            ],
            'data' => $contacts
        ], 200);
    }

    /**
     * [Admin] Changer le statut de lecture (Marquer comme lu / non lu)
     */
    public function toggleRead(Request $request, $id)
    {
        $contact = ContactRequest::find($id);

        if (!$contact) {
            return response()->json(['success' => false, 'message' => 'Demande introuvable'], 404);
        }

        // Validation du booléen reçu depuis React (true ou false)
        $validator = Validator::make($request->all(), [
            'is_read' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Mise à jour du booléen en base de données
        $contact->update(['is_read' => $request->is_read]);

        return response()->json([
            'success' => true,
            'message' => $request->is_read ? 'Message marqué comme lu.' : 'Message marqué comme non lu.',
            'data' => $contact
        ], 200);
    }

    /**
     * [Admin] Supprimer une demande de contact
     */
    public function destroy($id)
    {
        $contact = ContactRequest::find($id);

        if (!$contact) {
            return response()->json(['success' => false, 'message' => 'Demande introuvable'], 404);
        }
        $contact->delete();
        return response()->json([
            'success' => true,
            'message' => 'Demande de contact supprimée avec succès.'
        ], 200);
    }

    /**
     * Enregistrer une nouvelle demande de test drive depuis le site public
     * Pour le formulaire de test drive (Écran 5)
     */
    public function storeTestDrive(Request $request)
    {
        // 1. Validation stricte des données envoyées par React
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id', // Doit exister dans la table vehicles
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:20',
            'requested_date' => 'required|date|after_or_equal:today', // La date doit être aujourd'hui ou dans le futur
        ]);

        // Si la validation échoue, on renvoie les erreurs à React avec un code 422
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // enregistrement de la demande de test drive dans la base de données
        $testDriveRequest = TestDriveRequest::create([
            'vehicle_id' => $request->vehicle_id,
            'requested_date' => $request->requested_date,
            'phone' => $request->phone,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'status' => 'En attente', // Statut par défaut
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de test drive enregistrée avec succès.',
            'data' => $testDriveRequest
        ], 201);
    }

    /**
     * [Admin] Récupérer les demandes de test drive avec statistiques
     */
    public function indexTestDrives()
    {
        // 1. On calcule les compteurs à la volée grâce au statut
        $totalRequests = TestDriveRequest::count();
        $pendingRequests = TestDriveRequest::where('status', 'En attente')->count();
        $approvedRequests = TestDriveRequest::where('status', 'Approuvée')->count();
        $refusedRequests = TestDriveRequest::where('status', 'Refusée')->count();

        // 2. On récupère les demandes de test drive avec le véhicule associé
        $testDrives = TestDriveRequest::with('vehicle')->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $testDrives,
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'approved_requests' => $approvedRequests,
            'refused_requests' => $refusedRequests,
        ], 200);
    }

    /**
     * [Admin] Changer le statut d'une demande de test drive (Approuvée / Refusée)
     */
    public function updateTestDriveStatus(Request $request, $id)
    {
        $testDriveRequest = TestDriveRequest::find($id);

        if (!$testDriveRequest) {
            return response()->json(['success' => false, 'message' => 'Demande de test drive introuvable'], 404);
        }

        // Validation du statut reçu depuis React
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:En attente,Approuvée,Refusée',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Mise à jour du statut en base de données
        $testDriveRequest->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Statut de la demande de test drive mis à jour avec succès.',
            'data' => $testDriveRequest
        ], 200);
    }

    /**
     * [Admin] Supprimer une demande de test drive
     */
    public function destroyTestDrive($id)
    {
        $testDriveRequest = TestDriveRequest::find($id);

        if (!$testDriveRequest) {
            return response()->json(['success' => false, 'message' => 'Demande de test drive introuvable'], 404);
        }

        $testDriveRequest->delete();

        return response()->json(['success' => true, 'message' => 'Demande de test drive supprimée avec succès.'], 200);
    }

    /**
     * [Admin] afficher une demande de test drive spécifique avec le véhicule associé
     */
    public function showAllTestDrives($id)
    {
        $testDriveRequest = TestDriveRequest::with('vehicle')->find($id);

        if (!$testDriveRequest) {
            return response()->json(['success' => false, 'message' => 'Demande de test drive introuvable'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $testDriveRequest
        ], 200);
    }
}