<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{   
    public function register(Request $request){
        $userdata = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'username' => ['required', 'unique:users,name', 'max:10', 'regex: /^[a-zA-Z][\w]*$/'],
            'password' => ['required', 'min:8'],
        ]);

        $user = new User();
        $user->email = $userdata['email'];
        $user->name = $userdata['username'];
        $user->password = $userdata['password'];
        $user->save();

    }


    public function login(Request $request){
        $credentials = $request->validate([
            'email' => ['required'], 
            'password' => ['required']
        ]);
        
        if (!Auth::attempt($credentials)){
            return response()->json(['message' => 'wrong credentials'], 401);
        }
        
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }
    
    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'logged out']);
    }


    public function emailcheck(Request $request){
        $exists = User::where('email', $request->email)->exists();
        return ["exists" => $exists];
    }

    public function usernamecheck(Request $request){
        $exists = User::where('name', $request->username)->exists();
        return ["exists" => $exists];
    }


    public function getemail(Request $request){
        return User::where("id", $request->id)->value("email");
    }

    public function getusername(Request $request){
        return User::where("id", $request->id)->value("name");
    }

    public function getid(Request $request){
        return User::where("id", $request->id)->value("id");
    }

    public function getuser(Request $request){
        return User::where("id", $request->id)->select("name", "avatar", "bio", "isPrivate", "role")->first(); 
    }

}