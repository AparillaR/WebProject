<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\RoomModel;
use App\Models\Items;
use App\Models\User;


class BorrowerReservationModel extends Model
{
    use HasFactory;
    protected $table = 'reservations';
    public $timestamps = false;
    protected $fillable = [
       'user_id',
       'room_id',
       'item_id',
       'reservation _items',
        'reservation_date', 
        'start_time',
      
        'status'
    ];

    protected $casts = [
        'reservation_date' => 'date',
    ];

    // database/migrations/xxxx_xx_xx_xxxxxx_create_rooms_table.php
public function up()
{
    Schema::create('rooms', function (Blueprint $table) {
        $table->id();
        $table->string('rm_code')->unique();
        $table->string('rm_name');
        $table->integer('capacity')->default(0);
        $table->string('status')->default('available'); // available, in_use, maintenance
        $table->boolean('is_delete')->default(0);
        $table->timestamps();
    });
}


   public function items()
{
    return $this->belongsToMany(Items::class, 'reservation_items')
                ->withPivot('quantity')
                ->withTimestamps();
}
    public function getAvailableAttribute()
{
    return $this->quantity - $this->reserved_quantity; // or whatever logic you use
}

public function room()
{
    return $this->belongsTo(RoomModel::class, 'rm_code');
}

public function user()
{
    return $this->belongsTo(User::class, 'rm_name');
}


   
}
