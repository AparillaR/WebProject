<?php

namespace App\Http\Controllers;
use App\Models\Items;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Borrowing;

class ItemsController extends Controller
{
   public function list(Request $request)
{
    $query = Items::query();
    
    // Existing filters
    if (!empty($request->name)) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }
    
    if (!empty($request->model)) {
        $query->where('model', 'like', '%' . $request->model . '%');
    }
    
    if (!empty($request->status)) {
        $query->where('status', $request->status);
    }
    
    // Add category filter - FIXED
    if (!empty($request->category)) {
        $query->where('category', $request->category);
    }
    
    if (!empty($request->date)) {
        $query->whereDate('created_at', $request->date);
    }
    
    $getRecord = $query->orderBy('id', 'desc')->paginate(20);
    
    $data['getRecord'] = $getRecord;
    $data['header_title'] = "Items List";
    return view('admin.items.list', $data);
}

    public function add(): View
    {
        $data['header_title'] = "Add New Items";
        return view('admin.items.add', $data);
        
        
    }
    

    public function insert(Request $request) 
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string',
            'model' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'status' => 'required|in:available,out_of_stock',
            'category' => 'required|string|max:255',
        ]);

        try {
            $item = new Items();
            $item->name = trim($request->name);

            // Handle image upload
            if ($request->hasFile('image')) {
                $ext = $request->file('image')->getClientOriginalExtension();
                $randomStr = date('Ymd') . Str::random(5);
                $filename = 'IMG_' . strtolower($randomStr) . '.' . $ext;
                $request->file('image')->move(public_path('upload/images/'), $filename);
                $item->image = $filename;
            }

            $item->description = trim($request->description);
            $item->model = trim($request->model);
            $item->quantity = $request->quantity;
            $item->status = $request->status;   
            $item->category = $request->category;
            $item->save();

            // 🔑 Generate and save QR code right after insert

            return redirect('admin/items/list')->with('success', "Item successfully created");

        } catch (\Exception $e) {
            \Log::error('Error saving item: ' . $e->getMessage());
            return back()->with('error', 'Error saving item: ' . $e->getMessage());
        }
    }

    // In your ItemsController
    public function generateQrCode($id)
    {
        $item = Items::findOrFail($id);
        
        try {
            $qrPath = $item->generateQrCode();
            
            return redirect()->back()->with('success', 'QR Code generated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate QR Code: ' . $e->getMessage());
        }
    }

     // Download QR Code
    public function downloadQrCode($id)
    {
        $item = Items::findOrFail($id);
        
        if (!$item->hasQrCode()) {
            return redirect()->back()->with('error', 'QR Code not found. Please generate it first.');
        }

        $filePath = storage_path('app/public/' . $item->qr_code);
        $fileName = 'item_' . $item->id . '_qr_code.png';

        return response()->download($filePath, $fileName);
    }   

    public function showFromQR($id)
    {
        $item = Items::findOrFail($id);

        return view('items.scan-view', compact('item'));
    }

    public function borrowItem(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        
        $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $item->available_quantity,
            'notes' => 'nullable|string|max:500'
        ]);

        if ($item->borrowItem($request->quantity, auth()->id())) {
            // Add notes to the borrowing record
            $borrowing = $item->borrowings()->latest()->first();
            $borrowing->update(['notes' => $request->notes]);

            return redirect()->route('items.borrow', $id)
                ->with('success', 'Item borrowed successfully!');
        }

        return redirect()->back()->with('error', 'Not enough items available!');
    }

    public function showReturnForm($id)
    {
        $item = Item::findOrFail($id);
        $currentBorrowings = $item->getCurrentBorrowings();
        
        return view('items.return', compact('item', 'currentBorrowings'));
    }

    public function returnItem(Request $request, $id)
    {
        $item = Items::findOrFail($id);
        
        $request->validate([
            'borrowing_id' => 'required|exists:borrowings,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($item->returnItem($request->quantity, $request->borrowing_id)) {
            return redirect()->route('items.return', $id)
                ->with('success', 'Item returned successfully!');
        }

        return redirect()->back()->with('error', 'Failed to return item!');
    }

    public function edit($id) 
    {
        $data['getRecord'] = Items::getSingle($id);
        if(!empty($data['getRecord']))
                {
                $data['header_title'] = "Edit Admin";
                return view('admin.items.edit',$data);
        }
        else {
            abort(404);
        }
    }

    public function update($id, Request $request) 
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string',
            'model' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'status' => 'required|in:available,out_of_stock',
            'category' => 'required|string|max:255',
        ]);

        try {
            $item = Items::getSingle($id);
            $item->name = trim($request->name);
            
            if(!empty($request->file('image')))
            {
                if(!empty($item->getImage()))
                {
                    unlink('upload/images/' . $item->image);
                }
                
                $ext = $request->file('image')->getClientOriginalExtension();
                $file = $request->file('image');
                $randomStr = date('Ymd') . Str::random(5);
                $filename = 'IMG_' . strtolower($randomStr). '.'.$ext;
                $file->move(public_path('upload/images/'), $filename);
                $item->image = $filename;
            }
            
            $item->description = trim($request->description);
            $item->model = trim($request->model);
            $item->quantity = trim($request->quantity);
            $item->status = trim($request->status);
            
            $item->save();
        
            return redirect()->route('admin.items.list')->with('success', "Items successfully updated");
            
        } catch (\Exception $e) {
            \Log::error('Error saving item: ' . $e->getMessage());
            return back()->with('error', 'Error saving item: ' . $e->getMessage());
        }
    }

    public function delete($id) 
    {
        $item = Items::getSingle($id)->delete();
        
        return redirect('admin/items/list')->with('success', "Item Successfully Deleted");
    }
}
