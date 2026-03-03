<?php

namespace App\Modules\AccountsReceivable\UI\Controllers;

use App\Http\Controllers\Controller;

class AccountsReceivableController extends Controller
{
    public function index()
    {
        return view('accounts-receivable::index');
    }
}

