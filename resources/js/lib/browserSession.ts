import { reactive, readonly } from 'vue';
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
    isPersistent: boolean;
    lastRestoredAt: string | null;
    permissions: string[];
    persistence: BrowserSessionPersistence;
    preferredSurface: Auth['preferredSurface'];
    roles: string[];
    user: User | null;
};

const STORAGE_KEY = 'booking-app.browser-session.v1';

const browserSessionState = reactive<BrowserSessionState>({
    accessToken: null,
    hasHydrated: false,
    isPersistent: false,
    lastRestoredAt: null,
    permissions: [],
    persistence: 'memory',
    preferredSurface: null,
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
}

export function syncBrowserSessionFromPage(auth: Auth): void {
    if (!auth.user) {
        if (!browserSessionState.accessToken) {
            browserSessionState.user = null;
            browserSessionState.roles = [];
            browserSessionState.permissions = [];
            browserSessionState.preferredSurface = null;
        }

        return;
    }

    browserSessionState.user = auth.user;
    browserSessionState.roles = auth.roles;
    browserSessionState.permissions = auth.permissions;
    browserSessionState.preferredSurface = auth.preferredSurface;

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
    browserSessionState.isPersistent = true;
    browserSessionState.persistence = options.persistence ?? 'local';
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
    browserSessionState.isPersistent = false;
    browserSessionState.lastRestoredAt = null;
    browserSessionState.persistence = 'memory';
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
    browserSessionState.isPersistent = true;
    browserSessionState.lastRestoredAt = persistedSnapshot.updatedAt;
    browserSessionState.persistence = persistedSnapshot.persistence;
    browserSessionState.permissions = persistedSnapshot.permissions;
    browserSessionState.preferredSurface = persistedSnapshot.preferredSurface;
    browserSessionState.roles = persistedSnapshot.roles;
    browserSessionState.user = persistedSnapshot.user;
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
