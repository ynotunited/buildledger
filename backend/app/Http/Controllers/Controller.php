<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function ensureSessionStore(Request $request): void
    {
        if (! $request->hasSession()) {
            $request->setLaravelSession(app('session')->driver());
        }
    }

    protected function ensureOwnedByUser(Request $request, Model $model, string $ownerKey = 'user_id'): void
    {
        if ($request->user()?->isAdmin()) {
            return;
        }

        abort_unless(
            (int) $model->getAttribute($ownerKey) === (int) $request->user()->getAuthIdentifier(),
            404
        );
    }

    protected function ensureOperationEnabled(string $configKey, string $message, int $status = 503): void
    {
        abort_unless((bool) config($configKey, true), $status, $message);
    }

    protected function ensureBelongsTo(Model $model, string $foreignKey, Model $parent): void
    {
        abort_unless(
            (int) $model->getAttribute($foreignKey) === (int) $parent->getKey(),
            404
        );
    }
}
