<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import PropertyCard from '@/components/PropertyCard.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/properties';

const props = defineProps({
    properties: {
        type: Array as PropType<Array<App.Data.PropertyData>>,
        required: true,
    },
    can_create: {
        type: Boolean,
        required: true,
    },
    filters: {
        type: Object as PropType<{
            neighbourhood: string | null;
        }>,
        required: true,
    },
    neighbourhood_options: {
        type: Array as PropType<string[]>,
        required: true,
    },
});

const filters = ref({
    neighbourhood: props.filters.neighbourhood ?? '',
});

watch(
    () => filters.value.neighbourhood,
    (neighbourhood) => {
        const query =
            neighbourhood && neighbourhood.trim() !== ''
                ? { neighbourhood }
                : {};

        router.get(index(), query, {
            preserveScroll: true,
            preserveState: false,
        });
    },
);
</script>

<template>
    <Head title="Properties" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Properties</h1>
        <Button v-if="can_create" as-child>
            <Link :href="create()">Add property</Link>
        </Button>
    </div>
    <form>
        <select name="neighbourhood" v-model="filters.neighbourhood">
            <option value="">All</option>
            <option
                v-for="neighbourhood in neighbourhood_options"
                :key="neighbourhood"
                :value="neighbourhood"
            >
                {{ neighbourhood }}
            </option>
        </select>
    </form>
    <div
        v-if="properties.length === 0"
        class="mt-6 text-sm text-muted-foreground"
    >
        No properties have been added yet.
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        <PropertyCard
            v-for="property in properties"
            :key="property.id"
            :property="property"
        />
    </div>
</template>
