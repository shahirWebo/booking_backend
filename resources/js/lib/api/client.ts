import { resolveApiAccessToken } from '@/lib/api/tokenProvider';
import type {
    ApiEnvelope,
    ApiErrorEnvelope,
    ApiFieldErrors,
    ApiMeta,
    ApiSuccessEnvelope,
} from '@/types/api';

export type ApiRequestMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export type ApiQueryValue =
    | string
    | number
    | boolean
    | null
    | undefined
    | Array<string | number | boolean | null | undefined>;

export type ApiRequestOptions<TBody = unknown> = {
    body?: TBody;
    headers?: HeadersInit;
    method?: ApiRequestMethod;
    query?: Record<string, ApiQueryValue>;
    signal?: AbortSignal;
};

export type ApiClientAuthMode = 'public' | 'authenticated';

type ApiClientConfig = {
    authMode: ApiClientAuthMode;
    baseUrl?: string;
    defaultHeaders?: HeadersInit;
    fetchImplementation?: typeof fetch;
};

type RequestConfig = {
    method: ApiRequestMethod;
    path: string;
};

const DEFAULT_API_BASE_URL = '/api/v1';

export class ApiClientError extends Error {
    readonly code: string;
    readonly fieldErrors: ApiFieldErrors;
    readonly meta: ApiMeta | null;
    readonly responseHeaders: Headers;
    readonly status: number;

    constructor(
        status: number,
        responseHeaders: Headers,
        payload: Partial<ApiErrorEnvelope> & Pick<ApiErrorEnvelope, 'message'>,
    ) {
        super(payload.message);

        this.name = 'ApiClientError';
        this.code = payload.code ?? 'UNKNOWN_API_ERROR';
        this.fieldErrors = payload.errors ?? {};
        this.meta = payload.meta ?? null;
        this.responseHeaders = responseHeaders;
        this.status = status;
    }
}

export class MissingApiAccessTokenError extends Error {
    constructor() {
        super(
            'An authenticated API request was attempted without an access token.',
        );
        this.name = 'MissingApiAccessTokenError';
    }
}

export type ApiClient = ReturnType<typeof createApiClient>;

export function createPublicApiClient(
    config: Omit<ApiClientConfig, 'authMode'> = {},
) {
    return createApiClient({
        ...config,
        authMode: 'public',
    });
}

export function createAuthenticatedApiClient(
    config: Omit<ApiClientConfig, 'authMode'> = {},
) {
    return createApiClient({
        ...config,
        authMode: 'authenticated',
    });
}

export function createApiClient(config: ApiClientConfig) {
    const authMode = config.authMode;
    const baseUrl = config.baseUrl ?? DEFAULT_API_BASE_URL;
    const defaultHeaders = new Headers(config.defaultHeaders);
    const fetchImplementation =
        config.fetchImplementation ?? window.fetch.bind(window);

    async function send<TResponse, TBody = unknown>(
        path: string,
        options: ApiRequestOptions<TBody> = {},
    ): Promise<ApiSuccessEnvelope<TResponse> | undefined> {
        const request = await createRequest(
            baseUrl,
            authMode,
            {
                method: options.method ?? 'GET',
                path,
            },
            options,
            defaultHeaders,
        );

        const response = await fetchImplementation(request);

        if (response.status === 204) {
            return undefined;
        }

        const payload = await readJsonEnvelope<TResponse>(response);

        if (response.ok && isApiSuccessEnvelope<TResponse>(payload)) {
            return payload;
        }

        if (!response.ok && isApiErrorEnvelope(payload)) {
            throw new ApiClientError(
                response.status,
                response.headers,
                payload,
            );
        }

        throw new ApiClientError(response.status, response.headers, {
            message: response.ok
                ? 'The API returned an unexpected response.'
                : 'The API request failed unexpectedly.',
        });
    }

    async function request<TResponse, TBody = unknown>(
        path: string,
        options: ApiRequestOptions<TBody> = {},
    ): Promise<TResponse | undefined> {
        const envelope = await send<TResponse, TBody>(path, options);

        return envelope?.data;
    }

    function get<TResponse>(
        path: string,
        options?: Omit<ApiRequestOptions, 'method' | 'body'>,
    ) {
        return request<TResponse>(path, {
            ...options,
            method: 'GET',
        });
    }

    function post<TResponse, TBody = unknown>(
        path: string,
        body?: TBody,
        options?: Omit<ApiRequestOptions<TBody>, 'method' | 'body'>,
    ) {
        return request<TResponse, TBody>(path, {
            ...options,
            body,
            method: 'POST',
        });
    }

    function put<TResponse, TBody = unknown>(
        path: string,
        body?: TBody,
        options?: Omit<ApiRequestOptions<TBody>, 'method' | 'body'>,
    ) {
        return request<TResponse, TBody>(path, {
            ...options,
            body,
            method: 'PUT',
        });
    }

    function patch<TResponse, TBody = unknown>(
        path: string,
        body?: TBody,
        options?: Omit<ApiRequestOptions<TBody>, 'method' | 'body'>,
    ) {
        return request<TResponse, TBody>(path, {
            ...options,
            body,
            method: 'PATCH',
        });
    }

    function destroy<TResponse = void>(
        path: string,
        options?: Omit<ApiRequestOptions, 'method' | 'body'>,
    ) {
        return request<TResponse>(path, {
            ...options,
            method: 'DELETE',
        });
    }

    return {
        authMode,
        destroy,
        get,
        patch,
        post,
        put,
        request,
        send,
    };
}

async function createRequest<TBody>(
    baseUrl: string,
    authMode: ApiClientAuthMode,
    requestConfig: RequestConfig,
    options: ApiRequestOptions<TBody>,
    defaultHeaders: Headers,
): Promise<Request> {
    const url = new URL(
        buildPath(baseUrl, requestConfig.path),
        window.location.origin,
    );

    appendQuery(url.searchParams, options.query ?? {});

    const headers = new Headers(defaultHeaders);
    headers.set('Accept', 'application/json');
    headers.set('X-Requested-With', 'XMLHttpRequest');

    if (authMode === 'authenticated') {
        const accessToken = await resolveApiAccessToken();

        if (!accessToken) {
            throw new MissingApiAccessTokenError();
        }

        headers.set('Authorization', `Bearer ${accessToken}`);
    }

    if (options.headers) {
        new Headers(options.headers).forEach((value, key) => {
            headers.set(key, value);
        });
    }

    const init: RequestInit = {
        body: prepareRequestBody(options.body, headers),
        headers,
        method: requestConfig.method,
        signal: options.signal,
    };

    return new Request(url, init);
}

function buildPath(baseUrl: string, path: string): string {
    const normalizedBaseUrl = baseUrl.endsWith('/')
        ? baseUrl.slice(0, -1)
        : baseUrl;
    const normalizedPath = path.startsWith('/') ? path : `/${path}`;

    return `${normalizedBaseUrl}${normalizedPath}`;
}

function appendQuery(
    searchParams: URLSearchParams,
    query: Record<string, ApiQueryValue>,
): void {
    Object.entries(query).forEach(([key, value]) => {
        if (value === null || value === undefined) {
            return;
        }

        if (Array.isArray(value)) {
            value.forEach((item) => {
                if (item === null || item === undefined) {
                    return;
                }

                searchParams.append(key, String(item));
            });

            return;
        }

        searchParams.set(key, String(value));
    });
}

function prepareRequestBody(
    body: unknown,
    headers: Headers,
): BodyInit | undefined {
    if (body === undefined || body === null) {
        return undefined;
    }

    if (
        body instanceof Blob ||
        body instanceof FormData ||
        body instanceof URLSearchParams ||
        body instanceof ArrayBuffer
    ) {
        return body;
    }

    if (ArrayBuffer.isView(body)) {
        return body as unknown as BodyInit;
    }

    headers.set('Content-Type', 'application/json');

    return JSON.stringify(body);
}

async function readJsonEnvelope<TResponse>(
    response: Response,
): Promise<ApiEnvelope<TResponse> | null> {
    const contentType = response.headers.get('content-type') ?? '';

    if (!contentType.includes('application/json')) {
        return null;
    }

    try {
        return (await response.json()) as ApiEnvelope<TResponse>;
    } catch {
        return null;
    }
}

function isApiSuccessEnvelope<TResponse>(
    payload: ApiEnvelope<TResponse> | null,
): payload is ApiSuccessEnvelope<TResponse> {
    return payload?.success === true && 'data' in payload && 'meta' in payload;
}

function isApiErrorEnvelope(
    payload: ApiEnvelope<unknown> | null,
): payload is ApiErrorEnvelope {
    return (
        payload?.success === false && 'code' in payload && 'message' in payload
    );
}
