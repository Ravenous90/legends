<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRating;
use App\Models\UserRatingHistory;
use App\Services\LegendService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Nette\Utils\Image;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(public LegendService $legendService)
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

        $legends = User::orderBy('rating', 'DESC')->get();

        $images = [];

        foreach ($legends as $legend) {
            try {
                $directory = storage_path('app/public/legends/' .  $legend->id);
                $files = scandir($directory);
                $link = asset('storage/legends/' .  $legend->id . '/' . $files[2]);
                $images[$legend->id] = $link;
            } catch (\Exception) {
                continue;
            }
        }

        $myValues = [];
        $valueCounts = [];

        foreach (UserRating::all() as $rating) {
            if (!isset($valueCounts[$rating->assessed_user_id])) {
                $valueCounts[$rating->assessed_user_id] = 1;
            } else {
                $valueCounts[$rating->assessed_user_id] = $valueCounts[$rating->assessed_user_id] + 1;
            }

            if (intval($rating->evaluating_user_id) === Auth::id()) {
                $nextUpdateDate = (new Carbon($rating->updated_at))->addMonth();

                $myValues[intval($rating->assessed_user_id)] = [
                    'value' => intval($rating->value),
                    'updated' => $rating->updated_at,
                    'next_update' => $nextUpdateDate->format('D, d M Y H:i:s'),
                    'is_disabled' => intval(Carbon::now() < $nextUpdateDate),
                ];
            }
        }

        return view('home', [
            'legends' => $legends,
            'user' => Auth::user(),
            'images' => $images,
            'myValues' => $myValues,
            'valueCounts' => $valueCounts
        ]);
    }

    public function updateProfile()
    {
        $name = \request('name');

        if (!is_null($name) && $name !== '') {
            Auth::user()->update([
                'name' => $name
            ]);
        }

        $image = \request('file');

        try {
            if (!is_null($image)) {
                $path = "public/legends/" . Auth::id();
                Storage::deleteDirectory($path);

                $image->store($path);
            }
        } catch (\Exception) {
            return redirect()->back();
        }

        return redirect()->back();
    }

    public function setValue()
    {
        $value = intval(\request('value'));
        $legendId = intval(\request('legend_id'));

        if ($value < 0) {
            $value = 40;
        }

        if ($value > 100) {
            $value = 100;
        }

        UserRating::updateOrCreate(
            [
                'evaluating_user_id' => Auth::id(),
                'assessed_user_id' => $legendId
            ],
            [
                'value' => $value,
            ],
        );

        UserRatingHistory::create(
            [
                'evaluating_user_id' => Auth::id(),
                'assessed_user_id' => $legendId,
                'value' => $value,
            ],
        );

        $this->legendService->reCalculateRating($legendId);

        return redirect()->back();
    }
}
