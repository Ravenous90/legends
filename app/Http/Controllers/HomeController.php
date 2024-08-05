<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $email = Auth::user()?->email;

        if (!is_null($email)) {
            $isExists = User::where('email', $email)->exists();

            if (!$isExists) {
                User::create([
                    'email' => Auth::user()->email,
                    'name' => Auth::user()->name,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        $users = User::all();

        return view('home',['users' => $users]);
    }
}
