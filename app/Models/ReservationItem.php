<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationItem extends Model
{
    use HasFactory;

    protected $table = 'reservation_items';

    protected $fillable = [
        'reservation_id', 'item_id', 'quantity'
    ];

    /**
     * Get the reservation that owns the reservation item.
     */
    public function reservation()
    {
        return $this->belongsTo(ReservationModel::class, 'reservation_id');
    }

    /**
     * Get the item that owns the reservation item.
     */
    public function item()
    {
        return $this->belongsTo(Items::class, 'item_id');
    }
}