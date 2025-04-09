<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    public function book($event_id,Request $request)
    {
        //تحقق ان المستخدم مسجل دخول
        $user=Auth::user();
        if(!$user)
        {
            return response(['message'=>'unauthorized'],401);
        }

        if($user->role->name !=='user')
        {
            return response(['message'=>'only users can book events'],403);
        }

        $event= Event::find($event_id);
        if(!$event)
        {
            return response(['message'=>'Event is not found'],400);

        }
        if($event->status=='canceled' || $event->status== 'completed')
        {
            return response(['message'=>'this even is canceled or completed']);
        }

        $validator=Validator::make($request->all(),[
            'seats_reserved'=>'required|integer|min:1',
            'payment_method'=>"required|in:credit_cart,paypal"
        ]);

        if($validator->fails())
        {
            return response(['errors' => $validator->errors()], 422);
        }

        if($request->seats_reserved > $event->capacity)
        {
           return response(['message'=>'Not enough seats available'],400);
        }
        
      
 
        $total = $request->seats_reserved * $event->total_price;
        $processPayment=$this->processPayment($request->input("payment_method"),$total);
        if($processPayment["success"])
        {
            $user->book()->create([
         'user_id'=>$user->id,
         'event_id'=>$event->id,
         'seats_reserved'=>$request->seats_reserved,
         "total"=>$total,
         "payment_method"=>$request->input("payment_method"),
         "transaction_id"=>$processPayment["transaction_id"],
         'status'=>'confirmed'
        ]);
        $event->decrement('capacity', $request->seats_reserved);
        }
        return response([
            'message' => 'Booking successful',
            'event_name' => $event->name,
            'seats_reserved' => $request->seats_reserved,
            'total'=>$total,
            "payment_method"=>$request->input("payment_method"),
            "transaction_id"=>$processPayment["transaction_id"],
            'status'=>'paid'
            
        ], 201);





    }

    private function processPayment($payment_method,$total_price_after_discount)
{
    if($payment_method==="credit_cart")
    {
        return[
            "success"=>true,
            "transaction_id"=>uniqid()
        ];
    }

    elseif($payment_method==="paypal")
    {
        return[
            "success"=>true,
            "transaction_id"=>uniqid()
        ];
    }

    else{
        return[
            "success"=>false,
        ];
    }
}
}
