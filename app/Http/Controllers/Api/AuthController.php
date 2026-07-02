<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Connexion de l'administrateur / agent
     * Pour l'Écran 5 (Login)
     */
    public function login(Request $request)
    {
        // 1. Validation des champs saisis par l'utilisateur
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Vérification de l'existence de l'utilisateur
        $user = User::where('email', $request->email)->first();

        // 3. Vérification du mot de passe crypté
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects. Veuillez réessayer.'
            ], 401); // 401: Unauthorized
        }

        // 4. Génération du Token Sanctum
        $token = $user->createToken('admin_auth_token')->plainTextToken;

        // 5. On renvoie les infos de l'utilisateur et son Token à React
        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie !',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role
            ],
            'token'   => $token
        ], 200);
    }

    /**
     * Déconnexion (Révocation du token)
     */
    public function logout(Request $request)
    {
        // On supprime le token actuel qui a servi à faire la requête
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user'    => $request->user()
        ], 200);
    }

    // Add user
    public function store()
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => Hash::make(request('password')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'user' => $user
        ], 201);
    }

    // List users
    public function index()
    {
        return response()->json([
            'success' => true,
            'users' => User::all()
        ], 200);
    }

    // Show user
    public function show($id)
    {
        return response()->json([
            'success' => true,
            'user' => User::find($id)
        ], 200);
    }

    // Update user
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user
        ], 200);
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ], 200);
    }

    // Modifier son propre mot de passe
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect'
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès'
        ], 200);
    }
}