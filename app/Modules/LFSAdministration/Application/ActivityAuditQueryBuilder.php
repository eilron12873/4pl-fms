<?php

namespace App\Modules\LFSAdministration\Application;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ActivityAuditQueryBuilder
{
    /**
     * @return array{log_name?: string, event?: string, from_date?: string, to_date?: string, causer_id?: int}
     */
    public static function validateFilters(Request $request, bool $forExport = false): array
    {
        $data = $request->validate([
            'log_name' => ['nullable', 'string', 'max:64'],
            'event' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'causer_id' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $fromDate = $data['from_date'] ?? null;
        $toDate = $data['to_date'] ?? null;

        if (
            filled($fromDate)
            && filled($toDate)
            && \Carbon\Carbon::parse($fromDate)->gt(\Carbon\Carbon::parse($toDate))
        ) {
            throw ValidationException::withMessages([
                'to_date' => [__('The to date must be greater than or equal to the from date.')],
            ]);
        }

        if ($forExport && config('audit.export.require_date_range')) {
            if (blank($fromDate) || blank($toDate)) {
                throw ValidationException::withMessages([
                    'from_date' => [__('Both from date and to date are required for export.')],
                ]);
            }
            $from = \Carbon\Carbon::parse($fromDate)->startOfDay();
            $to = \Carbon\Carbon::parse($toDate)->startOfDay();
            $spanDays = $from->diffInDays($to) + 1;
            $maxSpan = (int) config('audit.export.max_date_span_days', 366);
            if ($spanDays > $maxSpan) {
                throw ValidationException::withMessages([
                    'to_date' => [__('The selected date range may not exceed :days days.', ['days' => $maxSpan])],
                ]);
            }
        }

        return $data;
    }

    public static function baseQuery(): Builder
    {
        return Activity::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @param  array{log_name?: string, event?: string, from_date?: string, to_date?: string, causer_id?: int}  $data
     */
    public static function applyFilters(Builder $query, array $data): void
    {
        if (filled($data['log_name'] ?? null)) {
            $query->where('log_name', $data['log_name']);
        }
        if (filled($data['event'] ?? null)) {
            $query->where('event', 'like', '%'.$data['event'].'%');
        }
        if (filled($data['from_date'] ?? null)) {
            $query->whereDate('created_at', '>=', $data['from_date']);
        }
        if (filled($data['to_date'] ?? null)) {
            $query->whereDate('created_at', '<=', $data['to_date']);
        }
        if (filled($data['causer_id'] ?? null)) {
            $query->where('causer_type', \App\Models\User::class)
                ->where('causer_id', $data['causer_id']);
        }
    }
}
