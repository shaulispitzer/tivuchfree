<script setup lang="ts">
import type { PropType } from 'vue';
import type { PropertyFilterState } from '@/components/properties/PropertyFilters.vue';
import SubscriptionSettings from '@/components/properties/SubscriptionSettings.vue';
import { index } from '@/routes/properties';
import { saveFilters } from '@/routes/subscriptions';

type Option = { value: string; label: string };

const props = defineProps({
    subscription: {
        type: Object as PropType<{
            token: string;
            email: string;
            filters: PropertyFilterState;
        }>,
        required: true,
    },
    neighbourhood_options: {
        type: Array as PropType<string[]>,
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

const filters = ref<PropertyFilterState>({ ...props.subscription.filters });
const subscriptionSettingsRef = ref<InstanceType<typeof SubscriptionSettings> | null>(
    null,
);
const processing = ref(false);

function updateFilters(value: PropertyFilterState): void {
    filters.value = { ...value, neighbourhoods: [...(value.neighbourhoods ?? [])] };
}

function submit(): void {
    processing.value = true;
    const filtersToSubmit =
        subscriptionSettingsRef.value?.getFilters() ?? filters.value;
    router.post(
        saveFilters.url(props.subscription.token),
        { filters: filtersToSubmit },
        {
            preserveScroll: true,
            onFinish: () => (processing.value = false),
        },
    );
}
</script>

<template>
    <Head :title="t('subscription.updateFilters')" />
    <div class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="mb-2 text-2xl font-bold">
            {{ t('subscription.updateFilters') }}
        </h1>
        <p class="mb-2 text-muted-foreground">
            {{ t('subscription.updateFiltersIntro') }}
        </p>
        <p class="mb-6 text-muted-foreground">
            {{ t('common.email') }}: <span class="font-medium text-foreground">{{ subscription.email }}</span>
        </p>
        <form @submit.prevent="submit" class="space-y-6">
            <SubscriptionSettings
                ref="subscriptionSettingsRef"
                :filters="filters"
                :neighbourhood_options="neighbourhood_options"
                :furnished_options="furnished_options"
                :type_options="type_options"
                @update:filters="updateFilters"
            />
            <div class="flex gap-3">
                <Button type="button" variant="outline" as-child>
                    <Link :href="index()">{{ t('common.back') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ processing ? t('common.sending') : t('common.save') }}
                </Button>
            </div>
        </form>
    </div>
</template>
