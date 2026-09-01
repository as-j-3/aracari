<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;


class UuidHelper
{
    private const ALLOWED_TABLES = [
        'cities',
        'countries',
        'users',
        'vehicles',
        'trips',
        'bookings',
        'branches',
        'services',
        'coupons',
        'amenities',
        'policies',
        'city_stations'
    ];

    private const CACHE_TTL = 3600; 


    private static function isTableAllowed(string $table): bool
    {
        return in_array($table, self::ALLOWED_TABLES);
    }


    private static function isValidUuid(?string $uuid): bool
    {
        return !empty($uuid) && Str::isUuid($uuid);
    }


    private static function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    public static function uuidToId(string $table, string $uuid): ?int
    {
        if (!self::isTableAllowed($table)) {
            throw new InvalidArgumentException("Table '{$table}' is not allowed for UUID conversion. Allowed tables: " . implode(', ', self::ALLOWED_TABLES));
        }

        if (!self::isValidUuid($uuid)) {
            return null;
        }

        if (!self::tableExists($table)) {
            return null;
        }

        $cacheKey = "uuid_to_id_{$table}_{$uuid}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($table, $uuid) {
            $result = DB::table($table)->where('uuid', $uuid)->first(['id']);
            return $result ? (int) $result->id : null;
        });
    }


    public static function idToUuid(string $table, int $id): ?string
    {
        if (!self::isTableAllowed($table)) {
            throw new InvalidArgumentException("Table '{$table}' is not allowed for UUID conversion. Allowed tables: " . implode(', ', self::ALLOWED_TABLES));
        }

        if (empty($id) || !is_numeric($id) || $id <= 0) {
            return null;
        }

        if (!self::tableExists($table)) {
            return null;
        }

        $cacheKey = "id_to_uuid_{$table}_{$id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($table, $id) {
            $result = DB::table($table)->where('id', $id)->first(['uuid']);
            return $result ? $result->uuid : null;
        });
    }


    public static function uuidsToIds(string $table, array $uuids): array
    {
        if (!self::isTableAllowed($table)) {
            throw new InvalidArgumentException("Table '{$table}' is not allowed for UUID conversion. Allowed tables: " . implode(', ', self::ALLOWED_TABLES));
        }

        if (empty($uuids)) {
            return [];
        }

        $validUuids = array_filter($uuids, [self::class, 'isValidUuid']);
        
        if (empty($validUuids)) {
            return [];
        }

        if (!self::tableExists($table)) {
            return [];
        }

        $cacheKey = "uuids_to_ids_{$table}_" . md5(serialize($validUuids));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($table, $validUuids) {
            $results = DB::table($table)
                ->whereIn('uuid', $validUuids)
                ->get(['uuid', 'id'])
                ->keyBy('uuid')
                ->map(fn($item) => (int) $item->id)
                ->toArray();

            return $results;
        });
    }

    public static function idsToUuids(string $table, array $ids): array
    {
        if (!self::isTableAllowed($table)) {
            throw new InvalidArgumentException("Table '{$table}' is not allowed for UUID conversion. Allowed tables: " . implode(', ', self::ALLOWED_TABLES));
        }

        if (empty($ids)) {
            return [];
        }

        $validIds = array_filter($ids, fn($id) => is_numeric($id) && $id > 0);
        
        if (empty($validIds)) {
            return [];
        }

        if (!self::tableExists($table)) {
            return [];
        }

        $cacheKey = "ids_to_uuids_{$table}_" . md5(serialize($validIds));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($table, $validIds) {
            $results = DB::table($table)
                ->whereIn('id', $validIds)
                ->get(['id', 'uuid'])
                ->keyBy('id')
                ->map(fn($item) => $item->uuid)
                ->toArray();

            return $results;
        });
    }
}