<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\UserResource;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
public function register(Request $request)
{
    try {
        // Validasi input
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|unique:users',
            'password' => 'required|string'
        ]);

        // Simpan user
        $user = User::create([
            'name'     => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role'     => 0
        ]);

        // Generate token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status'  => true,
            'message' => 'User registered successfully',
            'data'    => [
                'user'  => new UserResource($user),
                'token' => $token
            ]
        ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Jika validasi gagal
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Error umum (DB error, JWT error, dll)
            return response()->json([
                'status'  => false,
                'message' => 'Registration failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username'    => 'required|string',
            'password' => 'required'
        ]);

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Could not create token'
            ], 500);
        }

        // ttl dalam menit → convert ke detik
        $expiresInSeconds = JWTAuth::factory()->getTTL() * 120;

        return response()->json([
            'status'  => true,
            'message' => 'Login success',
            'data'    => [
                'user'  => new UserResource(JWTAuth::user()),
                'token' => $token,
                'token_type'  => 'bearer',
                'expires_in'  => $expiresInSeconds,
                'expires_at'  => now()->addSeconds($expiresInSeconds)->toDateTimeString(), // waktu lokal
                'expires_at_utc' => now()->addSeconds($expiresInSeconds)->toDateTimeString(),
            ]
        ]);
    }

    public function me()
    {
        return response()->json([
            'status' => true,
            'data'   => new UserResource(JWTAuth::user())
        ]);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60
        ]);
    }

    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return $this->respondWithToken($newToken);
        } catch (JWTException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Token refresh failed'
            ], 500);
        }
    }

    public function logout()
    {
        try {
            JWTAuth::parseToken()->invalidate();

            return response()->json([
                'status'  => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to logout, token invalid'
            ], 500);
        }
    }
}