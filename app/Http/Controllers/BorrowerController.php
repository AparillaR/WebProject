<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Hash;

class BorrowerController extends Controller
{
    public function list()
    {
        $data['getRecord'] = User::getBorrower();
        $data['header_title'] = "Borrower List";
        return view('admin.borrower.list', $data);
    }
    public function add()
    {
        $data['header_title'] = "Add New Borrower";
        return view('admin.borrower.add', $data);
    }

    public function insert(Request $request) 
    {
        request()->validate([
            'email' => 'required|email|unique:users'
            
        ]);

        $user = new User();
        $user->school_id = trim($request->school_id);
        $user->name = trim($request->name);
        $user->phone_number = trim($request->phone_number);
        $user->email = trim($request->email);
        $user->password = Hash::make($request->password);
        $user->user_type = 2;
        $user->save();
        
        return redirect('admin/borrower/list')->with('success', "Borrower successfully created");
    }

    public function edit($id) {
        $data['getRecord'] = User::getSingle($id);
        if(!empty($data['getRecord']))
             {
             $data['header_title'] = "Edit Borrower";
             return view('admin.borrower.edit',$data);
        }
        else {
           abort(404);
        }
    }

    public function update($id, Request $request) 
    {
         request()->validate([
            'email' => 'required|email|unique:users,email,' .$id
        ]);

        $user = User::getSingle($id);
         $user->school_id = trim($request->school_id);
        $user->name = trim($request->name);
        $user->phone_number = trim($request->phone_number);
        $user->email = trim($request->email);
        if(!empty($request->password))
        {
            $user->password = Hash::make($request->password);
        }
       
        $user->save();

        return redirect('admin/borrower/list')->with('success', "Borrower successfully updated");
    }

    public function delete($id) 
    {
        $user = User::getSingle($id);
        $user->is_delete = 1;
        $user->save();

        return redirect('admin/borrower/list')->with('success', "Borrower successfully deleted");
    }

      
    public function checkOtp(Request $request)
    {
        $userId = $request->get('user_id', Auth::id());
        
        // Check if admin has generated real OTP (not 'REQUEST')
        $otp = OtpCode::where('user_id', $userId)
                     ->where('used', false)
                     ->where('code', '!=', 'REQUEST')
                     ->where('expires_at', '>', now())
                     ->first();
        
        return response()->json([
            'has_otp' => !is_null($otp),
            'otp_code' => $otp ? $otp->code : null
        ]);
    }
    
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6'
        ]);
        
        try {
            $otp = OtpCode::where('code', $request->otp_code)
                         ->where('user_id', $request->user_id)
                         ->where('used', false)
                         ->where('expires_at', '>', now())
                         ->first();
            
            if ($otp) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP verified successfully'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error verifying OTP: ' . $e->getMessage()
            ], 500);
        }
    }
    
   

        public function requestOtp(Request $request)
{
    try {
        $user = Auth::user();
        
        // Initialize session array if not exists
        $otpRequests = Session::get('otp_requests', []);
        
        // Add OTP request to session
        $otpRequests[$user->id] = [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'requested_at' => now()->timestamp,
            'status' => 'pending'
        ];
        
        Session::put('otp_requests', $otpRequests);
        
        \Log::info('OTP Request Created for User: ' . $user->id);
        
        return response()->json([
            'success' => true,
            'message' => 'OTP request sent to administrator. Please wait for OTP.'
        ]);
        
    } catch (\Exception $e) {
        \Log::error('OTP Request Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error requesting OTP. Please try again.'
        ], 500);
    }
}
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Current password is required',
            'new_password.required' => 'New password is required',
            'new_password.min' => 'New password must be at least 8 characters',
            'new_password.confirmed' => 'New password confirmation does not match',
        ]);
        
        try {
            $user = Auth::user();
            
            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return back()
                    ->withInput()
                    ->with('error', 'Current password is incorrect.');
            }
            
            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();
            
            return back()->with('success', 'Password changed successfully!');
            
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error changing password: ' . $e->getMessage());
        }
    }


}
