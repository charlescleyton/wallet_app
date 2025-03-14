<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterAuthRequest;
use App\Http\Requests\LoginAuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Validator;

class AuthController extends Controller
{
    public function Register(RegisterAuthRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'token' => 'Bearer ' . $token
        ]);
    }

    public function login(LoginAuthRequest $request)
    {
        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Usuário não autorizado!'], 401);
        }

        $user = User::where('email', $request->email)->first();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'token' => 'Bearer ' . $token
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Obrigado por usar nosso aplicativo!']);
    }
}
