<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ListTree, Save } from '@lucide/vue';
import FormFeedback from '@/components/feedback/FormFeedback.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';

type AmenityRecord = {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
};

const props = defineProps<{
    mode: 'create' | 'edit';
    amenity: AmenityRecord | null;
    routes: {
        index: string;
        submit: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Amenities', href: admin.amenities.index() },
        ],
    },
});

const form = useForm({
    name: props.amenity?.name ?? '',
    code: props.amenity?.code ?? '',
    description: props.amenity?.description ?? '',
    is_active: props.amenity?.is_active ?? true,
});

function submit(): void {
    if (props.mode === 'create') {
        form.post(props.routes.submit, {
            preserveScroll: true,
        });

        return;
    }

    form.put(props.routes.submit, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="mode === 'create' ? 'Add Amenity' : 'Edit Amenity'" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <section
            class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-sidebar-accent/40 p-5 dark:border-sidebar-border"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="space-y-3">
                    <Link
                        :href="routes.index"
                        class="inline-flex items-center gap-2 text-sm font-medium text-sidebar-foreground/70 transition hover:text-sidebar-foreground"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Back to amenities
                    </Link>
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.24em] text-sidebar-foreground/60 uppercase"
                        >
                            Admin catalog
                        </p>
                        <h1
                            class="mt-2 text-2xl font-semibold tracking-tight text-sidebar-foreground"
                        >
                            {{
                                mode === 'create'
                                    ? 'Add amenity'
                                    : 'Edit amenity'
                            }}
                        </h1>
                    </div>
                </div>

                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-background"
                >
                    <ListTree class="h-5 w-5 text-muted-foreground" />
                </div>
            </div>
        </section>

        <section
            class="rounded-3xl border border-sidebar-border/70 bg-background p-5 dark:border-sidebar-border"
        >
            <form class="space-y-5" @submit.prevent="submit">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="amenity-name">Name</Label>
                        <Input
                            id="amenity-name"
                            v-model="form.name"
                            name="name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="amenity-code">Code</Label>
                        <Input
                            id="amenity-code"
                            v-model="form.code"
                            name="code"
                        />
                        <InputError :message="form.errors.code" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="amenity-description">Description</Label>
                    <textarea
                        id="amenity-description"
                        v-model="form.description"
                        name="description"
                        rows="5"
                        class="min-h-32 w-full rounded-[var(--radius-control)] border border-input bg-transparent px-4 py-3 text-sm leading-6 text-slate-900 outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <label
                    class="flex items-center gap-3 rounded-2xl border border-sidebar-border/70 bg-muted/60 px-4 py-3 text-sm text-muted-foreground dark:border-sidebar-border"
                >
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        name="is_active"
                        class="h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-emerald-500"
                    />
                    Keep this amenity active in the shared catalog
                </label>
                <InputError :message="form.errors.is_active" />

                <FormFeedback
                    v-if="form.hasErrors"
                    message="Please fix the highlighted amenity fields."
                    variant="error"
                />

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{
                            mode === 'create'
                                ? 'Create amenity'
                                : 'Save changes'
                        }}
                    </Button>
                    <Button as-child type="button" variant="outline">
                        <Link :href="routes.index">Cancel</Link>
                    </Button>
                </div>
            </form>
        </section>
    </div>
</template>
