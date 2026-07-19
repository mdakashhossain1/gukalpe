<?php

namespace App\Modules\Profile\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('Profile::profile', [
            'user' => Auth::user(),
        ]);
    }
}
