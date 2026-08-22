import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CustomerHomePage from '@/pages/customer/Home.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: {
        props: ['href'],
        template:
            '<a :href="typeof href === \'string\' ? href : href.url"><slot /></a>',
    },
    usePage: () => ({ props: { auth: { user: { name: 'Surya Kumar' } } } }),
}));

describe('customer home page', () => {
    it('renders the mobile discovery entry points', () => {
        const wrapper = mount(CustomerHomePage, {
            props: {
                sports: [
                    { id: 9, name: 'Football', code: 'football' },
                    { id: 12, name: 'Badminton', code: 'badminton' },
                ],
                nearbyTurfs: [
                    {
                        id: 7,
                        name: 'Rivershore Arena',
                        distance_meters: null,
                        location: {
                            locality: 'Indiranagar',
                            city: 'Bengaluru',
                        },
                        pricing_summary: {
                            currency: 'INR',
                            starting_price: '500.00',
                        },
                        detail_url: '/customer/turfs/7',
                    },
                ],
            },
        });

        expect(wrapper.text()).toContain('Hi, Surya');
        expect(wrapper.text()).toContain('What sport are you looking for?');
        expect(wrapper.text()).toContain('Find a court for tonight');
        expect(wrapper.text()).toContain('Categories');
        expect(wrapper.text()).toContain('Football');
        expect(wrapper.text()).toContain('Badminton');
        expect(wrapper.text()).toContain('Nearby arenas');
        expect(wrapper.text()).toContain('Rivershore Arena');
        expect(wrapper.get('a[href="/customer/turfs/7"]')).toBeDefined();
        expect(wrapper.text()).toContain('Community');
        expect(wrapper.get('a[href="/customer/search"]')).toBeDefined();
    });
});
