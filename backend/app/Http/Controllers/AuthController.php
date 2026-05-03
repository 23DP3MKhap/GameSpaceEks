<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
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

    public function sendVerificationCode(Request $request){
    $code = rand(100000, 999999);
    $request->user()->update(['verification_code' => $code]);
    
    Mail::send('emails.verification', ['code' => $code], function (Message $message) use ($request) {
    $message->to($request->user()->email)->subject('E-pasta verifikācija | GameSpace');
    });
    
    }

    public function verifyCode(Request $request){
    $request->validate(['code' => 'required']);
    
    if ($request->user()->verification_code != $request->code) {
        return response()->json(['message' => 'wrong code'], 422);
    }
    
    $request->user()->update([
        'email_verified' => true,
        'verification_code' => null
    ]);
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