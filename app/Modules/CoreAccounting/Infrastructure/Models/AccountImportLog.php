<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class AccountImportLog extends Model
{
    protected $fillable = [
        'file_hash',
        'original_name',
        'user_id',
        'rows_imported',
    ];
}

