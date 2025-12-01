<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use Request;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'name',
        'phone_number',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

   
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    static function getSingle($id) 
    {
        return self::find($id);
    }
     public function transactions()
    {
        return $this->hasMany(TransactionsModel::class);
    }

    static function getAdmin() 
    {
        $return = self::select('users.*')
        ->where('user_type', '=',1)
        ->where('is_delete', '=',0);

       if(!empty(Request::get('school_id')))   
        {
            $return = $return->where('school_id','like', '%'.Request::get('school_id').'%');
        }

        if(!empty(Request::get('name')))   
        {
            $return = $return->where('name','like', '%'.Request::get('name').'%');
        }

         if(!empty(Request::get('phone_number')))   
        {
            $return = $return->where('phone_number','like', '%'.Request::get('phone_number').'%');
        }

        if(!empty(Request::get('email')))   
        {
            $return = $return->where('email','like', '%'.Request::get('email').'%');
        }

        if(!empty(Request::get('date')))   
        {
            $return = $return->whereDate('created_at','=', Request::get('date'));
        }

        $return = $return->orderBy('id', 'desc')
        ->paginate(20);

        return $return;

    }
    
    static function getBorrower() 
    {
        $return = self::select('users.*')
        ->where('user_type', '=',2)
        ->where('is_delete', '=',0);
        if(!empty(Request::get('name')))   
        {
            $return = $return->where('name','like', '%'.Request::get('name').'%');
        }

        if(!empty(Request::get('email')))   
        {
            $return = $return->where('email','like', '%'.Request::get('email').'%');
        }

        if(!empty(Request::get('date')))   
        {
            $return = $return->whereDate('created_at','=', Request::get('date'));
        }

        $return = $return->orderBy('id', 'desc')
        ->paginate(5);

        return $return;

    }

}
