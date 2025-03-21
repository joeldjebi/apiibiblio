<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\Wallet_transaction;
use App\Models\Livre;
use App\Models\User;
use Validator;
use Illuminate\Http\Request;
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

class AbonnementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function verifyIfBuyLivre(Request $request)
    {
        // Validation de la requête
        $request->validate([
            'livre_id' => 'required|exists:livres,id',
        ]);

        // Vérification de l'utilisateur authentifié
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401); // Code HTTP 401 pour une erreur d'authentification
        }

        // Recherche du livre
        $livre = Livre::find($request->livre_id);

        if (!$livre) {
            return response()->json([
                'success' => false,
                'message' => 'Livre non trouvé.',
            ], 404); // Code HTTP 404 si le livre n'existe pas
        }

        // Si le livre est gratuit, permettre l'accès directement
        if ($livre->acces_livre == 'gratuit') {
            return response()->json([
                'success' => true,
                'message' => 'Livre gratuit, accès autorisé.',
            ], 200); // Code HTTP 200 pour succès
        }

        // Recherche de la transaction dans le portefeuille pour les livres payants
        $wallet_transaction = Wallet_transaction::where([
            'user_id' => $user->id,
            'livre_id' => $request->livre_id,
        ])->first();

        // Vérification de l'existence de la transaction
        if ($wallet_transaction) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction trouvée, l\'utilisateur a déjà acheté le livre.',
            ], 200); // Code HTTP 200 pour succès
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Aucune transaction trouvée pour cet achat.',
            ], 404); // Code HTTP 404 pour transaction non trouvée
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function buyLivreWithWallet(Request $request)
    {
        // Validation de la requête
        $request->validate([
            'livre_id' => 'required|exists:livres,id',
        ]);

        // Début de la transaction
        DB::beginTransaction();

        try {
            // Vérification de l'utilisateur authentifié
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié.',
                ], 401);
            }

            // Récupération du livre et vérification de sa disponibilité
            $livre = Livre::where('id', $request->livre_id)
                ->where('statut', 1)
                ->first();

            if (!$livre) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun livre trouvé ou livre non disponible.',
                ], 404);
            }

            // Vérification du solde du portefeuille
            if (floatval($user->wallet) < floatval($livre->amount)) {
                return response()->json([
                    'success' => false,
                    'message' => "Vous n'avez pas assez de jetons pour acheter ce livre.",
                ], 402);
            }

            // Déduction du montant du portefeuille
            $user->wallet -= floatval($livre->amount);
            $user->save();

            // Création de la transaction de portefeuille
            $wallet_transaction = new Wallet_transaction();

            $wallet_transaction->user_id = $user->id;
            $wallet_transaction->montant = $livre->amount;
            $wallet_transaction->type_transaction = $livre->acces_livre;
            $wallet_transaction->livre_id = $livre->id;
            $wallet_transaction->date_transaction = now();

            $wallet_transaction->save();

            // Tout s'est bien passé, valider la transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Livre acheté avec succès.',
            ], 200);
        } catch (\Exception $e) {
            // En cas d'erreur, annuler toutes les modifications
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue.',
                'error' => $e->getMessage(), // Optionnel : afficher pour débogage
            ], 500);
        }
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
    public function show(Abonnement $abonnement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Abonnement $abonnement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Abonnement $abonnement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Abonnement $abonnement)
    {
        //
    }
}