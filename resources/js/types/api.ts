export type ApiMeta = {
    request_id: string;
    [key: string]: unknown;
};

export type ApiLinks = Record<string, string | null>;

export type ApiFieldErrors = Record<string, string[]>;

export type ApiSuccessEnvelope<TData> = {
    success: true;
    data: TData;
    meta: ApiMeta;
    message?: string;
    links?: ApiLinks;
};

export type ApiErrorEnvelope = {
    success: false;
    code: string;
    message: string;
    meta: ApiMeta;
    errors?: ApiFieldErrors;
};

export type ApiEnvelope<TData> = ApiSuccessEnvelope<TData> | ApiErrorEnvelope;
