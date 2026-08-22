import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';
import SearchPage from '@/pages/customer/Search.vue';

const inertia = vi.hoisted(() => ({
    get: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (initial: Record<string, unknown>) =>
        reactive({
            ...initial,
            errors: {},
            processing: false,
            get: inertia.get,
        }),
}));

const props = {
    filters: {
        latitude: null,
        longitude: null,
        city: null,
        locality: null,
        turf_name: null,
        sport_ids: [],
        amenity_ids: [],
        min_price: null,
        max_price: null,
        distance_meters: null,
        date: null,
        is_indoor: null,
        sort: 'recommended',
        per_page: 12,
    },
    options: {
        sports: [],
        amenities: [],
        location_areas: [
            { city: 'Bengaluru', locality: 'Indiranagar' },
            { city: 'Mumbai', locality: null },
        ],
        sorts: [
            { value: 'recommended', label: 'Recommended' },
            { value: 'distance', label: 'Distance' },
        ],
    },
    results: {
        data: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 12,
            total: 0,
            from: null,
            to: null,
        },
        links: {
            prev: null,
            next: null,
        },
    },
    routes: {
        index: '/customer/search',
    },
    sort_support: {
        rating: false,
        popularity: false,
    },
};

describe('customer search page geolocation', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
    });

    it('requests the browser location, fills coordinates, and refreshes results', async () => {
        const getCurrentPosition = vi.fn((success: PositionCallback) => {
            success({
                coords: {
                    latitude: 19.076,
                    longitude: 72.8777,
                    accuracy: 10,
                    altitude: null,
                    altitudeAccuracy: null,
                    heading: null,
                    speed: null,
                    toJSON: () => ({}),
                },
                timestamp: Date.now(),
                toJSON: () => ({}),
            } as GeolocationPosition);
        });

        Object.defineProperty(window.navigator, 'geolocation', {
            configurable: true,
            value: { getCurrentPosition },
        });

        const wrapper = mount(SearchPage, { props });
        const locationButton = wrapper
            .findAll('button')
            .find((button) => button.text().includes('Use my location'));

        await locationButton?.trigger('click');

        expect(getCurrentPosition).toHaveBeenCalledWith(
            expect.any(Function),
            expect.any(Function),
            {
                enableHighAccuracy: true,
                maximumAge: 300000,
                timeout: 10000,
            },
        );
        expect(inertia.get).toHaveBeenCalledWith(
            '/customer/search',
            expect.objectContaining({
                preserveScroll: true,
                preserveState: true,
            }),
        );
        expect(wrapper.text()).toContain(
            'Location added. Refreshing nearby turf results now.',
        );
        expect(
            (wrapper.get('input[name="latitude"]').element as HTMLInputElement)
                .value,
        ).toBe('19.076000');
        expect(
            (wrapper.get('input[name="longitude"]').element as HTMLInputElement)
                .value,
        ).toBe('72.877700');
        expect((wrapper.get('#sort').element as HTMLSelectElement).value).toBe(
            'distance',
        );
    });

    it('shows a manual-entry fallback when geolocation is unavailable', async () => {
        Object.defineProperty(window.navigator, 'geolocation', {
            configurable: true,
            value: undefined,
        });

        const wrapper = mount(SearchPage, { props });

        expect(wrapper.text()).toContain(
            'Browser location is unavailable here, so you can still enter coordinates manually.',
        );

        const locationButton = wrapper
            .findAll('button')
            .find((button) => button.text().includes('Use my location'));

        expect(locationButton?.attributes('disabled')).toBeDefined();
    });

    it('shows a calm error when permission is denied', async () => {
        const getCurrentPosition = vi.fn(
            (
                _success: PositionCallback,
                error: PositionErrorCallback | null | undefined,
            ) => {
                error?.({
                    code: 1,
                    message: 'Denied',
                    PERMISSION_DENIED: 1,
                    POSITION_UNAVAILABLE: 2,
                    TIMEOUT: 3,
                } as GeolocationPositionError);
            },
        );

        Object.defineProperty(window.navigator, 'geolocation', {
            configurable: true,
            value: { getCurrentPosition },
        });

        const wrapper = mount(SearchPage, { props });
        const locationButton = wrapper
            .findAll('button')
            .find((button) => button.text().includes('Use my location'));

        await locationButton?.trigger('click');

        expect(wrapper.text()).toContain(
            'Location permission was denied. Enter coordinates manually or allow access and try again.',
        );
        expect(inertia.get).not.toHaveBeenCalled();
    });

    it('lets customers select a listed area without coordinates', async () => {
        const wrapper = mount(SearchPage, {
            props: {
                ...props,
                filters: {
                    ...props.filters,
                    latitude: '12.971600',
                    longitude: '77.594600',
                    distance_meters: '5000',
                    sort: 'distance',
                },
            },
        });

        await wrapper.get('#manual-area').setValue('0');

        expect(
            (wrapper.get('input[name="city"]').element as HTMLInputElement)
                .value,
        ).toBe('Bengaluru');
        expect(
            (wrapper.get('input[name="locality"]').element as HTMLInputElement)
                .value,
        ).toBe('Indiranagar');
        expect(
            (wrapper.get('input[name="latitude"]').element as HTMLInputElement)
                .value,
        ).toBe('');
        expect(
            (wrapper.get('input[name="longitude"]').element as HTMLInputElement)
                .value,
        ).toBe('');
        expect(
            (
                wrapper.get('input[name="distance_meters"]')
                    .element as HTMLInputElement
            ).value,
        ).toBe('');
        expect((wrapper.get('#sort').element as HTMLSelectElement).value).toBe(
            'recommended',
        );
    });
});
