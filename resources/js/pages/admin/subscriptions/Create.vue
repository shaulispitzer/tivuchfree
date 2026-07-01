<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import { store } from '@/actions/App/Http/Controllers/Admin/PropertySubscriptionController';
import type { PropertyFilterState } from '@/components/properties/PropertyFilters.vue';
import SubscriptionSettings from '@/components/properties/SubscriptionSettings.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/subscriptions';

type Option = { value: string; label: string };

const defaultFilters: PropertyFilterState = {
    neighbourhoods: [],
    hide_taken_properties: false,
    bedrooms_range: [1, 10],
    furnished: '',
    type: '',
    available_from: '',
    available_to: '',
    sort: 'newest',
};

defineProps({
    neighbourhood_options: {
        type: Array as PropType<Option[]>,
        required: true,
    },
    furnished_options: {
        type: Array as PropType<Option[]>,
        required: true,
    },
    type_options: {
        type: Array as PropType<Option[]>,
        required: true,
    },
});

const { t } = useI18n();

const filters = ref<PropertyFilterState>({ ...defaultFilters });
const subscriptionSettingsRef = ref<InstanceType<
    typeof SubscriptionSettings
> | null>(null);
const email = ref('');

const form = useForm<{
    filters: PropertyFilterState;
    email: string;
}>({
    filters: filters.value,
    email: '',
});

function updateFilters(value: PropertyFilterState): void {
    filters.value = {
        ...value,
        neighbourhoods: [...(value.neighbourhoods ?? [])],
    };
}

function submit(): void {
    const currentFilters =
        subscriptionSettingsRef.value?.getFilters() ?? filters.value;
    form.filters = { ...currentFilters };
    form.email = email.value;
    form.post(store.url(), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Admin - Add subscription" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold tracking-tight">
                Add subscription
            </h1>
            <Button as-child variant="secondary">
                <Link :href="index()">Back to list</Link>
            </Button>
        </div>

        <p class="text-sm text-muted-foreground">
            Create a subscription without email verification. A unique manage
            link will be generated for the subscriber.
        </p>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-2">
                <Label for="admin-subscription-email" requiredStar>{{
                    t('common.email')
                }}</Label>
                <Input
                    id="admin-subscription-email"
                    v-model="email"
                    type="email"
                    required
                    class="h-12 w-full max-w-md text-base"
                    :class="form.errors.email ? 'border-destructive' : ''"
                />
                <InputError :message="form.errors.email" />
            </div>

            <SubscriptionSettings
                ref="subscriptionSettingsRef"
                :filters="filters"
                :neighbourhood_options="neighbourhood_options"
                :furnished_options="furnished_options"
                :type_options="type_options"
                @update:filters="updateFilters"
            />

            <div class="flex flex-wrap gap-3">
                <Button type="submit" :disabled="form.processing">
                    {{
                        form.processing
                            ? t('common.creating')
                            : 'Create subscription'
                    }}
                </Button>
            </div>
        </form>
    </div>
</template>
