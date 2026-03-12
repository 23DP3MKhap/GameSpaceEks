<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{   
    public function register(Request $request){
        $email = $request->email;
        $password = $request->password;
        $username = $request->username;

        $user = new User();
        $user->email = $request->email;
        $user->name = $request->username;
        $user->password = $request->password;
        $user->save();
    }

    public function login(Request $request){
        $credentials = $request->validate(['email' => ['required'], 'password' => ['required']]);
        
        
        if (! Auth::attempt($credentials)){
            return response()->json(["message" => "wrong credentials"]);
        }

        $request->session()->regenerate();
        return response()->json(["message" => "true"]);
        }

    public function emailcheck(Request $request){
        $exists = User::where('email', $request->email)->exists();
        return ["exists" => $exists];
    }

    public function usernamecheck(Request $request){
        $exists = User::where('name', $request->username)->exists();
        return ["exists" => $exists];
    }
}
