<?php

namespace App\Modules\Legal\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function terms(): View
    {
        return view('Legal::terms');
    }

    public function privacy(): View
    {
        return view('Legal::privacy');
    }

    public function faq(): View
    {
        return view('Legal::faq');
    }
}
