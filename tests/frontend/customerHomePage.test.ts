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
        const wrapper = mount(CustomerHomePage);

        expect(wrapper.text()).toContain('Hi, Surya');
        expect(wrapper.text()).toContain('What sport are you looking for?');
        expect(wrapper.text()).toContain('Find a court for tonight');
        expect(wrapper.text()).toContain('Categories');
        expect(wrapper.text()).toContain('Nearby arenas');
        expect(wrapper.text()).toContain('Community');
        expect(wrapper.get('a[href="/customer/search"]')).toBeDefined();
    });
});
