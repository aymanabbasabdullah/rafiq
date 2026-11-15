import { HttpResponseError, http } from '@inertiajs/core';

export class JsonRequestError extends Error {
    constructor(
        public readonly status: number,
        public readonly body: unknown,
    ) {
        super(`Request failed with status ${status}`);
    }
}

function parseBody(raw: string): unknown {
    if (raw === '') {
        return null;
    }

    try {
        return JSON.parse(raw) as unknown;
    } catch {
        return raw;
    }
}

/**
 * Sends a JSON POST through Inertia's HTTP client (which attaches the XSRF token)
 * without navigating, so nothing ends up in the URL or browser history (ADR-019, PRIV-003).
 *
 * Request and response bodies are never logged.
 */
export async function postJson<TResponse>(url: string, data: Record<string, unknown>, signal?: AbortSignal): Promise<TResponse> {
    try {
        const response = await http.getClient().request({
            method: 'post',
            url,
            data,
            signal,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        });

        if (response.status < 200 || response.status >= 300) {
            throw new JsonRequestError(response.status, parseBody(response.data));
        }

        return parseBody(response.data) as TResponse;
    } catch (error) {
        if (error instanceof HttpResponseError) {
            throw new JsonRequestError(error.response.status, parseBody(error.response.data));
        }

        throw error;
    }
}
