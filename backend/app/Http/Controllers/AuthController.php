<?php

namespace App\Http\Controllers;
use App\Jobs\SendEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{   
    public function register(Request $request){
        $userdata = $request->validate([
            'email' => ['required', 'email', 'max:50', 'unique:users,email'],
            'username' => ['required', 'unique:users,username', 'max:20', 'regex:/^[a-zA-Z][a-zA-Z0-9]*$/'],
            'password' => ['required', 'min:8', 'max:250'],
        ]);

        $user = new User();
        $user->email = $userdata['email'];
        $user->username = $userdata['username'];
        $user->password_hash = $userdata['password'];
        $user->save();

    }


    public function login(Request $request){
        $credentials = $request->validate([
            'email' => ['required'], 
            'password' => ['required']
        ]);
        
        if (!Auth::attempt($credentials)){
            return response()->json(['message' => 'Nepareizs e-pasts vai parole'], 401);
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
        return response()->json(['message' => 'Izrakstīšanās veiksmīga']);
    }

    public function sendVerificationCode(Request $request){
        $user = $request->user();

        if ($user->email_verified) {
            return response()->json(['message' => 'E-pasts jau ir apstiprināts'], 409);
        }

        $code = (string) random_int(100000, 999999);

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);

        SendEmail::dispatch(
            $user->email,
            $user->username,
            'GameSpace | E-pasta adreses apstiprināšana',
            view('emails.verification', [
                'username' => $user->username,
                'reason' => 'Saņēmām pieprasījumu apstiprināt šo e-pasta adresi GameSpace kontam.',
                'code' => $code,
            ])->render(),
        );

        return response()->json(['message' => 'Kods tiks nosūtīts uz e-pastu']);
    }

    public function verifyCode(Request $request){
        $validated = $request->validate(['code' => 'required|string|size:6']);
        $user = $request->user();

        if ($user->verification_code === null || !hash_equals($user->verification_code, $validated['code'])) {
            return response()->json(['message' => 'Nepareizs kods. Mēģini vēlreiz.'], 422);
        }

        if ($user->verification_code_expires_at->isPast()) {
            return response()->json(['message' => 'Kods vairs nav derīgs.'], 422);
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
        $exists = User::where('username', $request->username)->exists();
        return ["exists" => $exists];
    }

    public function getuser(Request $request){
        $request->validate(['id' => 'required|integer']);
        return User::select("username", "avatar_url", "bio", "is_private", "role")->findOrFail($request->id);
    }

}