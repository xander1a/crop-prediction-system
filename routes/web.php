<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\CropController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\RegionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\CropPriceController;

use App\Http\Controllers\CropPricePredictionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\YearlyPredictionController;
use App\Http\Controllers\CropPredictionController1;
use Symfony\Component\Process\Process;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Price Predictions
    Route::prefix('predictions')->group(function () {
        Route::get('/', [PredictionController::class, 'index'])->name('predictions.index');
  });
    
    // Market Data
    Route::prefix('market')->group(function () {
        Route::get('/', [MarketController::class, 'index'])->name('market.index');
        Route::get('/trends', [MarketController::class, 'trends'])->name('market.trends');
        Route::get('/analysis', [MarketController::class, 'analysis'])->name('market.analysis');
    });
    
    // Crops Management
    Route::prefix('crops')->group(function () {
        Route::get('/', [CropController::class, 'index'])->name('crops.index');
        Route::post('/', [CropController::class, 'store'])->name('crops.store');
        Route::get('/{crop}', [CropController::class, 'show'])->name('crops.show');
    });
    
    // Alerts
    Route::prefix('alerts')->group(function () {
        Route::get('/', [AlertController::class, 'index'])->name('alerts.index');
        Route::post('/', [AlertController::class, 'store'])->name('alerts.store');
        Route::delete('/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');
        Route::put('/{alert}/read', [AlertController::class, 'markAsRead'])->name('alerts.read');
    });
    
    // Regions
    Route::prefix('regions')->group(function () {
        Route::get('/', [RegionController::class, 'index'])->name('regions.index');
        Route::post('/', [RegionController::class, 'store'])->name('regions.store');
        Route::get('/{region}', [RegionController::class, 'show'])->name('regions.show');
    });
    
    // Admin-only Routes
    Route::middleware('can:admin')->group(function () {
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
            // Additional admin routes...
        });
    });

    Route::resource('crop-prices', CropPriceController::class);


Route::get('/predict', [CropPredictionController1::class, 'showForm'])->name('crop.form');
Route::post('/predict', [CropPredictionController1::class, 'predict'])->name('crop.predict');


Route::get('/market-trend', [DashboardController::class, 'marketTrend'])->name('market.trend');

Route::get('/prediction-results', [PredictionController::class, 'index'])->name('prediction-results.index');





Route::controller(PredictionController::class)->group(function () {
    Route::get('/prediction-results', 'index')->name('prediction-results.index');
    Route::get('/prediction-results/download-pdf', 'downloadPDF')->name('prediction-results.download-pdf');
    Route::get('/prediction-results/download-pdf-filtered', 'downloadFilteredPDF')->name('prediction-results.download-pdf-filtered');
});


// Route::get('admin/users', [RegisteredUserController::class, 'user'])->name('admin.users');

});

// Logout
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout')
    ->middleware('auth');

// API Documentation
Route::get('/api-docs', function () {
    return view('api-docs');
})->name('api.docs');


Route::view('/note','note');

Route::get('/debug-data', [PredictionController::class, 'debugData']);

// Add to your routes
Route::get('/create-test-predictions', [PredictionController::class, 'createTestPredictions']);


// Prediction routes

Route::get('/predictions/create', [PredictionController::class, 'create'])->name('predictions.create');
Route::post('/predictions', [PredictionController::class, 'store'])->name('predictions.store');
Route::get('/predictions/{id}', [PredictionController::class, 'show'])->name('predictions.show');

// Debug and testing routes
Route::get('/debug-data', [PredictionController::class, 'debugData']);
Route::get('/system-debug', [PredictionController::class, 'systemDebug']);
Route::get('/generate-sample-data', [PredictionController::class, 'generateSampleData']);
Route::get('/create-test-predictions', [PredictionController::class, 'createTestPredictions']);

// API routes for AJAX requests
Route::post('/predictions/batch', [PredictionController::class, 'batchPredict']);
Route::get('/predictions/accuracy', [PredictionController::class, 'accuracy']);







Route::get('/train-model/{cropId}', [CropPricePredictionController::class, 'trainModel'])->name('train.model');

Route::post('/predict-price/{cropId}', [CropPricePredictionController::class, 'predictAndSave'])->name('predict.price');













Route::get('/python-test', function() {
    $controller = new CropPredictionController1;
    $pythonPath = $controller->findPythonExecutable();
    
    $process = new Process([$pythonPath, '-c', "import sklearn; print('OK')"]);
    $process->run();
    
    return [
        'python_path' => $pythonPath,
        'sklearn_check' => $process->isSuccessful() ? 'OK' : 'Failed',
        'output' => $process->getOutput(),
        'error' => $process->getErrorOutput()
    ];
});







Route::middleware(['auth'])->group(function () {
    Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
    Route::post('/users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulkAction');
});