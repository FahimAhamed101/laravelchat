<?php
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MessengerController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
   // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::group(['middleware' => 'auth'], function() {
    Route::get('messenger', [MessengerController::class, 'index'])->name('home');
    Route::post('profile', [UserProfileController::class, 'update'])->name('profile.update');
    // search route
    Route::get('messenger/search', [MessengerController::class, 'search'])->name('messenger.search');
     // fetch user by id
     Route::get('messenger/id-info', [MessengerController::class, 'fetchIdInfo'])->name('messenger.id-info');
     Route::post('messenger/send-message', [MessengerController::class, 'sendMessage'])->name('messenger.send-message');
     // fetch message
     Route::post('messenger/make-seen', [MessengerController::class, 'makeSeen'])->name('messenger.make-seen');
        // fetch message
    Route::get('messenger/fetch-messages', [MessengerController::class, 'fetchMessages'])->name('messenger.fetch-messeges');
 // fetch contacts
 Route::get('messenger/fetch-contacts', [MessengerController::class, 'fetchContacts'])->name('messenger.fetch-contacts');
 // favorite routes
 Route::post('messenger/favorite', [MessengerController::class, 'favorite'])->name('messenger.favorite');
 Route::get('messenger/fetch-favorite', [MessengerController::class, 'fetchFavoritesList'])->name('messenger.fetch-favorite');

});


require __DIR__.'/auth.php';
