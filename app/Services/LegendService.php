<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRating;

class LegendService
{
    public function reCalculateRating(int $legendId = 0)
    {
        $legend = User::find($legendId);

        $rating = round(UserRating::where('assessed_user_id', $legendId)->avg('value'), 2);

        $legend->update(['rating' => $rating]);
    }
}