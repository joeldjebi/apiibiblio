<?php

namespace App\Http\Controllers;

use App\Models\Auteur;
use App\Models\File;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Livre;
use Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AuteurController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Vérification de l'utilisateur
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        // Récupérer les auteurs avec au moins un livre
        $auteurs = Auteur::whereHas('livres') // Vérifie qu'un auteur a au moins un livre
            ->withCount('livres') // Compte le nombre de livres pour chaque auteur
            ->orderBy('livres_count', 'desc') // Tri par nombre de livres, optionnel
            ->get();

        // Vérification des résultats
        if ($auteurs->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Liste des auteurs avec au moins un livre.',
                'auteurs' => $auteurs,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucun auteur trouvé avec des livres.',
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
    public function show(Auteur $auteur)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Auteur $auteur)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Auteur $auteur)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Auteur $auteur)
    {
        //
    }
}