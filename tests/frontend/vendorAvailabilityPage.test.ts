import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';
import AvailabilityPage from '@/pages/vendor/turfs/Availability.vue';

const inertia = vi.hoisted(() => ({
    delete: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { delete: inertia.delete },
    useForm: (initial: Record<string, unknown>) =>
        reactive({
            ...initial,
            errors: {},
            processing: false,
            post: inertia.post,
            put: inertia.put,
            reset: vi.fn(),
        }),
}));

const props = {
    turf: {
        name: 'Arena One',
        location_name: 'Central Club',
        timezone: 'Asia/Kolkata',
        booking_lead_time_minutes: 30,
        advance_booking_window_days: 30,
        default_slot_duration_minutes: 60,
        availability_schedule: [
            {
                weekday: 1,
                is_active: true,
                time_ranges: [
                    {
                        starts_at_time: '09:00:00',
                        ends_at_time: '11:00:00',
                        ends_next_day: false,
                    },
                ],
            },
        ],
        slot_blocks: [],
        maintenance_blocks: [],
    },
    copy_targets: [{ id: 9, name: 'Arena Two' }],
    routes: {
        back: '/edit',
        schedule: '/schedule',
        configuration: '/configuration',
        slots: '/slots',
        slot_blocks: '/blocks',
        maintenance_blocks: '/maintenance',
        copy_schedule: '/copy',
    },
};

describe('vendor turf availability page', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('copies one weekday schedule across the week and submits it', async () => {
        const wrapper = mount(AvailabilityPage, { props });
        const copyButtons = wrapper
            .findAll('button')
            .filter((button) => button.text() === 'Copy');

        await copyButtons[0].trigger('click');
        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Save'))
            ?.trigger('click');

        expect(wrapper.text()).toContain('7 active days');
        expect(inertia.put).toHaveBeenCalledWith('/schedule');
    });

    it('renders calculated slots and exposes block and maintenance controls', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn().mockResolvedValue({
                ok: true,
                json: async () => ({
                    slots: [
                        {
                            starts_at: '2026-09-01T03:30:00Z',
                            starts_at_time: '09:00:00',
                            ends_at_time: '10:00:00',
                        },
                    ],
                }),
            }),
        );
        const wrapper = mount(AvailabilityPage, { props });

        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'Preview')
            ?.trigger('click');
        await vi.waitFor(() =>
            expect(wrapper.text()).toContain('09:00:00–10:00:00'),
        );

        expect(wrapper.text()).toContain('Block bookings');
        expect(wrapper.text()).toContain('Maintenance');
        expect(wrapper.text()).toContain('Copy to turf');
    });
});
