<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hash;
use Auth;
class AuthController extends Controller
{
   public function login()
    {
        // Check if user is already logged in
        if(!empty(Auth::check())) {
            if(Auth::user()->user_type == 1) {
                return redirect('admin/dashboard');
            }
            elseif(Auth::user()->user_type == 2) {
                return redirect('borrower/dashboard');
            }
            else {
                Auth::logout();
                return redirect()->back()->with('error', 'Invalid user type');
            }
        }
        
        return view('auth.login');
    }

   public function Authlogin(Request $request)
   {
    $remember = !empty($request->remember) ? true : false;
    if(Auth::attempt(['email' => $request->email, 'password' => $request->password], $remember))
    

   {
    if(Auth::user()->user_type == 1)
    {
        return redirect('admin/dashboard');
    }
    elseif(Auth::user()->user_type == 2)
    return redirect('borrower/dashboard');
   }
    else
    {
        return redirect()->back()->with('error', 'Please enter current email and password');
    }
   }
   public function logout()
   {
    Auth::logout();
    return redirect(url(''));
   }
}






