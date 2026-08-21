export type ApiAccessTokenProvider = () =>
    string | null | undefined | Promise<string | null | undefined>;

let apiAccessTokenProvider: ApiAccessTokenProvider = () => null;

export function setApiAccessTokenProvider(
    provider: ApiAccessTokenProvider,
): void {
    apiAccessTokenProvider = provider;
}

export function resetApiAccessTokenProvider(): void {
    apiAccessTokenProvider = () => null;
}

export async function resolveApiAccessToken(): Promise<string | null> {
    const token = await apiAccessTokenProvider();

    return token ?? null;
}
