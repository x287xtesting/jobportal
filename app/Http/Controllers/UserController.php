<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Roles;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function regist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|ends_with:gmail.com|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $image = null;
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture')->store('users', 'public');
        }

        $role = Roles::where('name', 'user')->first();
        if (!$role) {
            return response()->json(['error' => 'Role "user" not found in database'], 500);
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_picture' => $image,
            'role_id' => $role->id,
        ]);




        JWTAuth::factory()->setTTL(60 * 24);
        $token = JWTAuth::fromUser($user);


        return response([
            'message' => 'account created successfully,Verification code sent to your email',
            'name' => $user->name,
            'email' => $user->email,
            'profile_picture' => $user->profile_picture ? url('storage/' . $user->profile_picture) : null,
            'role' => $role->name,
            'token' => $token,
        ], 200);
    }





    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|ends_with:gmail.com',
            'password' => 'required|string|min:6',
            'remember_me' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }
        //   للمستخدمip مفتاح يتضمن عنوان
        $cachekey = 'login_attemps' . $request->ip();
        // تخزين المفتاح في الكاش وبدئه من الصفر سنوقفه عند الثلاثة
        $attempts = Cache::get($cachekey, 0);
        if ($attempts >= 3) {
            return response(['message' => 'Too many attempts to log in,You have been blocked for 30 seconds'], 429);
        }

        // البحث عن المستخدم باستخدام البريد الالكتروني
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response(['message' => 'User not found'], 404);
        }

        $guard = ($user->role->name === 'admin') ? 'admin' : 'user';
        if (!Auth::guard($guard)->attempt(['email' => $request->email, 'password' => $request->password])) {
            return response(['message' => 'wrong email or password'], 401);
        }
        $user = Auth::guard($guard)->user();
        Cache::forget($cachekey);
        // اذا تسجل الدخول بشكل صحيح احذف المفتاح

        // اذا ضغط المستخدم على تذكرني خلي وثت التوكن الضعف
        if ($request->input("remember_me")) {
            JWTAuth::factory()->setTTL(0.5);
        } else {
            JWTAuth::factory()->setTTL(60 * 15);
        }

        $token = JWTAuth::fromUser($user);

        return response([
            "message" => "login successfully",
            'name' => $user->name,
            'email' => $user->email,
            'profile_picture' => $user->profile_picture ? url('storage/' . $user->profile_picture) : null,
            'role' => $user->role->name,
            'token' => $token,
        ], 200);

        // هون في حال ماتسجل الدخول بشكل صحيح زيد على عداد محاولات التسجيل واحد
        Cache::put($cachekey, $attempts + 1, 30);

        return response([
            "message" => "wrong email or password",

        ], 200);
    }


    public function logout()
    {
        $token = JWTAuth::getToken();
        JWTAuth::invalidate($token);
        return response([
            "message" => "logout successfully"
        ]);
    }

    public function displayAllEvents()
    {
        $events = Event::latest()->get();

        if ($events->isEmpty()) {
            return response([
                "message" => "No events found"
            ], 404);
        }
        $events = $events->map(function ($event) {
            // تحقق من وجود المسار للصورة


            // إعادة بناء الكائن مع رابط الصورة
            return [
                'message' => 'These are all the events',
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'location' => $event->location,
                'total_price' => $event->total_price,
                'event_image' => $event->event_image ? url("/storage/" . $event->event_image) : null,
                'capacity' => $event->capacity,
                'status' => $event->status
            ];
        });

        // إرجاع البيانات
        return response()->json($events, 200);
    }

    public function showEvent($event_id)
    {
        $event = Event::find($event_id);
        if (!$event) {
            return response(['message' => 'even is not found'], 400);
        }

        return response([
            'message' => 'This is the event details',
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
            'location' => $event->location,
            'total_price' => $event->total_price,
            'event_image' => $event->event_image ? url("/storage/" . $event->event_image) : null,
            'capacity' => $event->capacity,
            'status' => $event->status
        ]);
    }
}
