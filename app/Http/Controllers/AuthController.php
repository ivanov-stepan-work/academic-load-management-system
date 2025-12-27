<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('login', 'password');

        if (Auth::attempt($credentials)) {
            // Получаем текущего аутентифицированного пользователя
            $user = Auth::user();

            // Загружаем роль пользователя
            $role = $user->roles()->first(); // Предполагаем, что у пользователя только одна роль

            // Собираем данные пользователя и его роли в один объект
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role, // Возвращаем имя роли или null, если роль не найдена
            ];

            // Возвращаем пользовательские данные в виде JSON
            return response()->json(['user' => $userData], 200);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
