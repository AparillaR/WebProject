<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomModel;
use App\Models\Items;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
   public function list()
    {
        $data['getRecord'] = DB::table('users')->where('user_type', 1)->paginate(10);
        $data['header_title'] = "Admin List";
        return view('admin.admin.list', $data);
    }

    public function add()
    {
        $data['header_title'] = "Add New Admin";
        return view('admin.admin.add', $data);
    }

    public function insert(Request $request) 
    {
        request()->validate([
            'email' => 'required|email|unique:users'
            
        ]);

        $user = new \App\Models\User();
        $user->school_id = trim($request->school_id);
        $user->name = trim($request->name);
        $user->phone_number = trim($request->phone_number);
        $user->email = trim($request->email);
        $user->password = bcrypt($request->password);
        $user->user_type = 1;
        $user->save();
        
        return redirect('admin/admin/list')->with('success', "Admin successfully created");
    }

    public function delete(Request $request, $id) 
    {
        $user = \App\Models\User::find($id);
        if($user) {
            $user->delete();
            return redirect('admin/admin/list')->with('success', "Admin successfully deleted");
        } else {
            return redirect('admin/admin/list')->with('error', "Admin not found");
        }
    }   

    public function edit($id) {
        $data['getRecord'] = \App\Models\User::find($id);
        if(!empty($data['getRecord']))
             {
             $data['header_title'] = "Edit Admin";
             return view('admin.admin.edit',$data);
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

        $user = \App\Models\User::find($id);
        $user->school_id = trim($request->school_id);
        $user->name = trim($request->name);
        $user->phone_number = trim($request->phone_number);
        $user->email = trim($request->email);
        if(!empty($request->password))
        {
            $user->password = bcrypt($request->password);
        }
        $user->save();
        
        return redirect('admin/admin/list')->with('success', "Admin successfully updated");
    }

    
}