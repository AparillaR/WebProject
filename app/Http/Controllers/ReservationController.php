<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReservationModel;
use App\Models\Items;
use App\Models\RoomModel;
use App\Models\User;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function list(Request $request)
    {
        // First, update overdue reservations
        $this->updateOverdueReservations();
        
        // Get search parameters
        $search = $request->only(['reservation_date', 'room_id']);
        
        // Query for pending reservations (status 0)
        $pendingQuery = ReservationModel::with(['user', 'room', 'reservationItems.item'])
                            ->where('status', 0);
        
        // Query for accepted reservations (status 1)  
        $acceptedQuery = ReservationModel::with(['user', 'room', 'reservationItems.item'])
                            ->where('status', 1);

        // Apply search filters if exists
        if (!empty($search['reservation_date'])) {
            $pendingQuery->whereDate('reservation_date', $search['reservation_date']);
            $acceptedQuery->whereDate('reservation_date', $search['reservation_date']);
        }

        if (!empty($search['room_id'])) {
            $pendingQuery->where('room_id', $search['room_id']);
            $acceptedQuery->where('room_id', $search['room_id']);
        }

        $data['pendingReservations'] = $pendingQuery->orderBy('created_at', 'desc')->get();
        $data['acceptedReservations'] = $acceptedQuery->orderBy('created_at', 'desc')->get();
        $data['rooms'] = RoomModel::where('status', 0)->get();
        $data['header_title'] = "Reservation List";
        
        return view('admin.reservations.list', $data);
    }

    public function add()
    {
        $data['header_title'] = "Add New Reservation";
        return view('admin.reservation.add', $data);
    }
    
    public function store(Request $request)
    {
        $data['header_title'] = "Store Reservation";
        return view('admin.reservation.store', $data);
    }
    public function insert(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'reservation_date' => 'required|date|after_or_equal:today',
        ]);

        // Calculate time_limit (20 minutes after reservation_date)
        $reservationDate = $request->reservation_date;
        $timeLimit = date('Y-m-d H:i:s', strtotime($reservationDate . ' +20 minutes'));

        ReservationModel::create([
            'user_id' => $request->user_id,
            'room_id' => $request->room_id,
            'reservation_date' => $reservationDate,
            'time_limit' => $timeLimit,
            'status' => 0, // pending
        ]);

        return redirect('admin/reservations/list')->with('success', "Reservation successfully created");
    }
    public function edit($id)
    {
        $reservation = ReservationModel::with(['user', 'room', 'reservationItems.item'])->find($id);
        if (!$reservation) {
            return redirect()->back()->with('error', 'Reservation not found');
        }
        
        $data['reservation'] = $reservation; // FIXED: Changed from 'reservations' to 'reservation'
        $data['header_title'] = "Edit Reservation";
        return view('admin.reservation.edit', $data);
    }

    public function delete($id)
    {
        $reservation = ReservationModel::find($id);
        if ($reservation) {
            // Also delete related reservation items
            \App\Models\ReservationItem::where('reservation_id', $id)->delete();
            $reservation->delete();
            return redirect()->back()->with('success', 'Reservation deleted successfully');
        }
        
        return redirect()->back()->with('error', 'Reservation not found');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:1,4', // 1 = approved, 4 = cancelled
            'remarks' => 'nullable|string'
        ]);

        $reservation = ReservationModel::find($id);
        if (!$reservation) {
            return redirect()->back()->with('error', 'Reservation not found');
        }

        $reservation->update([
            'status' => $request->status,
            'remarks' => $request->remarks
        ]);

        $statusText = $request->status == 1 ? 'approved' : 'cancelled';
        return redirect()->back()->with('success', "Reservation {$statusText} successfully!");
    }

    private function updateOverdueReservations()
    {
        $now = Carbon::now();
        
        // Update pending reservations that have passed their time_limit to cancelled (status 4)
        $overdueReservations = ReservationModel::where('status', 0) // pending
            ->where('time_limit', '<', $now)
            ->update(['status' => 4, 'remarks' => 'Auto-cancelled: Time limit exceeded']); // cancelled
        
        if ($overdueReservations > 0) {
            \Log::info("Auto-cancelled {$overdueReservations} overdue reservations");
        }
    }

    public function createTransactionFromReservation($id)
    {
        $reservation = ReservationModel::with(['user', 'reservationItems.item'])->findOrFail($id);
        
        // Check if reservation is approved and not expired
        if ($reservation->status != 1) {
            return redirect()->route('admin.reservation.list')
                ->with('error', 'Only approved reservations can be processed for borrowing.');
        }
        
        if ($reservation->time_limit && strtotime($reservation->time_limit) < time()) {
            return redirect()->route('admin.reservations.list')
                ->with('error', 'This reservation has expired and cannot be processed.');
        }

        // Get all borrowers (users)
        $borrowers = User::where('user_type', 'borrower')->get();
        
        // Get all available items
        $items = Items::where('quantity', '>', 0)->get();

        // Prepare selected items data for JavaScript
        $selectedItems = [];
        foreach ($reservation->reservationItems as $reservationItem) {
            if ($reservationItem->item) {
                $selectedItems[] = [
                    'id' => $reservationItem->item->id,
                    'name' => $reservationItem->item->name,
                    'quantity' => $reservationItem->quantity,
                    'available' => $reservationItem->item->quantity
                ];
            }
        }

        return view('admin.transactions.borrow', compact(
            'borrowers', 
            'items', 
            'reservation',
            'selectedItems'
        ));
    }

    public function markAsBorrowed($id)
    {
        $reservation = ReservationModel::findOrFail($id);
        $reservation->update(['status' => 2]); // 2 = borrowed
        
        return response()->json(['success' => true]);
    }
    

}