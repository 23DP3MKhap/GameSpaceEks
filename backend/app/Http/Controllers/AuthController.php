<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;

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
        $user = $request->user();

        if ($user->email_verified) {
            return response()->json(['message' => 'already verified'], 409);
        }

        $code = (string) random_int(100000, 999999);

        try {
            Http::timeout(10)->withHeaders([
                'api-key' => config('services.brevo.key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => 'GameSpace',
                    'email' => config('mail.from.address')
                ],
                'to' => [
                    ['email' => $user->email]
                ],
                'subject' => 'Verifikācijas kods - GameSpace',
                'htmlContent' => view('emails.verification', ['code' => $code])->render(),
            ])->throw();
        } catch (ConnectionException | RequestException $e) {
            report($e);
            return response()->json(['message' => 'send failed'], 502);
        }

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);

        return response()->json(['message' => 'sent']);
    }

    public function verifyCode(Request $request){
        $validated = $request->validate(['code' => 'required|string|size:6']);
        $user = $request->user();

        if ($user->verification_code === null || !hash_equals($user->verification_code, $validated['code'])) {
            return response()->json(['message' => 'wrong code'], 422);
        }

        if ($user->verification_code_expires_at === null || $user->verification_code_expires_at->isPast()) {
            return response()->json(['message' => 'code expired'], 422);
        }

        $user->update([
            'email_verified' => true,
            'verification_code' => null,
            'verification_code_expires_at' => null,
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