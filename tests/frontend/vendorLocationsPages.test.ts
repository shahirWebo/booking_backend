import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';
import FormPage from '@/pages/vendor/locations/Form.vue';
import IndexPage from '@/pages/vendor/locations/Index.vue';

const inertiaMocks = vi.hoisted(() => ({
    post: vi.fn(),
    useForm: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        props: ['title'],
        template: '<div><slot /></div>',
    },
    Link: {
        name: 'Link',
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
    router: {
        post: inertiaMocks.post,
    },
    useForm: inertiaMocks.useForm,
}));

function buildFormState(initial: Record<string, unknown>) {
    return reactive({
        ...initial,
        errors: {},
        hasErrors: false,
        processing: false,
        post: vi.fn(),
        put: vi.fn(),
    });
}

describe('vendor location pages', () => {
    it('lets the operator pin coordinates and toggle gallery images', async () => {
        inertiaMocks.useForm.mockImplementation((initial) => buildFormState(initial));

        const wrapper = mount(FormPage, {
            props: {
                mode: 'create',
                vendor: {
                    id: 10,
                    display_name: 'Blue Five Sports',
                    legal_name: 'Blue Five Sports Private Limited',
                },
                location: null,
                amenities: [
                    {
                        id: 1,
                        name: 'Parking',
                        code: 'parking',
                    },
                ],
                available_images: [
                    {
                        id: 11,
                        original_name: 'hero-shot.jpg',
                        canonical_extension: 'jpg',
                        size_bytes: 120000,
                        status: 'ready',
                        attached_to_current_location: false,
                    },
                ],
                routes: {
                    index: '/vendor/locations',
                    submit: '/vendor/locations',
                },
            },
        });

        await wrapper.get('[data-test="image-library-11"]').trigger('click');
        await nextTick();

        expect(wrapper.text()).toContain('hero-shot.jpg');
        expect(wrapper.text()).toContain('1 selected');

        const map = wrapper.get('[data-test="coordinate-map"]');
        vi.spyOn(map.element, 'getBoundingClientRect').mockReturnValue({
            x: 0,
            y: 0,
            top: 0,
            left: 0,
            bottom: 200,
            right: 300,
            width: 300,
            height: 200,
            toJSON: () => '',
        });

        await map.trigger('click', {
            clientX: 225,
            clientY: 50,
        });
        await nextTick();

        expect((wrapper.get('#latitude').element as HTMLInputElement).value).toBe(
            '45.000000',
        );
        expect((wrapper.get('#longitude').element as HTMLInputElement).value).toBe(
            '90.000000',
        );
    });

    it('renders the upgraded list summary and edit CTA', () => {
        const wrapper = mount(IndexPage, {
            props: {
                vendor: {
                    id: 10,
                    display_name: 'Blue Five Sports',
                    legal_name: 'Blue Five Sports Private Limited',
                },
                locations: [
                    {
                        id: 41,
                        name: 'Indiranagar Arena',
                        city: 'Bengaluru',
                        state: 'Karnataka',
                        timezone: 'Asia/Kolkata',
                        status: 'active',
                        latitude: 12.97,
                        longitude: 77.59,
                        amenity_ids: [1, 2],
                        operating_hours: [
                            {
                                weekday: 1,
                                sequence: 1,
                                opens_at_time: '06:00',
                                closes_at_time: '22:00',
                                ends_next_day: false,
                            },
                        ],
                        images: [{ id: 9 }],
                        routes: {
                            edit: '/vendor/locations/41/edit',
                        },
                    },
                ],
                routes: {
                    create: '/vendor/locations/create',
                    index: '/vendor/locations',
                },
            },
        });

        expect(wrapper.text()).toContain('Locations');
        expect(wrapper.text()).toContain('Coordinates set');
        expect(wrapper.text()).toContain('Edit location');
    });
});
