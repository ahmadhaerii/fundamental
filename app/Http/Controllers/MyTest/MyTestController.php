<?php

namespace App\Http\Controllers\MyTest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class MyTestController extends Controller
{


    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $response = Http::get('https://cdn.tsetmc.com/api/Instrument/GetInstrumentInfo/778253364357513');
        $response = Http::get('https://cdn.tsetmc.com/api/ClosingPrice/GetClosingPriceInfo/778253364357513');

        $html = $response->body();

        return $html ;
//        $request->user()->fill($request->validated());
//
//        if ($request->user()->isDirty('email')) {
//            $request->user()->email_verified_at = null;
//        }
//
//        $request->user()->save();
//
//        return to_route('profile.edit');
    }

}
