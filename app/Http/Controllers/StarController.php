<?php

namespace App\Http\Controllers;

use App\Models\Star;
use App\Models\Type_publication;
use App\Models\Livre;
use App\Models\User;
use App\Models\Auteur;
use Illuminate\Http\Request;
use Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class StarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function indexLivreOrMagazine()
    {
        $user = User::where('id', auth()->user()->id)->first();
        if (empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
                'dev' => "L'utilisateur n'est pas authentifié",
            ], 400);
        }

        $stars = Star::where('type_publication_id', 1)
        ->orWhere('type_publication_id', 2)
        ->with(['livre', 'livre.auteur', 'livre.type_publication', 'livre.categorie', 'livre.editeur', 'livre.langue', 'livre.createdBy', 'user'])
        ->get();
    
        if ($stars->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Liste des stars récupérée avec succès.',
                'data' => $stars,
            ], 200);
        }
    
        // Aucun livre trouvé
        return response()->json([
            'success' => false,
            'message' => 'Aucun livre trouvé pour cette catégorie.',
            'data' => $stars,
        ], 404);
    }

    /**
     * Display a listing of the resource.
     */
    public function indexEpisodeOrChapitre()
    {
        $user = User::where('id', auth()->user()->id)->first();
        if (empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
                'dev' => "L'utilisateur n'est pas authentifié",
            ], 400);
        }

        $stars = Star::where('type_publication_id', 3)
        ->orWhere('type_publication_id', 4)
        ->with(['livre', 'livre.auteur', 'livre.type_publication', 'livre.categorie', 'livre.editeur', 'livre.langue', 'livre.createdBy', 'user'])
        ->get();
    
        if ($stars->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Liste des stars récupérée avec succès.',
                'data' => $stars,
            ], 200);
        }
    
        // Aucun livre trouvé
        return response()->json([
            'success' => false,
            'message' => 'Aucun livre trouvé pour cette catégorie.',
            'data' => $stars,
        ], 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation des champs
        $request->validate([
            'livre_id' => 'required|exists:livres,id', // Assurez-vous que "livres" est la bonne table
            'type_publication_id' => 'required|exists:type_publications,id', // Assurez-vous que "type_publications" est la bonne table
            'star' => 'required|numeric|between:1,5',
        ]);
    
        // Récupération de l'utilisateur authentifié
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }
    
        // Vérification de l'existence d'un enregistrement
        $existing = Star::where('user_id', $user->id)
            ->where('livre_id', $request->livre_id)
            ->where('type_publication_id', $request->type_publication_id)
            ->first();
    
        if ($existing) {
            // Mise à jour de la variable star
            $existing->update([
                'star' => $request->star,
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Avis mis à jour avec succès.',
            ], 200);
        }
    
        // Création d'un nouvel enregistrement si aucun n'existe
        Star::create([
            'user_id' => $user->id,
            'livre_id' => $request->livre_id,
            'type_publication_id' => $request->type_publication_id,
            'star' => $request->star,
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'Avis enregistré avec succès.',
        ], 201);
    }
    

    /**
     * Display the specified resource.
     */
    public function show(Star $star)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Star $star)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Star $star)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Star $star)
    {
        //
    }
}