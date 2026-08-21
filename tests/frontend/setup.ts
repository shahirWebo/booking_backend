import { afterEach, vi } from 'vitest';

afterEach(() => {
    vi.clearAllMocks();
    window.localStorage.clear();
    window.sessionStorage.clear();
});
