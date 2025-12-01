<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionsModel;
use App\Models\User;
use App\Models\Items;

class BorrowerTransactionsController extends Controller
{
    public function list(Request $request) 
    {
        $search = [];
        
        if ($request->has('name') || $request->has('date')) {
            $search = [
                'name' => $request->get('name'),
                'date' => $request->get('date')
            ];
            
            $data['getRecord'] = TransactionsModel::getBorrowerTransactionsSearch($search);
        } else {
            $data['getRecord'] = TransactionsModel::getBorrowerTransactions();
        }

        $data['header_title'] = "My Borrowed Items";
        return view('borrower.transactions.list', $data);
    }
    public function returnItem(Request $request, $id)
{
    $transaction = TransactionsModel::where('id', $id)
        ->where('user_id', Auth::id()) // Ensure user can only return their own items
        ->first();

    if (!$transaction) {
        return redirect()->back()->with('error', 'Transaction not found or you do not have permission to return this item.');
    }

    $transaction->update([
        'status' => 'returned',
        'returned_at' => $request->return_date
    ]);

    return redirect()->route('borrower.transactions.list')->with('success', 'Item returned successfully!');
}

   public function borrow()
{
    $borrowers = User::getBorrower();
    $items = Items::getItems();
    return view('borrower.transactions.borrow', compact('borrowers', 'items'));
}

public function returnForm()
{
    $borrowers = User::getBorrower();
    
    // If user_id is selected, get their borrowed items
    $borrowedItems = [];
    $selectedBorrower = null;
    
    if (request('user_id')) {
        $selectedBorrower = User::find(request('user_id'));
        $borrowedItems = TransactionsModel::where('user_id', request('user_id'))
            ->whereNull('returned_date')
            ->with('item')
            ->get();
    }
    
    return view('borrower.transactions.return', compact('borrowers', 'borrowedItems', 'selectedBorrower'));
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

        return redirect()->route('borrower.transactions.list')->with('success', 'Items borrowed successfully');
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

        return redirect()->route('borrower.transactions.list')
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
    public function index()
    {
        return view('borrower.transactions_list');
    }

    /**
     * Show borrow item form
     */
    public function createBorrow()
    {
        return view('borrower.borrow_item');
    }

    /**
     * Show return item form
     */
    public function createReturn()
    {
        return view('borrower.return_item');
    }
   

}
