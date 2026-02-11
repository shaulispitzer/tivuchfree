<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import { Button } from '@/components/ui/button';
import { destroy } from '@/routes/admin/properties';

type Property = {
    id: number;
    street: string;
    building_number: number;
    floor: string;
    bedrooms: number;
    type: string;
    user: {
        name: string;
        email: string;
    };
};

defineProps({
    properties: {
        type: Array as PropType<Property[]>,
        required: true,
    },
});
</script>

<template>
    <Head title="Admin - Properties" />

    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Properties</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Manage all properties. Only admins can access this page.
            </p>
        </div>

        <div v-if="properties.length === 0" class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground">
            No properties have been listed yet.
        </div>

        <div v-else class="overflow-hidden rounded-lg border border-input shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Street</th>
                        <th class="px-4 py-3 text-left font-medium">Building</th>
                        <th class="px-4 py-3 text-left font-medium">Floor</th>
                        <th class="px-4 py-3 text-left font-medium">Bedrooms</th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Owner</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-input">
                    <tr
                        v-for="property in properties"
                        :key="property.id"
                        class="bg-background transition-colors hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">{{ property.street }}</td>
                        <td class="px-4 py-3">{{ property.building_number }}</td>
                        <td class="px-4 py-3">{{ property.floor }}</td>
                        <td class="px-4 py-3">{{ property.bedrooms }}</td>
                        <td class="px-4 py-3">{{ property.type }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{ property.user?.name }}</span>
                            <span v-if="property.user?.email" class="block text-xs text-muted-foreground">
                                {{ property.user.email }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Form v-bind="destroy.form(property.id)">
                                <Button variant="destructive" size="sm">
                                    Delete
                                </Button>
                            </Form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
