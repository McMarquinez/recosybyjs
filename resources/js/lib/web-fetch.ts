const unsafeMethods = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);

const csrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
};

const xsrfCookie = () => {
    const raw = document.cookie
        .split(';')
        .map((part) => part.trim())
        .find((part) => part.startsWith('XSRF-TOKEN='));

    if (!raw) {
        return '';
    }

    const value = raw.slice('XSRF-TOKEN='.length);

    try {
        return decodeURIComponent(value);
    } catch {
        return value;
    }
};

export const webFetch = (input: RequestInfo | URL, init: RequestInit = {}) => {
    const method = (init.method ?? 'GET').toUpperCase();
    const headers = new Headers(init.headers ?? {});

    if (!headers.has('Accept')) {
        headers.set('Accept', 'application/json');
    }

    if (!headers.has('X-Requested-With')) {
        headers.set('X-Requested-With', 'XMLHttpRequest');
    }

    if (unsafeMethods.has(method)) {
        if (!headers.has('X-CSRF-TOKEN')) {
            const token = csrfToken();
            if (token) {
                headers.set('X-CSRF-TOKEN', token);
            }
        }

        // Laravel may also accept X-XSRF-TOKEN (from XSRF-TOKEN cookie).
        if (!headers.has('X-XSRF-TOKEN')) {
            const token = xsrfCookie();
            if (token) {
                headers.set('X-XSRF-TOKEN', token);
            }
        }
    }

    return fetch(input, {
        ...init,
        headers,
        credentials: 'same-origin',
    });
};
