import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MobileSwipeActions from '@/components/mobile/MobileSwipeActions.vue';
import MobileTabs from '@/components/mobile/MobileTabs.vue';

describe('mobile primitives', () => {
    it('updates the selected tab when a tab is pressed', async () => {
        const wrapper = mount(MobileTabs, {
            props: {
                items: [
                    { key: 'overview', label: 'Overview' },
                    { key: 'activity', label: 'Activity' },
                ],
                modelValue: 'overview',
                'onUpdate:modelValue': (value: string) =>
                    wrapper.setProps({ modelValue: value }),
            },
        });

        await wrapper.get('button:last-child').trigger('click');

        expect(wrapper.props('modelValue')).toBe('activity');
    });

    it('emits an action after reveal action is pressed', async () => {
        const wrapper = mount(MobileSwipeActions, {
            props: {
                actions: [
                    { key: 'edit', label: 'Edit' },
                    { key: 'cancel', label: 'Cancel', destructive: true },
                ],
            },
            slots: {
                default: '<p>Row content</p>',
            },
        });

        await wrapper.get('button[aria-hidden="false"], button').trigger('click');
        await wrapper.findAll('button')[2]?.trigger('click');

        expect(wrapper.emitted('action')?.[0]).toEqual(['edit']);
    });
});
