<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClientSubaccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Trim email to avoid spacing issues
        $email = trim($request->email);
        $password = $request->password;

        if (!Auth::guard('web')->attempt(['email' => $email, 'password' => $password])) {
            return back()->with('error', 'Invalid email or password.')->withInput();
        }

        $request->session()->regenerate();

        $user = Auth::user();
        
        // Redirect based on user role
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
                
            case 'client':
                return redirect()->route('client.dashboard');
                
            case 'subaccount':
                $subaccount = ClientSubaccount::where('user_id', $user->id)->first();
                if ($subaccount) {
                    return redirect()->route('client.account.subaccounts.show', $subaccount);
                }
                return redirect()->route('client.dashboard');
                
            default:
                // Logout if role is not recognized
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->with('error', 'Your account does not have access permissions.')->withInput();
        }
    }
}
