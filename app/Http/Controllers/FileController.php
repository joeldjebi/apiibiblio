<?php

namespace App\Http\Controllers;

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

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getLivreByFile(Request $request)
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

        // Recherche du files
        $files = File::where(['livre_id' => $request->livre_id])->get();

        // Vérification si le livre existe et si episode_id n'est pas null
        if ($files) {
            return response()->json([
                'success' => true,
                'message' => 'Les fichiers.',
                'files' => $files
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Aucun fichier associé à ce livre ou livre introuvable.',
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
    public function show(File $file)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(File $file)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, File $file)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(File $file)
    {
        //
    }

    public function getSignedImageUrlFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
    
        $fileName = basename($request->filename);
    
        // Vérifie si l'image existe et génère une URL temporaire
        $imagePath = 'path/' . $fileName; // Assurez-vous que "path/" correspond à votre structure
        
        if (Storage::disk('wasabi')->exists($imagePath)) {
            // Générez l'URL temporaire pour un accès limité
            $url = Storage::disk('wasabi')->temporaryUrl(
                $imagePath,
                now()->addMinutes(20)
            );
    
            // Retourne l'URL dans une réponse JSON
            return response()->json(['imageUrl' => $url]);
        }
    
        return response()->json(['error' => 'The file does not exist in storage.'], 404);
    }
    
}