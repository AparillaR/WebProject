<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;



class ReservationModel extends Model
{
    use HasFactory;

    protected $table = 'reservations';

    protected $fillable = [
        'user_id',
        'room_id',
        'item_id', 
        'reservation_date', 
        'time_limit', 
        'status', 
        'created_at', 
        'updated_at'
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room()
    {
        return $this->belongsTo(RoomModel::class, 'room_id');
    }

    public function reservationItems()
    {
        return $this->hasMany(ReservationItem::class, 'reservation_id');
    }

    public static function getReservations()
    {
        return self::with(['user', 'room', 'item', 'reservationItems.item'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
    }

        public function reservation()
    {
        return $this->belongsTo(ReservationModel::class);
    }

    public static function getSingle($id)
    {
        return self::with(['user', 'room', 'item', 'reservationItems.item'])->find($id);
    }
}