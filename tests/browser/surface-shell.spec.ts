import { expect, test } from '@playwright/test';

const surfaces = [
    {
        path: '/customer',
        heading: 'Customer App',
        navLabel: 'Search',
    },
    {
        path: '/vendor',
        heading: 'Vendor Portal',
        navLabel: 'Hub',
    },
    {
        path: '/admin',
        heading: 'Admin Console',
        navLabel: 'Home',
    },
] as const;

for (const surface of surfaces) {
    test(`${surface.heading} renders the mobile shell without console errors`, async ({
        page,
    }) => {
        const consoleErrors: string[] = [];
        const pageErrors: string[] = [];

        page.on('console', (message) => {
            if (message.type() === 'error') {
                consoleErrors.push(message.text());
            }
        });

        page.on('pageerror', (error) => {
            pageErrors.push(error.message);
        });

        await page.goto(surface.path);

        await expect(
            page.getByRole('heading', { name: surface.heading }),
        ).toBeVisible();
        await expect(page.getByText('Session continuity')).toBeVisible();
        await expect(
            page.getByRole('link', {
                name: surface.navLabel,
                exact: true,
            }),
        ).toBeVisible();

        expect(consoleErrors).toEqual([]);
        expect(pageErrors).toEqual([]);
    });
}

test('the customer shell routes touch users into the auth flow', async ({
    page,
}) => {
    await page.goto('/customer');

    await page.getByRole('link', { name: 'Open auth flow' }).tap();

    await expect(page).toHaveURL(/\/login$/);
    await expect(
        page.getByRole('heading', { name: 'Log in to your account' }),
    ).toBeVisible();
});
