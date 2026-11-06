<?php

namespace App\Http\Controllers;
use App\Jobs\SendEmail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

        $code = EmailVerification::issue($user, 'registration');

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

        if (!EmailVerification::consume($user, 'registration', $validated['code'])) {
            return response()->json(['message' => 'Nepareizs vai novecojis kods. Mēģini vēlreiz.'], 422);
        }

        $user->update(['email_verified' => true]);
    }

    public function requestEmailChange(Request $request){
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:50', 'unique:users,email'],
            'current_password' => ['required', 'string'],
        ]);
        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => 'Pašreizējā parole nav pareiza',
            ]);
        }

        Cache::put('email_change:' . $user->id, $validated['email'], now()->addMinutes(15));
        $code = EmailVerification::issue($user, 'email_change');

        SendEmail::dispatch(
            $validated['email'],
            $user->username,
            'GameSpace | E-pasta adreses maiņa',
            view('emails.verification', [
                'username' => $user->username,
                'reason' => 'Saņēmām pieprasījumu mainīt GameSpace konta e-pasta adresi uz šo adresi.',
                'code' => $code,
            ])->render(),
        );

        return response()->json(['message' => 'Kods tiks nosūtīts uz jauno e-pastu']);
    }

    public function confirmEmailChange(Request $request){
        $validated = $request->validate(['code' => 'required|string|size:6']);
        $user = $request->user();
        $email = Cache::get('email_change:' . $user->id);

        if (!$email || !EmailVerification::consume($user, 'email_change', $validated['code'])) {
            return response()->json(['message' => 'Nepareizs vai novecojis kods. Mēģini vēlreiz.'], 422);
        }

        Cache::forget('email_change:' . $user->id);

        if (User::where('email', $email)->exists()) {
            return response()->json(['message' => 'E-pasts jau ir reģistrēts'], 422);
        }

        $user->update(['email' => $email, 'email_verified' => true]);

        return $user;
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