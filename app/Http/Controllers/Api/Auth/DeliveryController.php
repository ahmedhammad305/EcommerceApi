<?php


namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
class DeliveryController extends Controller
{
     public function Register(Request $request)
    {
        // validate request
        $data = $request->validate([
            'name' => 'required|string|max:256',
            'email' => 'required|string|email|max:256|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create([
            'name' => $data['name'],
            'email' =>  $data['email'],
            'password' =>  $data['password'],
            'type' => 'delivery',
        ]);
        
        $user->assignRole('delivery');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }
    public function login(Request $request)
{
    $data = $request->validate([
        'email' => 'required|string|email|max:256',
        'password' => 'required|string|min:8',
    ]);

    $user = User::where('email', $data['email'])->first();

    if (!$user || !Hash::check($data['password'], $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'User login successfully',
        'user' => $user,
        'token' => $token
    ], 200);
}


    //logout

    public function logout(Request $requset) {

        $requset->user()->currentAccessToken()->delete();
                return response()->json(['message'=>'user deleted successfully',201]);

    }
}
