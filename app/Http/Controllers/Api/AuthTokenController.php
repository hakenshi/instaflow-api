<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthTokenController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'workspace_id' => $user->workspace_id,
                'meta_user_id' => $user->meta_user_id,
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->tokens()->delete();

        return response()->noContent();
    }
}
