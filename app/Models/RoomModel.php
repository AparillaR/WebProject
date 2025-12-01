<?php
// app/Models/RoomModel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class RoomModel extends Model
{
    use HasFactory;

    protected $table = 'rooms';

    protected $fillable = [
        'rm_code',
        'rm_name',
        'status',
       
      
        
    ];

    /**
     * Get single room by ID
     */
    public static function getSingle($id)
    {
        return self::find($id);
    }

    public function reservations()
    {
        return $this->hasMany(ReservationModel::class, 'room_id');
    }
    
    public function room()
    {
        return $this->hasMany(RoomModel::class);
    }
    
   
     public static function getRecord()
    {
        return self::select('rooms.*')
                    ->where('rooms.is_delete', '=', 0)
                    ->orderBy('rooms.id', 'desc')
                    ->paginate(20);
    }

    public static function getRoom()
    {
        return self::select('rooms.*')
                    ->where('rooms.is_delete', '=', 0)
                    ->orderBy('rooms.id', 'desc')
                    ->paginate(20);
    }
}