<?php
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::post('/v1/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    return response()->json([
        'token_type' => 'Bearer',
        'access_token' => $user->createToken('internal-hub-client')->plainTextToken,
    ]);
});


Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

/*

$user = App\Models\User::create([
    'name' => 'SuperAdmin',
    'email' => 'admin@payaccsys.com',
    'password' => bcrypt('&9GP(&Rm3r4>,]<}W?tHobv(j}Sw$,>/DNiOn<{dt8MZ5k]G5M'),
]);

// Create token and output the plain text secret
$token = $user->createToken('phoenix-absa-client')->plainTextToken;
echo $token;
// 7|kExUv2K5SblqWWVTQNd3QhV187IDkRdEl1BwJhYh54522c90

// Phoenix user
$user = App\Models\User::create([
    'name' => 'Phoenix',
    'email' => 'absa@payaccsys.com',
    'password' => bcrypt('8:rjFjt86`>2bf.n_F;to,j3}]£q+GPbFdjjd&z(Hdm}^~TVk{'),
]);

// Create token and output the plain text secret
$token = $user->createToken('phoenix-absa-client')->plainTextToken;
echo $token;
// 6|tSHlSvkuMbSyeCrBuwFZ43eqnUASDSvVi3aYq3izc9ff714a



*/
