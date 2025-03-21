<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TypePublicationController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\LivreController;
use App\Http\Controllers\PaysController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\ChapitreController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AuteurController;
use App\Http\Controllers\PubliciteController;
use App\Http\Controllers\StarController;
use App\Http\Controllers\AbonnementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


// Préfixe pour API v1
Route::prefix('v1')->group(function () {

    // Routes protégées par le middleware d'authentification
    Route::middleware('auth:api')->group(function () {
        // Route de déconnexion
        Route::post('logout', [AuthController::class, 'logout']);

        // Route pour obtenir les informations de l'utilisateur
        Route::post('user-infos', [AuthController::class, 'getUser']);

        // Route pour mettre à jour les informations de l'utilisateur
        Route::post('/user-update/{id}', [AuthController::class, 'updateUser']);

        // Route pour mettre à jour le mot de passe
        Route::post('/password-update/{id}', [AuthController::class, 'updatePassword']);

        // Route pour les type de publications
        Route::post('/type-de-publications', [TypePublicationController::class, 'index']);

        // Route pour les type de publications
        Route::post('/get-livre-by-type-de-publications', [TypePublicationController::class, 'getLivreByTypePublication']);

        // Route pour les categorie de livre
        Route::post('/categorie-livre', [CategorieController::class, 'index']);

        // Route pour les categorie par livre
        Route::post('/get-livre-by-categorie', [CategorieController::class, 'getLivreByCategorie']);

        // Route pour afficher les livres
        Route::post('/get-livre-all', [LivreController::class, 'index']);

        // Route pour afficher les details du livre
        Route::post('/show-livre', [LivreController::class, 'showLivre']);

        // Route pour afficher les livres de la semaine
        Route::post('/get-livre-de-la-semaine', [LivreController::class, 'getLivreAlaUne']);

        // Route pour acheter un livre
        Route::post('/buy-livre-with-wallet', [LivreController::class, 'buyLivreWithWallet']);

        // Route pour afficher les episode par livre
        Route::post('/episode-by-livre', [EpisodeController::class, 'getLivreByEpisode']);

        // Route pour afficher les episode par livre
        Route::post('/get-signed-file-episode', [EpisodeController::class, 'getSignedImageUrlEpisode']);

        // Route pour afficher les chapitre par livre
        Route::post('/chapitre-by-livre', [ChapitreController::class, 'getLivreByChapitre']);

        // Route pour afficher les chapitre par livre
        Route::post('/get-signed-file-chapitre', [ChapitreController::class, 'getSignedImageUrlChapitre']);

        // Route pour afficher les file par livre
        Route::post('/file-by-livre-file', [FileController::class, 'getLivreByFile']);

        // Route pour afficher les file par livre
        Route::post('/get-signed-file', [FileController::class, 'getSignedImageUrlFile']);

        // Route pour signé le cover des livre
        Route::post('/get-signed-image-url-livre-cover', [LivreController::class, 'getSignedImageUrlLivreCover']);

        // Route pour afficher les auteurs
        Route::post('/get-all-auteurs', [AuteurController::class, 'index']);

        // Route pour save historique de lecture
        Route::post('/save-historique-lecteur', [LivreController::class, 'storeHistoriqueDeLecture']);

        // Route pour save historique de lecture
        Route::post('/get-historique-lecteur', [LivreController::class, 'getHistoriqueDeLecture']);

        // Route pour afficher les livres par categorie ou auteur
        Route::post('/get-livres-by-categorie-or-auteur', [LivreController::class, 'getLivresByCategorieOrAuteur']);

        // Route pour afficher les livres par categorie ou auteur
        Route::post('/get-livres-buy-by-user', [LivreController::class, 'getLivreBuyByUser']);

        // Route pour afficher les livres par categorie ou auteur
        Route::post('/get-historique-achat-livre', [LivreController::class, 'getHistoriqueDeAchatDeLivre']);

        // Route pour affcher les publicites
        Route::post('/get-all-publicites', [PubliciteController::class, 'index']);

        // Route pour affcher les livre ou les magazine noté
        Route::post('/store-star', [StarController::class, 'store']);

        // Route pour affcher les livre ou les magazine noté
        Route::post('/get-all-livre-or-magazine-star', [StarController::class, 'indexLivreOrMagazine']);

        // Route pour affcher les livre ou les magazine noté
        Route::post('/get-all-episode-or-chapitre-star', [StarController::class, 'indexEpisodeOrChapitre']);

        // Route pour verifier si le livre a deja ete acheter ou pas
        Route::post('/verify-if-buy-livre', [AbonnementController::class, 'verifyIfBuyLivre']);

        // Route pour buy livre with wallet
        Route::post('/buy-livre-with-wallet', [AbonnementController::class, 'buyLivreWithWallet']);

    });

    // Routes publiques
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Route pour verifier si le numero de telephone existe
    Route::post('otp-register', [AuthController::class, 'verifyNumberExist']);

    // Route pour verifier si le otp est correcte
    Route::post('verify-otp-register', [AuthController::class, 'verifyOtp']);

    // Route pour verifier si le otp est correcte
    Route::post('verify-mobile-and-otp-password-forget', [AuthController::class, 'verifyNumberPasswordForget']);

    // Route pour verifier le otp de mot de passe oublié
    Route::post('verify-otp-password-forget', [AuthController::class, 'verifyOtpPasswordForget']);

    // Route pour mettre a jour le mot de passe oublié
    Route::post('update-password-forget', [AuthController::class, 'passwordForgetUpdate']);

    // Route pour les categorie par livre
    Route::post('/get-pays-all', [PaysController::class, 'indexPaysAll']);
});








