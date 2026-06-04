<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RowLevelSecurity
{
    public static function supported(): bool
    {
        return DB::getDriverName() === 'pgsql';
    }

    public static function applyRequestContext(Request $request): void
    {
        if (! self::supported()) {
            return;
        }

        $user = $request->user();
        self::setContext([
            'app.user_id' => $user?->id ? (string) $user->id : '',
            'app.user_role' => $user?->role ?? '',
            'app.access_mode' => $user ? 'authenticated' : 'public',
            'app.public_access_token' => self::resolvePublicToken($request),
        ]);
    }

    /**
     * @param  array<string, string>  $context
     */
    public static function setContext(array $context): void
    {
        if (! self::supported()) {
            return;
        }

        foreach ($context as $key => $value) {
            DB::selectOne('select set_config(?, ?, false)', [(string) $key, (string) $value]);
        }
    }

    /**
     * @template T
     *
     * @param  array<string, string>  $context
     * @param  callable(): T  $callback
     * @return T
     */
    public static function runWithContext(array $context, callable $callback): mixed
    {
        if (! self::supported()) {
            return $callback();
        }

        $previousContext = [];

        foreach (array_keys($context) as $key) {
            $previousContext[$key] = self::currentSetting($key);
        }

        try {
            self::setContext($context);

            return $callback();
        } finally {
            self::setContext($previousContext);
        }
    }

    public static function userPolicy(string $userColumn = 'user_id'): string
    {
        return sprintf(
            "current_setting('app.user_role', true) = 'admin' OR %s = nullif(current_setting('app.user_id', true), '')::bigint",
            $userColumn
        );
    }

    public static function userOrPublicTokenPolicy(string $userColumn, string $tokenColumn): string
    {
        return sprintf(
            "(%s) OR (current_setting('app.access_mode', true) = 'public' AND %s = nullif(current_setting('app.public_access_token', true), '')::uuid)",
            self::userPolicy($userColumn),
            $tokenColumn
        );
    }

    public static function parentPolicy(string $parentTable, string $foreignKeyColumn, string $parentUserColumn = 'user_id'): string
    {
        $parentTable = self::quoteIdentifier($parentTable);
        $foreignKeyColumn = self::quoteIdentifier($foreignKeyColumn);
        $parentUserColumn = self::quoteIdentifier($parentUserColumn);

        return sprintf(
            "current_setting('app.user_role', true) = 'admin' OR EXISTS (SELECT 1 FROM %s WHERE %s.id = %s AND %s = nullif(current_setting('app.user_id', true), '')::bigint)",
            $parentTable,
            $parentTable,
            $foreignKeyColumn,
            $parentUserColumn
        );
    }

    private static function resolvePublicToken(Request $request): string
    {
        $route = $request->route();

        foreach (['uuid', 'token', 'signing_token', 'public_token'] as $parameter) {
            $value = $route?->parameter($parameter);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private static function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    private static function currentSetting(string $key): string
    {
        $result = DB::selectOne('select current_setting(?, true) as value', [(string) $key]);

        return (string) ($result->value ?? '');
    }
}
