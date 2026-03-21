<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportAccessService
{
    /**
     * Returns array of accessible office IDs for the given user.
     * Admin   → null  (null = unrestricted / all offices)
     * Superior → own + all direct subordinate offices
     * Regular → own office only
     */
    public static function accessibleOfficeIds($user): ?array
    {
        if ($user->role === 'admin') {
            return null;
        }

        if ($user->role === 'superior') {
            $subordinates = DB::table('office_libraries')
                ->where('parent_office_id', $user->office_id)
                ->pluck('id')
                ->toArray();

            return array_unique(array_merge([$user->office_id], $subordinates));
        }

        return [$user->office_id];
    }

    /**
     * Applies office_id scope to the given query builder.
     * Admin gets no restriction. Others are limited to accessible offices.
     */
    public static function scopeQuery($query, $user, string $column = 'office_id')
    {
        $ids = self::accessibleOfficeIds($user);
        if ($ids !== null) {
            $query->whereIn($column, $ids);
        }
        return $query;
    }

    /**
     * Returns true if the user can access data for the specified office.
     */
    public static function canAccessOffice($user, string $officeId): bool
    {
        $ids = self::accessibleOfficeIds($user);
        if ($ids === null) {
            return true;
        }
        return in_array($officeId, $ids);
    }

    /**
     * Returns all accessible office records (id + office_name) for display in filter dropdowns.
     */
    public static function accessibleOffices($user): \Illuminate\Support\Collection
    {
        $query = DB::table('office_libraries')->select('id', 'office_name');
        $ids = self::accessibleOfficeIds($user);
        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }
        return $query->orderBy('office_name')->get();
    }
}
