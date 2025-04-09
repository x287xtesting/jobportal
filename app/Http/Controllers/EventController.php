<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{



    public function addEvent(Request $request)
    {
        $validator=Validator::make($request->all(),[
        'title'=>'required|string',
        'description'=>'nullable|string|max:255',
        'start_time'=>'required|date_format:Y-m-d|after:today',
        'end_time'=>'required|date_format:Y-m-d|after:start_time',
        'location'=>'required|string',
        'total_price'=>'required|numeric|min:0',
        'event_image'=>'nullable|image|max:2048|mimes:jpeg,jpg,png,gif',
        'capacity'=>'required|integer|min:10|max:100',
        'status'=>'required|in:upcoming,ongoing,completed,canceled'
        ]);
        if($validator->fails())
        {
            return response(['errors'=>$validator->errors()],422);
        }

         $image=null;
         if($request->hasFile('event_image'))
         {
            $image=$request->file('event_image')->store('events','public');
         }

         $event=Event::create([
            'title'=>$request->title,
            'description'=>$request->description,
            'start_time'=>$request->start_time,
            'end_time'=>$request->end_time,
            'location'=>$request->location,
            'total_price'=>$request->total_price,
            'event_image'=>$image,
            'capacity'=>$request->capacity,
            'status'=>$request->status

         ]);

         return response([
           'message'=>'Event added successfully',
           'id'=> $event->id,
           'title'=> $event->title,
            'description'=> $event->description,
            'start_time'=> $event->start_time,
            'end_time'=> $event->end_time,
            'location'=> $event->location,
            'total_price'=> $event->total_price,
            'event_image'=>$event->event_image ? url("/storage/".$event->event_image) : null,
            'capacity'=> $event->capacity,
            'status'=> $event->status

         ],200);
        }

        public function updateEvent($event_id, Request $request)
        {
            $event = Event::find($event_id);
        
            if (!$event) {
                return response()->json(['message' => 'Event not found'], 404);
            }
        
            // السماح بالتعديل فقط إذا كان الحدث "ongoing" أو "upcoming"
            if (!in_array($event->status, ['upcoming', 'ongoing'])) {
                return response()->json(['message' => "This event cannot be updated as it is {$event->status}"], 403);
            }
        
            $validator = Validator::make($request->all(), [
                'title' => 'nullable|string',
                'description' => 'nullable|string|max:255',
                'start_time' => 'nullable|date|after:today',
                'end_time' => 'nullable|date|after:start_time',
                'location' => 'nullable|string',
                'total_price' => 'nullable|numeric|min:0',
                'event_image' => 'nullable|image|max:2048|mimes:jpeg,jpg,png,gif',
                'capacity' => 'nullable|integer|min:10|max:100',
                'status' => 'nullable|in:upcoming,ongoing,completed,canceled'
            ]);
        
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $isUpdate=false;
        
            if($request->filled('title') && $request->title !== $event->title)
       {
         $event->update([
           'title'=>$request->title
         ]);
         $isUpdate=true;
       }

       if($request->filled('description') && $request->description != $event->description)
       {
         $event->update([
           'description'=>$request->description
         ]);
         $isUpdate=true;
       }

       
       if($request->filled('start_time') && $request->start_time != $event->start_time)
       {
        if($event->status=='ongoing')
        {
            return response(['message'=>'This event is already ongoing,you can not change the start date']);
        }
        else
        {
         $event->update([
           'start_time'=>$request->start_time
         ]);
         $isUpdate=true;
       }
      }

       if($request->filled('end_time') && $request->end_time != $event->end_time)
       {
         $event->update([
           'end_time'=>$request->end_time
         ]);
         $isUpdate=true;
       }

       if($request->filled('location') && $request->location != $event->location)
       {
         $event->update([
           'location'=>$request->location
         ]);
         $isUpdate=true;
       }

       if($request->filled('total_price') && $request->total_price != $event->total_price)
       {
         $event->update([
           'total_price'=>$request->total_price
         ]);
         $isUpdate=true;
       }

       if($request->hasFile('event_image'))
       {
        if ($event->event_image && file_exists(storage_path('app/public/' . $event->event_image))) {
            Storage::disk('public')->delete($event->event_image );  // حذف الصورة القديمة
           
        }
    
        // تخزين الصورة الجديدة
        $image = $request->file('event_image')->store('events', 'public');  // تخزين الصورة في مجلد public/images
        $event->update(['event_image'=>$image]);
        $isUpdate=true;
       }
       
       if($request->filled('capacity') && $request->capacity != $event->capacity)
       {
         $event->update([
           'capacity'=>$request->capacity
         ]);
         $isUpdate=true;
       }
       
       if($request->filled('status') && $request->status != $event->status)
       {
        if($event->status=='ongoing' && $request->filled('status')=='upcoming')
        {
          return response(['message'=>'you can not change the ongoing status to upcoming']);
        }
         $event->update([
           'status'=>$request->status
         ]);
         $isUpdate=true;
       }


       if(!$isUpdate)
       {
           return response([
               "message"=>"no change were made",
               "event"=>$event
           ],200);
       }
       
       return response([
          "message" =>"updated event successfully",
        "event"=>$event
      ],200);
       
       }
        
        
public function deleteEvent($event_id)
{
    try {
        // البحث عن الحدث باستخدام event_id
        $event = Event::find($event_id);
        
        // إذا لم يتم العثور على الحدث
        if (!$event) {
            return response(['message' => 'Event is not found'], 404);
        }
        
        // حذف الصورة المرفقة إذا وجدت
        if ($event->event_image && Storage::disk('public')->exists($event->event_image)) {
            Storage::disk('public')->delete($event->event_image);
        }
        
        // حذف الحدث مباشرة
        $event->delete();
        
        return response(['message' => 'Event deleted successfully'], 200);
        
    } catch (\Exception $e) {
        return response([
            'message' => 'Failed to delete event',
            'error' => $e->getMessage()
        ], 500);
    }
}

    }