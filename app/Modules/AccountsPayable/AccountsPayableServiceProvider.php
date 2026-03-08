<?php

namespace App\Modules\AccountsPayable;

use App\Events\JournalPosted;
use App\Modules\AccountsPayable\Listeners\RecordBillLineFromJournal;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AccountsPayableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(JournalPosted::class, RecordBillLineFromJournal::class);
    }
}

