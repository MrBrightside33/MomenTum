import { REQUEST_TIMEOUT } from './loading-config';
import { showToast } from './toast';

const activeRequests = new Map();

export class HttpError extends Error {
    constructor({ status = 0, message, errors = {}, data = null, code = null }) {
        super(message);
        this.name = 'HttpError';
        this.status = status;
        this.errors = errors;
        this.data = data;
        this.code = code;
    }
}

const readResponseBody = async (response, responseType) => {
    if (response.status === 204 || responseType === 'response') {
        return null;
    }

    if (responseType === 'blob') {
        return response.blob();
    }

    if (responseType === 'text') {
        return response.text();
    }

    const contentType = response.headers.get('content-type') ?? '';

    if (!contentType.includes('application/json')) {
        return null;
    }

    try {
        return await response.json();
    } catch (error) {
        console.error('Could not parse an HTTP JSON response.', error);
        throw new HttpError({
            status: response.status,
            message: 'The server returned an unreadable response.',
        });
    }
};

const getLagValue = () => {
    if (document.querySelector('meta[name="app-env"]')?.content !== 'local') {
        return null;
    }

    const cookieLag = document.cookie.match(/(?:^|;\s*)mt_lag=(\d+)/)?.[1];
    let storageLag = null;

    try {
        storageLag = window.localStorage.getItem('mt_lag');
    } catch (error) {
        console.error('Could not read the development latency setting.', error);
    }

    const lag = Number(cookieLag ?? storageLag ?? 0);

    return [800, 2000, 4000].includes(lag) ? lag : null;
};

export const request = async (
    url,
    {
        method = 'GET',
        json,
        body,
        headers = {},
        signal,
        key,
        timeout = REQUEST_TIMEOUT,
        responseType = 'json',
    } = {},
) => {
    if (navigator.onLine === false) {
        throw new HttpError({ message: "You're offline. Check your connection and try again." });
    }

    if (key && activeRequests.has(key)) {
        activeRequests.get(key).abort();
    }

    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort('timeout'), timeout);
    const requestHeaders = new Headers(headers);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!requestHeaders.has('Accept')) {
        requestHeaders.set('Accept', responseType === 'json' ? 'application/json' : '*/*');
    }
    requestHeaders.set('X-Requested-With', 'XMLHttpRequest');

    if (csrfToken && !['GET', 'HEAD', 'OPTIONS'].includes(method.toUpperCase())) {
        requestHeaders.set('X-CSRF-TOKEN', csrfToken);
    }

    const lag = getLagValue();

    if (lag !== null) {
        requestHeaders.set('X-Lag', String(lag));
    }

    let requestBody = body;

    if (json !== undefined) {
        requestHeaders.set('Content-Type', 'application/json');
        requestBody = JSON.stringify(json);
    }

    const abortFromCaller = () => controller.abort(signal.reason);
    signal?.addEventListener('abort', abortFromCaller, { once: true });
    if (signal?.aborted) {
        abortFromCaller();
    }

    if (key) {
        activeRequests.set(key, controller);
    }

    try {
        const response = await fetch(url, {
            method,
            headers: requestHeaders,
            body: requestBody,
            credentials: 'same-origin',
            signal: controller.signal,
        });
        const data = await readResponseBody(response, responseType);

        if (response.status === 401) {
            window.location.assign('/login');
            throw new HttpError({ status: 401, message: 'Please sign in again.', data });
        }

        if (response.status === 419) {
            showToast('error', 'Your session expired. Reloading the page…');
            window.setTimeout(() => window.location.reload(), 1500);
            throw new HttpError({ status: 419, message: 'Your session expired.', data });
        }

        if (response.status >= 500) {
            throw new HttpError({
                status: response.status,
                message: 'Something went wrong on the server. Please try again.',
                data,
            });
        }

        if (!response.ok) {
            throw new HttpError({
                status: response.status,
                message: typeof data?.message === 'string' ? data.message : 'The request could not be completed.',
                errors: data?.errors ?? {},
                data,
            });
        }

        return {
            status: response.status,
            ok: response.ok,
            data,
            headers: response.headers,
            url: response.url,
        };
    } catch (error) {
        if (error instanceof HttpError) {
            throw error;
        }

        if (error?.name === 'AbortError') {
            const timedOut = controller.signal.reason === 'timeout';
            const message = timedOut
                ? 'The request timed out. Please try again.'
                : 'The request was cancelled.';
            throw new HttpError({ message, code: timedOut ? 'timeout' : 'aborted' });
        }

        if (navigator.onLine === false) {
            throw new HttpError({ message: "You're offline. Check your connection and try again." });
        }

        throw new HttpError({
            message: 'The request could not be completed. Please try again.',
            data: error,
            code: 'network',
        });
    } finally {
        window.clearTimeout(timeoutId);
        signal?.removeEventListener('abort', abortFromCaller);

        if (key && activeRequests.get(key) === controller) {
            activeRequests.delete(key);
        }
    }
};
