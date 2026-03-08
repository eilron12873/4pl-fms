<?php

namespace App\Modules\AccountsReceivable;

use App\Events\JournalPosted;
use App\Modules\AccountsReceivable\Listeners\RecordInvoiceLineFromJournal;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AccountsReceivableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(JournalPosted::class, RecordInvoiceLineFromJournal::class);
    }
}

