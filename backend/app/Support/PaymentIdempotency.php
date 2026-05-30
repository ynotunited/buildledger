<?php

namespace App\Support;

use App\Models\IdempotencyRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PaymentIdempotency
{
    public function reserve(
        string $scope,
        ?string $idempotencyKey,
        array $payload,
        ?int $userId = null,
        array $metadata = []
    ): array {
        if (! is_string($idempotencyKey) || trim($idempotencyKey) === '') {
            throw ValidationException::withMessages([
                'idempotency_key' => 'An idempotency key is required for payment requests.',
            ]);
        }

        $idempotencyKey = trim($idempotencyKey);
        $requestHash = $this->hashPayload($payload);

        $existing = IdempotencyRecord::query()
            ->where('scope', $scope)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            $this->ensureSameRequest($existing, $requestHash);

            if ($existing->response_payload !== null) {
                return [
                    'record' => $existing,
                    'state' => 'cached',
                    'cached_response' => $existing->response_payload ?? [],
                    'response_status' => $existing->response_status ?? 200,
                ];
            }

            return [
                'record' => $existing,
                'state' => 'processing',
            ];
        }

        try {
            $record = IdempotencyRecord::create([
                'user_id' => $userId,
                'scope' => $scope,
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'status' => 'processing',
                'metadata' => $metadata,
            ]);
        } catch (QueryException $exception) {
            $record = IdempotencyRecord::query()
                ->where('scope', $scope)
                ->where('idempotency_key', $idempotencyKey)
                ->firstOrFail();

            $this->ensureSameRequest($record, $requestHash);

            if ($record->response_payload !== null) {
                return [
                    'record' => $record,
                    'state' => 'cached',
                    'cached_response' => $record->response_payload ?? [],
                    'response_status' => $record->response_status ?? 200,
                ];
            }

            return [
                'record' => $record,
                'state' => 'processing',
            ];
        }

        return [
            'record' => $record,
            'state' => 'new',
        ];
    }

    public function cacheResponse(
        IdempotencyRecord $record,
        int $statusCode,
        array $payload,
        string $status = 'completed',
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $metadata = []
    ): IdempotencyRecord {
        $record->forceFill([
            'status' => $status,
            'response_status' => $statusCode,
            'response_payload' => $payload,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'metadata' => array_merge($record->metadata ?? [], $metadata),
        ])->save();

        return $record->fresh();
    }

    public function responseFromRecord(IdempotencyRecord $record): array
    {
        return [
            'status' => $record->response_status ?? 200,
            'payload' => $record->response_payload ?? [],
        ];
    }

    private function ensureSameRequest(IdempotencyRecord $record, string $requestHash): void
    {
        if (! hash_equals($record->request_hash, $requestHash)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'This idempotency key was already used for a different payment request.',
            ]);
        }
    }

    private function hashPayload(array $payload): string
    {
        $normalized = Arr::sortRecursive($payload);

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }
}
