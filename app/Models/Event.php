<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table='events';
    protected $fillable=['title','description','start_time','end_time','event_image','location','total_price','capacity','status'];

    public function book()
    {
        return $this->hasMany(Book::class);
    }
}
