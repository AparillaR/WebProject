<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\User as Authenticatable;

class TransactionsModel extends Model
{
    use HasFactory, SoftDeletes, HasRoles;

     protected $table = 'transactions';
    protected $fillable = [
        'user_id', 
        'item_id', 
        'borrowed_date',
        'due_date',
        'return_date',
        'status',
    ];
    protected $casts = [
        'borrowed_date' => 'datetime',
        'due_date' => 'datetime',
        'return_date' => 'datetime',    
    ];

    static function getSingle($id) 
    {
        return self::find($id);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function item()
    {
        return $this->belongsTo(Items::class, 'item_id');
    }
     public function hasQrCode()
    {
        // Adjust this logic based on how you store QR code information
        return !empty($this->qr_code_path) || !empty($this->qr_code_url);
    }

    public static function getTransactions() 
    {
        return self::select('transactions.*')
            ->orderBy('id', 'desc')
            ->paginate(5);
    }

    public static function getBorrowerTransactions()
    {
        $userId = Auth::id(); // Get current logged-in user ID
        
        return self::select(
                'transactions.*',
                'items.name as item_name',
                'items.image as item_image',
                'items.quantity as item_quantity',
                'transactions.due_date as deadline',
                'transactions.status'
            )
            ->join('items', 'items.id', '=', 'transactions.item_id')
            ->where('transactions.user_id', $userId) // Only show transactions for logged-in user
            ->where('transactions.status', 'borrowed') // Only show borrowed items
            ->orderBy('transactions.id', 'desc')
            ->paginate(20);
    }

    // Search functionality for borrower
    public static function getBorrowerTransactionsSearch($search = [])
    {
        $userId = Auth::id();
        
        $query = self::select(
                'transactions.*',
                'items.name as item_name',
                'items.image as item_image',
                'items.quantity as item_quantity',
                'transactions.due_date as deadline',
                'transactions.status'
            )
            ->join('items', 'items.id', '=', 'transactions.item_id')
            ->where('transactions.user_id', $userId)
            ->where('transactions.status', 0);

        // Search by item name
        if (!empty($search['name'])) {
            $query->where('items.name', 'like', '%' . $search['name'] . '%');
        }

        // Search by date
        if (!empty($search['date'])) {
            $query->whereDate('transactions.borrowed_date', $search['date']);
        }

        return $query->orderBy('transactions.id', 'desc')->paginate(20);
    }


     // Check if transaction is overdue
    public function getIsOverdueAttribute()
    {
        if ($this->status === 1) {
            return false;
        }
        
        return $this->due_date && $this->due_date->isPast();
    }


}

