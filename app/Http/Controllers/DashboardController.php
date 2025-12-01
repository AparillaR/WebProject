<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
class DashboardController extends Controller
{
    public function dashboard()
    {
        $data['header_title'] = 'Dashboard';
        if(Auth::user()->user_type == 1)
        {
            return view('admin.dashboard', $data);
        }
        else if(Auth::user()->user_type == 2)
        {
            return view('borrower.dashboard', $data);
        }

        
    }

         public function index()
    {
        $adminCount = Admin::count();
        $borrowerCount = Borrower::count();
        $itemCount = Item::count();
        $transactionCount = Transaction::count();
        
        return view('dashboard', compact(
            'adminCount', 
            'borrowerCount', 
            'itemCount', 
            'transactionCount'
        ));
    }
}
 