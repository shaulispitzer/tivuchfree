<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import { Button } from '@/components/ui/button';
import { markAsTaken } from '@/routes/my-properties';
import { edit } from '@/routes/properties';

defineProps({
    properties: {
        type: Array as PropType<App.Data.PropertyData[]>,
        required: true,
    },
});
</script>

<template>
    <Head title="My Properties" />

    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">My Properties</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Properties that you have posted.
            </p>
        </div>

        <div
            v-if="properties.length === 0"
            class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground"
        >
            You have not posted any properties yet.
        </div>

        <div
            v-else
            class="overflow-hidden rounded-lg border border-input shadow-sm"
        >
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Street</th>
                        <th class="px-4 py-3 text-left font-medium">
                            Building
                        </th>
                        <th class="px-4 py-3 text-left font-medium">Floor</th>
                        <th class="px-4 py-3 text-left font-medium">
                            Bedrooms
                        </th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-input">
                    <tr
                        v-for="property in properties"
                        :key="property.id"
                        class="bg-background transition-colors hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">{{ property.street }}</td>
                        <td class="px-4 py-3">
                            {{ property.building_number ?? '-' }}
                        </td>
                        <td class="px-4 py-3">{{ property.floor }}</td>
                        <td class="px-4 py-3">{{ property.bedrooms }}</td>
                        <td class="px-4 py-3">{{ property.type ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    property.taken
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-green-100 text-green-700'
                                "
                            >
                                {{ property.taken ? 'Taken' : 'Available' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button size="sm" variant="secondary" as-child>
                                    <Link :href="edit(property.id)">Edit</Link>
                                </Button>

                                <Button v-if="!property.taken" size="sm" as-child>
                                    <Link
                                        :href="markAsTaken(property.id)"
                                        method="patch"
                                        as="button"
                                    >
                                        Mark as taken
                                    </Link>
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
