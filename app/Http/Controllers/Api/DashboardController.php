<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Models\TestDriveRequest;
use App\Models\Vehicle;
use App\Models\ContactRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    // summary of the dashboard data
    public function getSummary(Request $request)
    {
        $user = $request->user(); // L'utilisateur authentifié (via Sanctum/JWT)

        // Récupération et filtrage des véhicules selon le rôle
        $vehiclesQuery = Vehicle::with('user:id,name');

        if ($user->role !== 'admin') {
            // Un agent classique ne voit que ses véhicules
            $vehiclesQuery->where('user_id', $user->id);
        }
        $vehicles = $vehiclesQuery->latest()->get();

        // Récupération des IDs des véhicules concernés pour filtrer le reste
        $vehicleIds = $vehicles->pluck('id');

        // Demandes de contact liées aux véhicules accessibles
        $requests = ContactRequest::with('vehicle')
            ->where('is_read', false)
            ->where(function ($query) use ($vehicleIds) {
                $query->whereIn('vehicle_id', $vehicleIds)
                    ->orWhereNull('vehicle_id');
            })
            ->latest()
            ->get();
        // Rendez-vous d'essai liés aux véhicules accessibles
        $appointments = TestDriveRequest::with('vehicle')
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereIn('status',  ['En attente', 'Approuvée']) // On ne veut que les rendez-vous en attente ou approuvés
            ->latest()
            ->get();

        // Historique des ventes (Si admin -> toutes les ventes, si agent -> uniquement ses ventes)
        $salesQuery = Sales::with(['vehicle', 'user:id,name']);
        if ($user->role !== 'admin') {
            $salesQuery->where('user_id', $user->id);
        }
        $sales = $salesQuery->latest()->get();

        // Calcul des statistiques globales pour l'onglet Vue d'ensemble
        $stats = [
            'total_vehicles' => $vehicles->count(),
            'available_vehicles' => $vehicles->where('status', 'Disponible')->count(),
            'pending_requests' => $requests->count(),
            'pending_appointments' => $appointments->where('status', 'En attente')->count(),
            'total_sales_count' => $sales->count(),
            'total_revenue' => $sales->sum('final_price'),
        ];

        return response()->json([
            'stats' => $stats,
            'vehicles' => $vehicles,
            'requests' => $requests,
            'appointments' => $appointments,
            'sales' => $sales
        ]);
    }

    // Update appointment status
    public function updateAppointment(Request $request, $id)
    {
        $appointment = TestDriveRequest::find($id);
        if (!$appointment) {
            return response()->json(['success' => false, 'message' => 'Rendez-vous introuvable'], 404);
        }
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:En attente,Approuvée,Terminée,Annulée',
            'requested_time' => 'sometimes|date_format:H:i', // Format HH:MM
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $appointment->update(['status' => $request->status, 'requested_time' => $request->requested_time]);
        return response()->json([
            'success' => true,
            'message' => 'Statut de la demande de test drive mis à jour avec succès.',
            'data' => $appointment
        ], 200);
    }

    // Store a new sale
    public function storeSale(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'user_id' => 'required|exists:users,id',
            'buyer_name' => 'required|string|max:255',
            'final_price' => 'required|numeric|min:0',
            'sale_date' => 'required|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $sale = Sales::create($request->all());

        // Update the vehicle status to 'Vendu'
        $vehicle = Vehicle::find($request->vehicle_id);
        if ($vehicle) {
            $vehicle->update(['status' => 'Vendu']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vente ajoutée avec succès.',
            'data' => $sale
        ], 200);
    }

    /**
     * Liste des clients ayant fait une demande de contact ou un rendez-vous d'essai pour les véhicules 
     */
    public function getClients(Request $request)
    {
        // Récupération des clients ayant fait une demande de contact ou un rendez-vous d'essai
        $contacts = ContactRequest::with('vehicle')->latest()->get();
        $appointments = TestDriveRequest::with('vehicle')->latest()->get();

        // On regroupe par email plutôt que de ne garder qu'un seul enregistrement par
        // client (unique()) : un client peut avoir plusieurs demandes de contact et/ou
        // plusieurs demandes d'essai (pour différents véhicules), on veut TOUT
        // remonter pour lui, pas seulement la toute première demande rencontrée.
        $allRecords = $contacts->concat($appointments);
        $grouped = $allRecords->groupBy('email');

        $clients = $grouped->map(function ($records, $email) {
            // Identité (nom, téléphone) prise sur l'enregistrement le plus récent,
            // qui a le plus de chances d'être à jour si le client a redonné ses infos.
            $mostRecent = $records->sortByDesc('created_at')->first();

            $contactsForClient = $records->filter(fn ($r) => $r instanceof ContactRequest)->values();
            $appointmentsForClient = $records->filter(fn ($r) => $r instanceof TestDriveRequest)->values();

            return [
                'email' => $email,
                'first_name' => $mostRecent->first_name,
                'last_name' => $mostRecent->last_name,
                'phone' => $mostRecent->phone,
                'contacts' => $contactsForClient,
                'appointments' => $appointmentsForClient,
                'contacts_count' => $contactsForClient->count(),
                'appointments_count' => $appointmentsForClient->count(),
                'last_activity_at' => $mostRecent->created_at,
            ];
        })->sortByDesc('last_activity_at')->values();

        return response()->json([
            'success' => true,
            'clients' => $clients
        ], 200);
    }
}
