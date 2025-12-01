<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionsModel;
use App\Models\ReservationModel;
use App\Models\User;
use App\Models\Items;


class TransactionsController extends Controller
{
    public function list() 
    {
        $data['getRecord'] = TransactionsModel::getTransactions();
        $data['header_title'] = "Borrowed List";
        return view('admin.transactions.list', $data);
    }

     public function add() 
    {
       
        $data['header_title'] = "Add Borrower List";
        return view('admin.transactions.add', $data);
    }

    public function insert(Request $request) 
    {
        request()->validate([
            'email' => 'required|email|unique:users'
        ]);

        $user = new User();
        $user->name = trim($request->name);
        $user->email = trim($request->email);
        $user->password = Hash::make($request->password);
        $user->user_type = 2;
        $user->save();
        
        return redirect('admin/transactions/list')->with('success', "Borrower successfully created");
    }
    public function borrow()
    {
        $borrowers = User::getBorrower();
        $items = Items::getItems();
        return view('admin.transactions.borrow', compact('borrowers', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:items,id',
            'borrowed_date' => 'required|date',
            'due_date' => 'required|date'
        ]);

        // Check if all items are available
        foreach ($request->item_ids as $item_id) {
            $item = Items::find($item_id);
            if (!$item->isAvailable()) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Item '{$item->name}' is not available for borrowing. Available quantity: {$item->available_quantity}");
            }
        }

        // Create a transaction for each selected item
        foreach ($request->item_ids as $item_id) {
            $transaction = new TransactionsModel();
            $transaction->user_id = $request->user_id;
            $transaction->item_id = $item_id;
            $transaction->borrowed_date = $request->borrowed_date;
            $transaction->due_date = $request->due_date;
            $transaction->status = 0;
            $transaction->save();

            // Note: We don't deduct quantity from items table anymore
            // We calculate available quantity based on borrowed transactions
        }

        // If this transaction was created from a reservation, update the reservation status
        if ($request->has('reservation_id')) {
            $reservation = ReservationModel::find($request->reservation_id);
            if ($reservation) {
                $reservation->update(['status' => 2]); // 2 = borrowed
            }
        }

        return redirect()->route('admin.transactions.list')->with('success', 'Items borrowed successfully');
    }

   
   public function returnForm(Request $request)
    {
        // Get all borrowers who have borrowed items
        $borrowers = User::whereHas('transactions', function($query) {
            $query->where('status', 'borrowed');
        })->get();

        $selectedBorrower = null;
        $borrowedItems = collect();

        // If a borrower is selected, get their borrowed items
        if ($request->has('user_id') && $request->user_id) {
            $selectedBorrower = User::find($request->user_id);
            if ($selectedBorrower) {
                $borrowedItems = TransactionsModel::with('item')
                    ->where('user_id', $request->user_id)
                    ->where('status', 'borrowed')
                    ->get();
            }
        }

        return view('admin.transactions.return', compact('borrowers', 'selectedBorrower', 'borrowedItems'));
    }

    public function processReturn(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:transactions,id',
            'returned_date' => 'required|date',
        ]);

        // Update the status of selected transactions to 'returned'
        TransactionsModel::whereIn('id', $request->transaction_ids)
            ->update([
                'status' => 1,
                'returned_date' => $request->returned_date
            ]);

        return redirect()->route('admin.transactions.list')
            ->with('success', 'Items returned successfully!');
    }

      public function getBorrowedItems(Request $request)
    {
        $userId = $request->get('user_id'); 
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID is required',
                'items' => []
            ]);
      
        }
    }



    // Student Side
    
}