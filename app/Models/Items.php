<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Permission\Traits\HasRoles;

class Items extends Model
{
    use HasFactory, SoftDeletes, HasRoles;
    
    protected $table = 'items';
    protected $fillable = [
        'name', 
        'image', 
        'description', 
        'model', 
        'quantity', 
        'category',
        'status',
        'qr_code',
    ];

    // Add this accessor for available_quantity
    protected $appends = ['available_quantity'];

    static function getSingle($id) 
    {
        return self::find($id);
    }
    
    public function reservationItems()
    {
        return $this->hasMany(ReservationItem::class, 'item_id');
    }

    static function getItems()
    {
        return self::select('items.*')
                ->orderBy('id', 'desc')
                ->paginate(20);
    }

    public function getImage()
    {
        if(!empty($this->image) && file_exists('upload/images/' . $this->image))
        {
            return url('upload/images/' . $this->image);
        }
        else
        {
            return "";
        }
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }

   public function generateQrCode()
    {
        // QR code content - URL to item scan page
        $qrContent = url('/item/' . $this->id . '/scan');

        // Generate QR code
        $qrCode = QrCode::format('png')
            ->size(300)
            ->generate($qrContent);

        // Define file path
        $fileName = 'item_' . $this->id . '_qr.png';
        $filePath = 'qr_codes/' . $fileName;

        // Save to storage
        Storage::disk('public')->put($filePath, $qrCode);

        // Update item with QR code path
        $this->update([
            'qr_code' => $filePath
        ]);

        return $filePath;
    }

    /**
     * Get QR Code URL
     */
    public function getQrCodeUrl()
    {
        if ($this->qr_code && Storage::disk('public')->exists($this->qr_code)) {
            return url('storage/app/public/'. $this->qr_code);
        }
        
        return null;
    }

    /**
     * Check if QR code exists
     */
    public function hasQrCode()
    {
        return $this->qr_code && Storage::disk('public')->exists($this->qr_code);
    }

    public function borrowItem($quantity = 1, $userId = null)
    {
        if ($this->available_quantity >= $quantity) {
            $this->available_quantity -= $quantity;
            $this->save();

            // Create borrowing record
            Borrowing::create([
                'item_id' => $this->id,
                'user_id' => $userId ?? auth()->id(),
                'quantity' => $quantity,
                'borrowed_at' => now(),
                'status' => 'borrowed'
            ]);

            return true;
        }
        return false;
    }

    public function returnItem($quantity = 1, $borrowingId = null)
    {
        $this->available_quantity += $quantity;
        $this->save();

        // Update borrowing record
        if ($borrowingId) {
            $borrowing = Borrowing::find($borrowingId);
            if ($borrowing) {
                $borrowing->update([
                    'returned_at' => now(),
                    'status' => 'returned'
                ]);
            }
        }

        return true;
    }

    public function getCurrentBorrowings()
    {
        return $this->borrowings()->where('status', 'borrowed')->get();
    }

    public function transactions()
    {
        return $this->hasMany(TransactionsModel::class, 'item_id');
    }

    // Check if item is available for borrowing
    public function isAvailable()
    {
        return $this->quantity > 0;
    }

    // Get available quantity (total minus borrowed)
    public function getAvailableQuantityAttribute()
    {
        $borrowedCount = $this->transactions()
            ->where('status', 0)
            ->count();
            
        return max(0, $this->quantity - $borrowedCount);
    }
}