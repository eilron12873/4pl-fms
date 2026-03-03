<?php

namespace App\Modules\LFSAdministration\UI\Controllers;

use App\Http\Controllers\Controller;

class LFSAdministrationController extends Controller
{
    public function index()
    {
        return view('lfs-administration::index');
    }
}

