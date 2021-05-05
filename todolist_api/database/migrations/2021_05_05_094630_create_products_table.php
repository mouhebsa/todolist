<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::group([
//     'prefix' => 'auth'
// ], function () {
//     Route::post('login', 'AuthController@login');
//     Route::post('signup', 'AuthController@signup');
  
//     Route::group([
//       'middleware' => 'auth:api'
//     ], function() {
//         Route::get('logout', 'AuthController@logout');
//         Route::get('user', 'AuthController@user');
//     });
// });

Route::middleware('api')->post('/signup', function (Request $request) {
    $request->validate([
        'name' => 'required|string',
        'email' => 'required|string|email|unique:users',
        'password' => 'required|string|confirmed'
    ]);
    $user = new User([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password)
    ]);
    $user->save();
    return response()->json([
        'message' => 'Successfully created user!'
    ], 201);
});

Route::middleware('auth:api')->get('/logout', function (Request $request) {
    
    return $request->user()->token()->revoke();
});

Route::middleware('api')->post('/signin', function (Request $request) {
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
        'remember_me' => 'boolean'
    ]);
    $credentials = request(['email', 'password']);
    if(!Auth::attempt($credentials))
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    $user = $request->user();
    $tokenResult = $user->createToken('Personal Access Token');
    $token = $tokenResult->token;
    if ($request->remember_me)
        $token->expires_at = Carbon::now()->addWeeks(1);
    $token->save();
    return response()->json([
        'access_token' => $tokenResult->accessToken,
        'token_type' => 'Bearer',
        'expires_at' => Carbon::parse(
            $tokenResult->token->expires_at
        )->toDateTimeString()
    ]);
});

Route::middleware('auth:api')->get('/products', function (Request $request) {
    
    return $Product = Product::get()->toJson(JSON_PRETTY_PRINT);
    return response($Product, 200);
});

Route::middleware('auth:api')->get('/product/{id}', function (Request $request) {
    $id = $request->route('id');
    if (Product::where('id', $id)->exists()) {
        $Product = Product::where('id', $id)->get()->toJson(JSON_PRETTY_PRINT);
        return response($Product, 200);
      } else {
        return response()->json([
          "message" => "todo not found"
        ], 404);
      }
});

Route::middleware('auth:api')->put('/product/{id}', function (Request $request) {
    $id = $request->route('id');
    if (Product::where('id', $id)->exists()) {
        $Product = Product::find($id);
        $Product->title = is_null($request->title) ? $Product->title : $request->title;
        $Product->description = is_null($request->description) ? $Product->description : $request->description;
        $Product->finished = is_null($request->finished) ? $Product->finished : $request->finished;
        $Product->save();

        return response()->json([
            "message" => "records updated successfully"
        ], 200);
        } else {
        return response()->json([
            "message" => "Product not found"
        ], 404);
        
    }
});

Route::middleware('auth:api')->delete('/product/{id}', function (Request $request) {
    $id = $request->route('id');
    if($request->user()->id == $id) {
        if(Product::where('id', $id)->exists()) {
            $Product = Product::find($id);
            $Product->delete();
    
            return response()->json([
              "message" => "records deleted"
            ], 202);
          } else {
            return response()->json([
              "message" => "Student not found"
            ], 404);
          };
    } else {
        return response()->json([
            "message" => "You don't have permession to delete"
        ], 500);
    }
});

Route::middleware('auth:api')->post('/product', function (Request $request) {
    $Product = new Product;
    if(is_null($request->title) ) {
        return response()->json([
            "message" => "Title is required"
          ], 500);
    }
    if(is_null($request->description) ) {
        return response()->json([
            "message" => "description is required"
          ], 500);
    }
    $Product->title = $request->title;
    $Product->description = $request->description;
    $Product->finished = false;
    $Product->user_id = $request->user()->id;
    $Product->save();

    return response()->json([
      "message" => "student record created"
    ], 201);
});

// Route::middleware('auth')->post('/signup', function (Request $request) {
//     console.log($request)    ;//return $request->user();
// });


