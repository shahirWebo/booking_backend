import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import EmptyState from '@/components/feedback/EmptyState.vue';
import ErrorState from '@/components/feedback/ErrorState.vue';
import FormFeedback from '@/components/feedback/FormFeedback.vue';

describe('feedback states', () => {
    it('renders form feedback copy', () => {
        const wrapper = mount(FormFeedback, {
            props: {
                description: 'Additional guidance.',
                message: 'Something happened.',
                variant: 'info',
            },
        });

        expect(wrapper.text()).toContain('Something happened.');
        expect(wrapper.text()).toContain('Additional guidance.');
    });

    it('renders empty state actions from the default slot', () => {
        const wrapper = mount(EmptyState, {
            props: {
                description: 'Try broadening your search.',
                title: 'No venues available',
            },
            slots: {
                default: '<button type="button">Search again</button>',
            },
        });

        expect(wrapper.text()).toContain('No venues available');
        expect(wrapper.text()).toContain('Search again');
    });

    it('emits retry from the error state action', async () => {
        const wrapper = mount(ErrorState, {
            props: {
                description: 'Retry is safe here.',
                retryLabel: 'Retry request',
                title: 'Unable to load data',
            },
        });

        await wrapper.get('button').trigger('click');

        expect(wrapper.emitted('retry')).toHaveLength(1);
    });
});
