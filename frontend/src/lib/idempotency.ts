const PREFIX = 'buildledger:idempotency:';

function fingerprint(payload: unknown): string {
    if (typeof payload === 'string') {
        return payload;
    }

    return JSON.stringify(payload);
}

export function getOrCreateIdempotencyKey(scope: string, payload: unknown): string {
    if (typeof window === 'undefined') {
        return crypto.randomUUID();
    }

    const key = `${PREFIX}${scope}:${fingerprint(payload)}`;

    try {
        const existing = window.sessionStorage.getItem(key);
        if (existing) {
            return existing;
        }

        const generated = crypto.randomUUID();
        window.sessionStorage.setItem(key, generated);
        return generated;
    } catch {
        return crypto.randomUUID();
    }
}

export function clearIdempotencyKey(scope: string, payload: unknown): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.sessionStorage.removeItem(`${PREFIX}${scope}:${fingerprint(payload)}`);
    } catch {
        // ignore storage failures
    }
}
