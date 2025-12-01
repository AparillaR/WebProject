<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReservationModel;
use App\Models\RoomModel;
use App\Models\Items;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ReservationItem;

class BorrowerReservationController extends Controller
{
    public function list()
    {
        // First, update overdue reservations (both pending AND approved)
        $this->updateOverdueReservations();
        
        // Then get the reservations with relationships
        $data['getRecord'] = ReservationModel::where('user_id', auth()->id())
            ->with(['room', 'reservationItems.item'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('borrower.reservation.list', $data);
    }

    public function add()
    {
        // Get active rooms from database
        $rooms = RoomModel::where('status', 0)->get();
        
        // Get available items
        $items = Items::where('quantity', '>', 0)->get();

        return view('borrower.reservation.add', compact('rooms', 'items'));
    }

    public function store(Request $request)
    {
        \Log::info('Form data:', $request->all());

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'items' => 'required|array|min:1',
            'items.*' => 'required|exists:items,id',
            'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|integer|min:1',
            'reservation_date' => 'required|date|after_or_equal:today',
        ]);

        try {
            DB::beginTransaction();

            // Calculate time_limit (20 minutes after reservation_date)
            $reservationDate = $request->reservation_date;
            $timeLimit = date('Y-m-d H:i:s', strtotime($reservationDate . ' +20 minutes'));

            // Create reservation
            $reservation = ReservationModel::create([
                'user_id' => auth()->id(),
                'room_id' => $request->room_id,
                'reservation_date' => $reservationDate,
                'time_limit' => $timeLimit,
                'status' => 0, // pending
            ]);

            // Attach items to reservation_items table
            foreach ($request->items as $index => $itemId) {
                if (!empty($itemId)) {
                    $quantity = $request->quantities[$index] ?? 1;
                    
                    ReservationItem::create([
                        'reservation_id' => $reservation->id,
                        'item_id' => $itemId,
                        'quantity' => $quantity,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('borrower.reservation.list')
                ->with('success', 'Reservation created successfully! Time limit: 20 minutes from reservation time.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error creating reservation: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update overdue reservations automatically
     * This will cancel BOTH pending AND approved reservations that exceed time_limit
     */
    private function updateOverdueReservations()
    {
        $now = Carbon::now();
        
        // Update pending AND approved reservations that have passed their time_limit to cancelled (status 4)
        // Only cancel if status is not already borrowed (2) or completed (3)
        $overdueReservations = ReservationModel::whereIn('status', [0, 1]) // pending (0) AND approved (1)
            ->where('time_limit', '<', $now)
            ->whereNotIn('status', [2, 3]) // don't cancel if already borrowed or completed
            ->update([
                'status' => 4, // cancelled
                'remarks' => DB::raw('COALESCE(CONCAT(remarks, " - Auto-cancelled: Time limit exceeded"), "Auto-cancelled: Time limit exceeded")')
            ]);
        
        if ($overdueReservations > 0) {
            \Log::info("Auto-cancelled {$overdueReservations} overdue reservations (both pending and approved)");
        }
    }

    public function edit($id)
    {
        $reservation = ReservationModel::where('user_id', auth()->id())->findOrFail($id);
        
        // Check if reservation can be edited (only pending and not expired)
        if ($reservation->status != 0 || ($reservation->time_limit && strtotime($reservation->time_limit) < time())) {
            return redirect()->route('borrower.reservation.list')
                ->with('error', 'This reservation cannot be edited.');
        }

        $rooms = RoomModel::where('status', 0)->get();
        $items = Items::where('quantity', '>', 0)->get();

        return view('borrower.reservation.edit', compact('reservation', 'rooms', 'items'));
    }

    public function update(Request $request, $id)
    {
        $reservation = ReservationModel::where('user_id', auth()->id())->findOrFail($id);
        
        // Check if reservation can be updated
        if ($reservation->status != 0 || ($reservation->time_limit && strtotime($reservation->time_limit) < time())) {
            return redirect()->route('borrower.reservation.list')
                ->with('error', 'This reservation cannot be updated.');
        }

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'items' => 'required|array|min:1',
            'items.*' => 'required|exists:items,id',
            'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|integer|min:1',
            'reservation_date' => 'required|date|after_or_equal:today',
        ]);

        try {
            DB::beginTransaction();

            // Recalculate time_limit
            $reservationDate = $request->reservation_date;
            $timeLimit = date('Y-m-d H:i:s', strtotime($reservationDate . ' +20 minutes'));

            // Update reservation
            $reservation->update([
                'room_id' => $request->room_id,
                'reservation_date' => $reservationDate,
                'time_limit' => $timeLimit,
            ]);

            // Delete existing reservation items
            ReservationItem::where('reservation_id', $reservation->id)->delete();

            // Add new reservation items
            foreach ($request->items as $index => $itemId) {
                if (!empty($itemId)) {
                    $quantity = $request->quantities[$index] ?? 1;
                    
                    ReservationItem::create([
                        'reservation_id' => $reservation->id,
                        'item_id' => $itemId,
                        'quantity' => $quantity,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('borrower.reservation.list')
                ->with('success', 'Reservation updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error updating reservation: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function delete($id)
    {
        $reservation = ReservationModel::where('user_id', auth()->id())->findOrFail($id);
        
        // Check if reservation can be deleted (only pending and not expired)
        if ($reservation->status != 0 || ($reservation->time_limit && strtotime($reservation->time_limit) < time())) {
            return redirect()->route('borrower.reservation.list')
                ->with('error', 'This reservation cannot be deleted.');
        }

        try {
            DB::beginTransaction();

            // Delete reservation items first
            ReservationItem::where('reservation_id', $reservation->id)->delete();
            
            // Then delete reservation
            $reservation->delete();

            DB::commit();

            return redirect()->route('borrower.reservation.list')
                ->with('success', 'Reservation deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('borrower.reservation.list')
                ->with('error', 'Error deleting reservation: ' . $e->getMessage());
        }
    }

    
   
    
    
}