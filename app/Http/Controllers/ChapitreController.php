<?php

namespace App\Http\Controllers;

use App\Models\Chapitre;
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
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChapitreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getLivreByChapitre(Request $request)
    {
        // Validation des entrées
        $validator = Validator::make($request->all(), [
            'livre_id' => 'required|numeric|exists:livres,id'
        ]);

        // Vérification de l'utilisateur
        $user = User::where('id', auth()->user()->id)->first();
        if (empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
                'dev' => "L'utilisateur n'est pas authentifié",
            ], 400);
        }

        // Recherche du livre
        $chapitres = Chapitre::where(['livre_id' => $request->livre_id])->get();
        // Parcourir chaque chapitre pour générer l'URL signée
        foreach ($chapitres as $chapitre) {
            // Vérifiez que le 'path' existe avant de générer l'URL signée
            if (!empty($chapitre->path)) {
                // Générer l'URL temporaire pour chaque fichier
                $chapitre->path = Storage::disk('wasabi')->temporaryUrl(
                    $chapitre->path, now()->addMinutes(20)
                );
            }
        }

        // Vérification si le chapitres existe et si chapitre_id n'est pas null
        if ($chapitres) {
            return response()->json([
                'success' => true,
                'message' => 'Les chapitres par chapitres.',
                'chapitres' => $chapitres
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Aucun chapitre associé à ce livre ou livre introuvable.',
            ], 404);
        }
    }


    public function getSignedImageUrlChapitre(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required'
        ]);

        $fileName = basename($request->filename);
        // Vérifie si l'image existe et génère une URL temporaire
        if (!empty($fileName)) {
            $imagePath = 'file_chapitre/' . $fileName; // Assurez-vous que le chemin est correct
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
    public function show(Chapitre $chapitre)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Chapitre $chapitre)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Chapitre $chapitre)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Chapitre $chapitre)
    {
        //
    }

}
