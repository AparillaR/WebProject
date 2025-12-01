<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomModel;
use Hash;


class RoomController extends Controller
{
    public function list() {
        $data['getRecord'] = RoomModel::getRoom();
        $data['header_title'] = "Room List";
        return view('admin.room.list', $data);
    }
    public function add() {
        $data['header_title'] = "Add Room";
        return view('admin.room.add', $data);
    }

public function store(Request $request)
{
    $validate = $request->validate([
        'rm_code' => 'required|string|max:50|unique:rooms,rm_code', // Change 'room' to 'rooms'
    ]);
    
    $room = new RoomModel();
    $room->rm_code = trim($validate['rm_code']);
    $room->rm_name = trim($request['rm_name']);
    $room->status = trim($request['status']); 
    $room->is_delete = 0;
    $room->save();

    return redirect('admin/room/list')->with('success', 'Room added successfully!');
}

        public function index()
        {
            // Temporary: Add test data
            $testRooms = [
                (object)['rm_code' => 'TEST001', 'rm_name' => 'Test Room 1'],
                (object)['rm_code' => 'TEST002', 'rm_name' => 'Test Room 2'],
                
            ];
            
            $rooms = RoomModel::all();
            
            if ($rooms->count() === 0) {
                $rooms = collect($testRooms);
            }
            
            return view('admin.room.list', compact('room'));

        }

         public function update($id, Request $request) 
    {
          $validate = $request->validate([
           'rm_code' => 'required|string|max:50|unique:room,rm_code,' .$id,
           
           
        ]);

        $room = RoomModel::getSingle($id);
        $room->rm_code = trim($validate->rm_code);
        $room->rm_name = trim($request->rm_name);
        $room->status = trim($request->status);
        $room->is_delete = 0; // Default to active status

       
        $room->save();
        
        return redirect('admin/room/list')->with('success', "Admin successfully updated");
    }

      public function edit($id) {
        $data['getRecord'] = RoomModel::getSingle($id);
        if(!empty($data['getRecord']))
             {
             $data['header_title'] = "Edit Admin";
             return view('admin.room.edit',$data);
        }
        else {
           abort(404);
        }
    }
   
     public function delete($id) 
    {
        $room = RoomModel::getSingle($id);
        $room->is_delete = 1;
        $room->save();

        return redirect('admin/room/list')->with('success', "Room successfully deleted");
    }

    public function create()
{
    // Get all active rooms
    $rooms = Room::where('status', 1)->get();
    
    // Get all active items with available quantity
    $items = Item::where('status', 1)
                ->where('quantity', '>', 0)
                ->get()
                ->map(function($item) {
                    $item->available_quantity = $item->quantity;
                    return $item;
                });
    
    // Get all borrowers (users with borrower role)
    $borrowers = User::where('user_type', 'borrower')
                     ->where('is_deleted', 0)
                     ->get();

    return view('your-view-name', compact('rooms', 'items', 'borrowers'));
}

// If you're processing a reservation, update that method too:
public function processReservation($id)
{
    $reservation = ReservationModel::with(['user', 'room', 'reservationItems.item'])
        ->findOrFail($id);
    
    // Get reserved items
    $selectedItems = [];
    foreach ($reservation->reservationItems as $reservationItem) {
        if ($reservationItem->item) {
            $selectedItems[] = [
                'id' => $reservationItem->item->id,
                'name' => $reservationItem->item->name,
                'quantity' => $reservationItem->quantity,
                'available' => $reservationItem->item->quantity,
            ];
        }
    }

    // Get all active rooms
    $rooms = Room::where('status', 1)->get();
    
    // Get all active items with available quantity
    $items = Item::where('status', 1)
                ->where('quantity', '>', 0)
                ->get()
                ->map(function($item) {
                    $item->available_quantity = $item->quantity;
                    return $item;
                });

    return view('your-view-name', compact('reservation', 'selectedItems', 'rooms', 'items'));
}
    }



