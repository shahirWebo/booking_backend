import { reactive, readonly } from 'vue';
import { authApiService } from '@/lib/api/auth';
import type { AuthenticatedApiUser } from '@/lib/api/auth';
import { ApiClientError } from '@/lib/api/client';
import { setApiAccessTokenProvider } from '@/lib/api/tokenProvider';
import type { Auth, BrowserSessionPersistence, User } from '@/types/auth';

type BrowserSessionSnapshot = {
    version: 1;
    accessToken: string | null;
    persistence: Exclude<BrowserSessionPersistence, 'memory'>;
    preferredSurface: Auth['preferredSurface'];
    roles: string[];
    permissions: string[];
    user: User | null;
    updatedAt: string;
};

type BrowserSessionState = {
    accessToken: string | null;
    hasHydrated: boolean;
    hasRestored: boolean;
    isPersistent: boolean;
    isRestoring: boolean;
    lastRestoredAt: string | null;
    permissions: string[];
    persistence: BrowserSessionPersistence;
    preferredSurface: Auth['preferredSurface'];
    roles: string[];
    restoreFailed: boolean;
    user: User | null;
};

const STORAGE_KEY = 'booking-app.browser-session.v1';

const browserSessionState = reactive<BrowserSessionState>({
    accessToken: null,
    hasHydrated: false,
    hasRestored: false,
    isPersistent: false,
    isRestoring: false,
    lastRestoredAt: null,
    permissions: [],
    persistence: 'memory',
    preferredSurface: null,
    restoreFailed: false,
    roles: [],
    user: null,
});

let listenersRegistered = false;

setApiAccessTokenProvider(() => browserSessionState.accessToken);

export function getBrowserSessionState() {
    return readonly(browserSessionState);
}

export function initializeBrowserSession(auth: Auth): void {
    if (typeof window === 'undefined') {
        return;
    }

    hydratePersistedBrowserSession();
    syncBrowserSessionFromPage(auth);
    registerBrowserSessionListeners();
    void restorePersistedBrowserSession(auth);
}

export function syncBrowserSessionFromPage(auth: Auth): void {
    if (!auth.user) {
        if (!browserSessionState.accessToken) {
            browserSessionState.user = null;
            browserSessionState.roles = [];
            browserSessionState.permissions = [];
            browserSessionState.preferredSurface = null;
            browserSessionState.restoreFailed = false;
        }

        return;
    }

    browserSessionState.hasRestored = true;
    browserSessionState.user = auth.user;
    browserSessionState.roles = auth.roles;
    browserSessionState.permissions = auth.permissions;
    browserSessionState.preferredSurface = auth.preferredSurface;
    browserSessionState.restoreFailed = false;

    if (browserSessionState.isPersistent) {
        persistBrowserSession();
    }
}

export function persistBrowserTokenSession(options: {
    accessToken: string;
    auth: Auth;
    persistence?: Exclude<BrowserSessionPersistence, 'memory'>;
}): void {
    if (typeof window === 'undefined') {
        return;
    }

    browserSessionState.accessToken = options.accessToken;
    browserSessionState.hasRestored = options.auth.user !== null;
    browserSessionState.isPersistent = true;
    browserSessionState.persistence = options.persistence ?? 'local';
    browserSessionState.restoreFailed = false;
    browserSessionState.user = options.auth.user;
    browserSessionState.roles = options.auth.roles;
    browserSessionState.permissions = options.auth.permissions;
    browserSessionState.preferredSurface = options.auth.preferredSurface;

    persistBrowserSession();
}

export function clearBrowserSession(): void {
    if (typeof window !== 'undefined') {
        window.localStorage.removeItem(STORAGE_KEY);
        window.sessionStorage.removeItem(STORAGE_KEY);
    }

    browserSessionState.accessToken = null;
    browserSessionState.hasRestored = false;
    browserSessionState.isPersistent = false;
    browserSessionState.isRestoring = false;
    browserSessionState.lastRestoredAt = null;
    browserSessionState.permissions = [];
    browserSessionState.persistence = 'memory';
    browserSessionState.preferredSurface = null;
    browserSessionState.restoreFailed = false;
    browserSessionState.roles = [];
    browserSessionState.user = null;
}

export async function logoutBrowserSession(): Promise<void> {
    try {
        if (browserSessionState.accessToken) {
            await authApiService.logout();
        }
    } catch {
        // Local sign-out must still complete even if the network revoke fails.
    } finally {
        clearBrowserSession();
    }
}

export function resolveBrowserSessionAuth(auth: Auth): Auth {
    if (
        auth.user ||
        !browserSessionState.accessToken ||
        !browserSessionState.user
    ) {
        return auth;
    }

    return {
        ...auth,
        user: browserSessionState.user,
        roles: browserSessionState.roles,
        permissions: browserSessionState.permissions,
        preferredSurface: browserSessionState.preferredSurface,
        sessionMode: 'token',
    };
}

function hydratePersistedBrowserSession(): void {
    if (browserSessionState.hasHydrated || typeof window === 'undefined') {
        return;
    }

    const persistedSnapshot =
        readSnapshot(window.sessionStorage) ??
        readSnapshot(window.localStorage);

    browserSessionState.hasHydrated = true;

    if (!persistedSnapshot) {
        return;
    }

    browserSessionState.accessToken = persistedSnapshot.accessToken;
    browserSessionState.hasRestored = persistedSnapshot.user !== null;
    browserSessionState.isPersistent = true;
    browserSessionState.lastRestoredAt = persistedSnapshot.updatedAt;
    browserSessionState.persistence = persistedSnapshot.persistence;
    browserSessionState.permissions = persistedSnapshot.permissions;
    browserSessionState.preferredSurface = persistedSnapshot.preferredSurface;
    browserSessionState.roles = persistedSnapshot.roles;
    browserSessionState.user = persistedSnapshot.user;
}

async function restorePersistedBrowserSession(auth: Auth): Promise<void> {
    if (
        auth.user ||
        browserSessionState.hasRestored ||
        browserSessionState.isRestoring ||
        !browserSessionState.accessToken
    ) {
        return;
    }

    browserSessionState.isRestoring = true;
    browserSessionState.restoreFailed = false;

    try {
        const restoredUser = await authApiService.fetchCurrentUser();

        browserSessionState.user = normalizeAuthenticatedApiUser(restoredUser);
        browserSessionState.hasRestored = true;
        browserSessionState.restoreFailed = false;

        if (browserSessionState.isPersistent) {
            persistBrowserSession();
        }
    } catch (error) {
        browserSessionState.restoreFailed = true;

        if (
            (error instanceof ApiClientError ||
                (typeof error === 'object' &&
                    error !== null &&
                    'status' in error &&
                    (error.status === 401 || error.status === 403))) &&
            (error.status === 401 || error.status === 403)
        ) {
            clearBrowserSession();
        }
    } finally {
        browserSessionState.isRestoring = false;
    }
}

function registerBrowserSessionListeners(): void {
    if (listenersRegistered || typeof window === 'undefined') {
        return;
    }

    window.addEventListener('storage', (event) => {
        if (event.key !== STORAGE_KEY) {
            return;
        }

        browserSessionState.hasHydrated = false;
        hydratePersistedBrowserSession();
    });

    listenersRegistered = true;
}

function persistBrowserSession(): void {
    if (
        typeof window === 'undefined' ||
        !browserSessionState.isPersistent ||
        !browserSessionState.accessToken
    ) {
        return;
    }

    const storage =
        browserSessionState.persistence === 'session'
            ? window.sessionStorage
            : window.localStorage;
    const alternateStorage =
        browserSessionState.persistence === 'session'
            ? window.localStorage
            : window.sessionStorage;

    const snapshot: BrowserSessionSnapshot = {
        version: 1,
        accessToken: browserSessionState.accessToken,
        persistence:
            browserSessionState.persistence === 'session' ? 'session' : 'local',
        preferredSurface: browserSessionState.preferredSurface,
        roles: browserSessionState.roles,
        permissions: browserSessionState.permissions,
        updatedAt: new Date().toISOString(),
        user: browserSessionState.user,
    };

    storage.setItem(STORAGE_KEY, JSON.stringify(snapshot));
    alternateStorage.removeItem(STORAGE_KEY);
    browserSessionState.lastRestoredAt = snapshot.updatedAt;
}

function readSnapshot(storage: Storage): BrowserSessionSnapshot | null {
    const rawSnapshot = storage.getItem(STORAGE_KEY);

    if (!rawSnapshot) {
        return null;
    }

    try {
        const snapshot = JSON.parse(rawSnapshot) as BrowserSessionSnapshot;

        if (snapshot.version !== 1) {
            storage.removeItem(STORAGE_KEY);

            return null;
        }

        return snapshot;
    } catch {
        storage.removeItem(STORAGE_KEY);

        return null;
    }
}

function normalizeAuthenticatedApiUser(user: AuthenticatedApiUser): User {
    return {
        id: user.id,
        name: user.name,
        email: user.email,
        email_verified_at: null,
        mobile_number: user.mobile_number,
        status: user.status,
    };
}
