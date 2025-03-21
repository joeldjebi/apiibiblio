<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
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

class LivreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $user = User::where('id', auth()->user()->id)->first();
        if (empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
                'dev' => "L'utilisateur n'est pas authentifié",
            ], 400);
        }

        $livres = Livre::where([
            'statut' => 1,
        ])
        ->with(['auteur', 'type_publication', 'categorie', 'editeur', 'langue', 'createdBy'])
        ->get();
    
        if ($livres->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Liste des livres récupérée avec succès.',
                'livres' => $livres,
            ], 200);
        }
    
        // Aucun livre trouvé
        return response()->json([
            'success' => false,
            'message' => 'Aucun livre trouvé pour cette catégorie.',
        ], 404);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Livre $livre)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Livre $livre)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Livre $livre)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Livre $livre)
    {
        //
    }
}