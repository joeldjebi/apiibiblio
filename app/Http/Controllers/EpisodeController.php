<?php

namespace App\Http\Controllers;

use App\Models\Episode;
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

class EpisodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getLivreByEpisode(Request $request)
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

        // Recherche du episodes
        $episodes = Episode::where(['livre_id' => $request->livre_id])->get();
            // Parcourir chaque épisode pour générer l'URL signée pour le chemin 'path'
        foreach ($episodes as $episode) {
            // Vérifier que le 'path' existe avant de générer l'URL signée
            if (!empty($episode->path)) {
                $episode->path = Storage::disk('wasabi')->temporaryUrl(
                    $episode->path, now()->addMinutes(20) // Durée d'expiration
                );
            }
        }

        // Vérification si le livre existe et si episode_id n'est pas null
        if ($episodes) {
            return response()->json([
                'success' => true,
                'message' => 'Les épisodes.',
                'episodes' => $episodes
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Aucun épisode associé à ce livre ou livre introuvable.',
            ], 404);
        }
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
    public function show(Episode $episode)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Episode $episode)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Episode $episode)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Episode $episode)
    {
        //
    }

    public function getSignedImageUrlEpisode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required'
        ]);

        $fileName = basename($request->filename);
        // Vérifie si l'image existe et génère une URL temporaire
        if (!empty($fileName)) {
            $imagePath = 'file_episode/' . $fileName; // Assurez-vous que le chemin est correct
            // Générez l'URL temporaire pour un accès limité
            $url = Storage::disk('wasabi')->temporaryUrl(
                $imagePath, now()->addMinutes(20)
            );

            // Retourne l'URL dans une réponse JSON
            return response()->json(['imageUrl' => $url]);
        }

        return response()->json(['error' => 'Image name is empty or invalid'], 400);
    }
}
