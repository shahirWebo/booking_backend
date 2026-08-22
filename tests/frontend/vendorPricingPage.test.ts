import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';
import PricingPage from '@/pages/vendor/turfs/Pricing.vue';

const inertia = vi.hoisted(() => ({
    delete: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { delete: inertia.delete, put: inertia.put },
    useForm: (initial: Record<string, unknown>) =>
        reactive({
            ...initial,
            errors: {},
            processing: false,
            clearErrors: vi.fn(),
            post: inertia.post,
        }),
}));

const props = {
    turf: {
        id: 1,
        name: 'Arena One',
        location_name: 'Central Club',
        timezone: 'Asia/Kolkata',
        default_slot_duration_minutes: 60,
    },
    pricing_rules: [
        {
            id: 1,
            rule_type: 'base' as const,
            price_minor: 10000,
            price: '100.00',
            currency: 'INR',
            priority: 100,
            effective_from_date: null,
            effective_until_date: null,
            weekday: null,
            special_date: null,
            starts_at_time: null,
            ends_at_time: null,
            ends_next_day: false,
            is_active: true,
            update_url: '/rules/1',
            delete_url: '/rules/1',
        },
    ],
    routes: {
        back: '/edit',
        availability: '/availability',
        store: '/rules',
        slots: '/slots',
        quote: '/quote',
    },
};

describe('vendor turf pricing page', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('exposes the pricing layer editors and persists a new rule', async () => {
        const wrapper = mount(PricingPage, { props });

        expect(wrapper.text()).toContain('Base');
        expect(wrapper.text()).toContain('Peak hours');
        expect(wrapper.text()).toContain('Weekend');
        expect(wrapper.text()).toContain('Special date');

        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'Peak hours')
            ?.trigger('click');
        await wrapper.find('form').trigger('submit');

        expect(wrapper.text()).toContain('Peak window');
        expect(inertia.post).toHaveBeenCalledWith('/rules', expect.any(Object));
    });

    it('loads available slots and calculates a server-backed preview', async () => {
        vi.stubGlobal(
            'fetch',
            vi
                .fn()
                .mockResolvedValueOnce({
                    ok: true,
                    json: async () => ({
                        slots: [
                            {
                                starts_at: '2026-09-01T03:30:00.000000Z',
                                ends_at: '2026-09-01T04:30:00.000000Z',
                                starts_at_time: '09:00:00',
                                ends_at_time: '10:00:00',
                            },
                        ],
                    }),
                })
                .mockResolvedValueOnce({
                    ok: true,
                    json: async () => ({
                        quote: {
                            total_minor: 10000,
                            currency: 'INR',
                            slots: [{ price_minor: 10000, pricing_rule_id: 1 }],
                        },
                    }),
                }),
        );
        const wrapper = mount(PricingPage, { props });

        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Load slots'))
            ?.trigger('click');
        await vi.waitFor(() => expect(wrapper.text()).toContain('09:00-10:00'));

        await wrapper
            .findAll('button')
            .find((button) => button.text() === '09:00-10:00')
            ?.trigger('click');
        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Calculate'))
            ?.trigger('click');

        await vi.waitFor(() => expect(wrapper.text()).toContain('Rs 100.00'));
        expect(fetch).toHaveBeenCalledTimes(2);
    });
});
