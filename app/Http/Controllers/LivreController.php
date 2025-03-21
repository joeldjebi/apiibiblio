<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\Livre;
use App\Models\User;
use App\Models\Auteur;
use App\Models\Forfait;
use App\Models\Historique_de_lecture;
use App\Models\Wallet_transaction;
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
use Illuminate\Support\Facades\Storage;


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
            'pays_id' => $user->pays_id
        ])
        ->with(['auteur', 'type_publication', 'categorie', 'editeur', 'langue', 'createdBy'])
        ->get()
        ->map(function ($livre) {
            // Vérifier si l'image existe et générer l'URL signée
            if (!empty($livre->image_cover)) {
                $imagePath = 'image_cover_livre/' . $livre->image_cover;
                $livre->image_cover_signed_url = Storage::disk('wasabi')->temporaryUrl(
                    $imagePath, now()->addMinutes(20)
                );
            } else {
                $livre->image_cover_signed_url = null;
            }

            return $livre;
        });

        if ($livres->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Liste des livres récupérée avec succès.',
                'livres' => $livres,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucun livre trouvé pour cette catégorie.',
        ], 404);
    }

    /**
     * Display a listing of the resource.
     */
    public function showLivre(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'livre_id' => 'required|numeric|exists:livres,id',
        ]);

        $user = User::where('id', auth()->user()->id)->first();
        if (empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
                'dev' => "L'utilisateur n'est pas authentifié",
            ], 400);
        }

        $livre = Livre::findOrFail($request->livre_id);
        if (empty($livre)) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
                'dev' => "Livre introuvable",
            ], 400);
        }

        // $livres = Livre::where([
        //     'statut' => 1,
        //     'id' => $livre->id
        // ])
        // ->with(['auteur', 'type_publication', 'categorie', 'editeur', 'langue', 'createdBy'])
        // ->get();

        $livres = Livre::where([
            'statut' => 1,
            'pays_id' => $user->pays_id,
            'id' => $livre->id
        ])
        ->with(['auteur', 'type_publication', 'categorie', 'editeur', 'langue', 'createdBy'])
        ->get()
        ->map(function ($livre) {
            // Vérifier si l'image existe et générer l'URL signée
            if (!empty($livre->image_cover)) {
                $imagePath = 'image_cover_livre/' . $livre->image_cover;
                $livre->image_cover_signed_url = Storage::disk('wasabi')->temporaryUrl(
                    $imagePath, now()->addMinutes(20)
                );
            } else {
                $livre->image_cover_signed_url = null;
            }

            return $livre;
        });

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
    public function buyLivreWithWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'livre_id' => 'required|numeric|exists:livres,id',
            'type_achat' => 'required|in:gratuit,achat,abonnement,achat_et_abonnement',
            'forfait_id' => 'nullable|numeric|exists:forfaits,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $livre = Livre::findOrFail($request->livre_id);

        // Vérification du type d'achat en fonction de l'offre du livre
        $typeAchat = $request->type_achat;

        // Vérifiez si l'utilisateur a déjà acheté le livre
        if ($user->wallet_transaction()->where('livre_id', $livre->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Livre déjà acheté.'], 400);
        }

        // Vérification pour les livres gratuits : seul 'gratuit' est autorisé
        if ($livre->amount == 0 && $typeAchat !== 'gratuit') {
            return response()->json(['success' => false, 'message' => "Ce livre est gratuit, vous ne pouvez pas l'acheter ou vous abonner."], 400);
        }

        // Si le livre nécessite un abonnement ou achat_et_abonnement, l'achat et l'abonnement sont permis
        if (($livre->acces_livre == 'abonnement' || $livre->acces_livre == 'achat_et_abonnement') && $typeAchat !== 'abonnement' && $typeAchat !== 'achat_et_abonnement') {
            return response()->json(['success' => false, 'message' => 'Ce livre nécessite un abonnement pour être acheté.'], 400);
        }

        // Si le livre nécessite un achat ou achat_et_abonnement, l'achat et l'abonnement sont permis
        if (($livre->acces_livre == 'achat' || $livre->acces_livre == 'achat_et_abonnement') && $typeAchat !== 'achat' && $typeAchat !== 'achat_et_abonnement') {
            return response()->json(['success' => false, 'message' => 'Ce livre nécessite un achat pour en avoir accès.'], 400);
        }

        // Si le livre nécessite l'achat et l'abonnement, on permet l'achat ou l'abonnement ou les deux
        if ($livre->acces_livre == 'achat_et_abonnement' && !in_array($typeAchat, ['achat', 'abonnement', 'achat_et_abonnement'])) {
            return response()->json(['success' => false, 'message' => 'Ce livre nécessite à la fois un achat et un abonnement.'], 400);
        }

        // Début de la transaction de base de données
        DB::beginTransaction();

        try {
            // Si l'utilisateur a choisi "achat"
            if ($typeAchat === 'achat' && $livre->acces_livre == 'achat') {
                if ($user->wallet < $livre->amount) {
                    return response()->json(['success' => false, 'message' => 'Solde insuffisant pour l\'achat.'], 400);
                }

                // Déduire le montant du wallet pour un achat
                $user->wallet -= $livre->amount;
                $user->save();

            }
            // Si l'utilisateur a choisi "abonnement"
            elseif ($typeAchat === 'abonnement' && $livre->acces_livre == 'abonnement') {
                $forfait = Forfait::findOrFail($request->forfait_id);

                // Vérification du forfait valide
                if (!$forfait) {
                    return response()->json(['success' => false, 'message' => 'Forfait invalide.'], 400);
                }

                // Annuler tous les abonnements actifs
                $user->abonnements()
                    ->where('statut', 'actif')
                    ->update(['statut' => 'inactif']);

                // Créer un nouvel abonnement avec le nouveau forfait
                $user->abonnements()->create([
                    'forfait_id' => $forfait->id,
                    'date_debut' => now(),
                    'date_fin' => now()->addMonths($forfait->duree),
                    'statut' => 'actif',
                ]);

                // Vérification du solde pour l'abonnement
                if ($user->wallet < $livre->amount) {
                    return response()->json(['success' => false, 'message' => 'Solde insuffisant pour l\'abonnement.'], 400);
                }

                // Déduire le montant du wallet pour l'abonnement
                $user->wallet -= $livre->amount;
                $user->abonnement_expires_at = now()->addMonths($forfait->duree);
                $user->save();

            }
            // Si l'utilisateur choisit "gratuit"
            elseif ($typeAchat === 'gratuit') {
                if ($livre->amount != 0) {
                    return response()->json(['success' => false, 'message' => 'Le livre n\'est pas gratuit.'], 400);
                }
            }
            // Cas "achat_et_abonnement" (l'utilisateur choisit à la fois l'achat et l'abonnement)
            elseif ($typeAchat === 'achat_et_abonnement' && $livre->acces_livre == 'achat_et_abonnement') {
                // Vérifier si l'utilisateur a sélectionné un abonnement avec forfait
                if ($request->has('forfait_id')) {
                    $forfait = Forfait::findOrFail($request->forfait_id);

                    // Annuler tous les abonnements actifs
                    $user->abonnements()
                        ->where('statut', 'actif')
                        ->update(['statut' => 'inactif']);

                    // Créer un nouvel abonnement avec le forfait sélectionné
                    $user->abonnements()->create([
                        'forfait_id' => $forfait->id,
                        'date_debut' => now(),
                        'date_fin' => now()->addMonths($forfait->duree),
                        'statut' => 'actif',
                    ]);
                }

                // Vérification du solde pour l'achat et l'abonnement
                if ($user->wallet < $livre->amount) {
                    return response()->json(['success' => false, 'message' => 'Solde insuffisant.'], 400);
                }

                // Déduire le montant du wallet pour l'achat et l'abonnement
                $user->wallet -= $livre->amount;
                $user->abonnement_expires_at = now()->addMonths($forfait->duree);
                $user->save();

            } else {
                return response()->json(['success' => false, 'message' => 'Type d\'achat invalide.'], 400);
            }

            // Enregistrer la transaction dans tous les cas
            // Créer une fonction pour déterminer le type de transaction
            function getTransactionType(string $typeAchat): string {
                if ($typeAchat == 'achat') {
                    return 'achat_livre';
                } elseif ($typeAchat == 'abonnement') {
                    return 'abonnement';
                } else {
                    return 'gratuit';
                }
            }

            $wallet_transaction = new Wallet_transaction();

            $wallet_transaction->livre_id = $livre->id;
            $wallet_transaction->user_id = $user->id;
            $wallet_transaction->montant = $livre->amount;
            $wallet_transaction->type_transaction = getTransactionType($typeAchat);  // Appel à la fonction
            $wallet_transaction->date_transaction = now();

            $wallet_transaction->save();


            // Commit de la transaction
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Achat effectué avec succès.']);

        } catch (\Exception $e) {
            // Loguer l'exception pour obtenir plus de détails
            \Log::error('Erreur lors de l\'achat livre: '.$e->getMessage(), ['exception' => $e]);

            // En cas d'erreur, annuler la transaction
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Une erreur est survenue, veuillez réessayer.'], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function getLivreAlaUne(Request $request)
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
            'alaune' => 1,
            'pays_id' => $user->pays_id
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
     * Display the specified resource.
     */
    public function storeHistoriqueDeLecture(Request $request)
    {
        // Validation des champs avec au moins un des trois requis
        $request->validate([
            'position' => 'nullable|string|max:20',
            'file_id' => 'nullable|exists:files,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'chapitre_id' => 'nullable|exists:chapitres,id',
        ]);

        // Vérification qu'au moins un champ parmi file_id, episode_id, chapitre_id est renseigné
        if (!$request->file_id && !$request->episode_id && !$request->chapitre_id) {
            return response()->json([
                'success' => false,
                'message' => 'Au moins un des champs file_id, episode_id ou chapitre_id est requis.',
            ], 422);
        }

        // Récupération de l'utilisateur authentifié
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        // Recherche d'un historique existant correspondant
        $historique = Historique_de_lecture::where('user_id', $user->id)
            ->where(function ($query) use ($request) {
                $query->when($request->file_id, fn($q) => $q->where('file_id', $request->file_id))
                      ->when($request->episode_id, fn($q) => $q->where('episode_id', $request->episode_id))
                      ->when($request->chapitre_id, fn($q) => $q->where('chapitre_id', $request->chapitre_id));
            })
            ->first();

        if ($historique) {
            // Mise à jour si un enregistrement existe
            $historique->position = $request->position ?? $historique->position;
            $historique->save();

            return response()->json([
                'success' => true,
                'message' => 'Historique de lecture mis à jour avec succès.',
            ], 200);
        }

        // Création d'un nouvel enregistrement si aucun n'existe
        $historique = Historique_de_lecture::create([
            'user_id' => $user->id,
            'file_id' => $request->file_id,
            'episode_id' => $request->episode_id,
            'chapitre_id' => $request->chapitre_id,
            'position' => $request->position,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nouvel historique de lecture ajouté avec succès.',
        ], 201);
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function getHistoriqueDeLecture()
    {
        // Vérification de l'utilisateur authentifié
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401); // Code 401 pour utilisateur non authentifié
        }

        // Récupération de l'historique de lecture
        $historique = Historique_de_lecture::where('user_id', $user->id)
            ->whereNotNull('file_id') // Vérifie que file_id n'est pas null
            ->with([
                'file', 'file.livre', 'file.livre.type_publication', 'file.livre.auteur',
                'file.livre.categorie', 'file.livre.editeur', 'file.livre.pays',
                'file.livre.langue'
                ])
            ->get();

        // Vérification des résultats
        if ($historique->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Historique récupéré avec succès.',
                'historique' => $historique,
            ], 200);
        }

        // Aucun historique trouvé
        return response()->json([
            'success' => false,
            'message' => 'Aucun historique trouvé.',
        ], 404);
    }


    /**
     * Update the specified resource in storage.
     */
    public function getLivresByCategorieOrAuteur(Request $request)
	{
		// Validation des champs
		$request->validate([
			'auteur_id' => 'nullable|exists:auteurs,id',
			'categorie_id' => 'nullable|exists:categories,id',
			'vedette' => 'nullable|boolean',
		]);

		// Requête de récupération des livres
		$livresQuery = Livre::query();

		// Appliquer les filtres uniquement si les valeurs sont présentes et non nulles
		if ($request->filled('auteur_id')) {
			$livresQuery->where('auteur_id', $request->auteur_id);
		}

		if ($request->filled('categorie_id')) {
			$livresQuery->where('categorie_id', $request->categorie_id);
		}

		// Appliquer le filtre 'vedette' seulement si une valeur est spécifiée (vraie ou fausse)
		if ($request->has('vedette') && !is_null($request->vedette)) {
			$livresQuery->where('vedette', $request->vedette);
		}

		// Afficher la requête SQL générée pour le débogage
		//dd($livresQuery->toSql());

		// Récupérer les livres avec leurs relations
		$livres = $livresQuery->with(['type_publication', 'auteur', 'categorie', 'editeur', 'langue', 'createdBy'])->get();

		// Vérifier si aucun livre n'est trouvé
		if ($livres->isEmpty()) {
			return response()->json([
				'success' => false,
				'message' => 'Aucun livre trouvé pour les critères donnés.',
			], 404);
		}

		// Classer les livres par type_publication
		$groupedLivres = $livres->groupBy(function ($livre) {
			return $livre->typePublication ? $livre->typePublication->libelle : 'Inconnu';
		});

		// Retourner les livres
		return response()->json([
			'success' => true,
			'message' => 'Livres récupérés avec succès.',
			'data' => $groupedLivres->values(),
		], 200);
	}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Livre $livre)
    {
        //
    }

    public function getSignedImageUrlLivreCover(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required'
        ]);

        $fileName = basename($request->filename);
        // Vérifie si l'image existe et génère une URL temporaire
        if (!empty($fileName)) {
            $imagePath = 'image_cover_livre/' . $fileName; // Assurez-vous que le chemin est correct
            // Générez l'URL temporaire pour un accès limité
            $url = Storage::disk('wasabi')->temporaryUrl(
                $imagePath, now()->addMinutes(20)
            );

            // Retourne l'URL dans une réponse JSON
            return response()->json(['imageUrl' => $url]);
        }

        return response()->json(['error' => 'Image name is empty or invalid'], 400);
    }

    /**
     * Display a listing of the resource.
     */
    // public function getLivreBuyByUser(Request $request)
    // {

    //     $user = User::where('id', auth()->user()->id)->first();
    //     if (empty($user)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Utilisateur introuvable',
    //             'dev' => "L'utilisateur n'est pas authentifié",
    //         ], 400);
    //     }

    //     $wallet_transactions = Wallet_transaction::where([
    //         'user_id' => auth()->user()->id
    //     ])
    //     ->with(['livre.auteur', 'livre.type_publication', 'livre.categorie', 'livre.editeur', 'livre.langue', 'livre.createdBy'])
    //     ->get();

    //     if ($wallet_transactions->isNotEmpty()) {
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Liste des livres achetés récupérée avec succès.',
    //             'wallet_transactions' => $wallet_transactions,
    //         ], 200);
    //     }

    //     // Aucun livre trouvé
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Aucun livre trouvé.',
    //     ], 404);
    // }

    // public function getLivreBuyByUser(Request $request)
    // {
    //     $user = auth()->user();
    //     if (!$user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Utilisateur introuvable',
    //             'dev' => "L'utilisateur n'est pas authentifié",
    //         ], 400);
    //     }

    //     $wallet_transactions = Wallet_transaction::where('user_id', $user->id)
    //         ->with([
    //             'livre.auteur',
    //             'livre.type_publication',
    //             'livre.categorie',
    //             'livre.editeur',
    //             'livre.langue',
    //             'livre.createdBy'
    //         ])
    //         ->get()
    //         ->map(function ($transaction) {
    //             if ($transaction->livre && !empty($transaction->livre->image_cover)) {
    //                 $imagePath = 'image_cover_livre/' . $transaction->livre->image_cover;
    //                 $transaction->livre->image_cover_signed_url = Storage::disk('wasabi')->temporaryUrl(
    //                     $imagePath, now()->addMinutes(20)
    //                 );
    //             } else {
    //                 $transaction->livre->image_cover_signed_url = null;
    //             }
    //             return $transaction;
    //         });

    //     if ($wallet_transactions->isNotEmpty()) {
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Liste des livres achetés récupérée avec succès.',
    //             'wallet_transactions' => $wallet_transactions,
    //         ], 200);
    //     }

    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Aucun livre trouvé.',
    //     ], 404);
    // }

	public function getLivreBuyByUser(Request $request)
    {
        $user = User::find(auth()->id());

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
                'dev' => "L'utilisateur n'est pas authentifié",
            ], 400);
        }

        $wallet_transactions = Wallet_transaction::where('user_id', $user->id)
            ->with([
                'livre.auteur',
                'livre.type_publication',
                'livre.categorie',
                'livre.editeur',
                'livre.langue',
                'livre.createdBy',
                'livre.stars' => function ($query) use ($user) {
                    // Récupérer uniquement l'avis de l'utilisateur connecté
                    $query->where('user_id', $user->id);
                }
            ])
            ->get()
            ->groupBy('livre.type_publication.libelle'); // Regrouper par type de publication

        if ($wallet_transactions->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Liste des livres achetés classée par type de publication.',
                'wallet_transactions' => $wallet_transactions,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucun livre trouvé.',
        ], 404);
    }

    public function getHistoriqueDeAchatDeLivre(Request $request)
    {
        $user = User::find(auth()->id());

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
                'dev' => "L'utilisateur n'est pas authentifié",
            ], 400);
        }

        $wallet_transactions = Wallet_transaction::where('user_id', $user->id)
            ->with([
                'livre' => function ($query) {
                    $query->select('id', 'titre', 'amount', 'categorie_id', 'auteur_id', 'editeur_id', 'langue_id');
                },
                'livre.auteur' => function ($query) {
                    $query->select('id', 'nom', 'prenoms');
                },
                'livre.categorie' => function ($query) {
                    $query->select('id', 'libelle');
                },
                'livre.editeur' => function ($query) {
                    $query->select('id', 'nom', 'prenoms');
                },
                'livre.langue' => function ($query) {
                    $query->select('id', 'libelle');
                },
            ])
            ->get();

        if ($wallet_transactions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun livre trouvé.',
            ], 404);
        }

        // Transformation des données pour obtenir exactement les champs souhaités
        $result = $wallet_transactions->map(function ($transaction) {
            // Vérification que la relation 'livre' est chargée
            if (!$transaction->livre) {
                return null;
            }

            return [
                "titre"            => $transaction->livre->titre,
                "amount"            => $transaction->livre->amount,
                "categorie_libelle"=> optional($transaction->livre->categorie)->libelle,
                "auteur_nom"       => optional($transaction->livre->auteur)->nom,
                "auteur_prenoms"   => optional($transaction->livre->auteur)->prenoms,
                "editeur_nom"      => optional($transaction->livre->editeur)->nom,
                "editeur_prenoms"  => optional($transaction->livre->editeur)->prenoms,
                "langue_libelle"   => optional($transaction->livre->langue)->libelle,
            ];
        })->filter(); // pour supprimer d'éventuels null

        return response()->json([
            'success' => true,
            'message' => 'Liste des livres achetés.',
            'data'    => $result->values(), // réindexer le tableau
        ], 200);
    }


}
