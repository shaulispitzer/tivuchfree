<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, destroy, edit } from '@/routes/admin/neighbourhoods';

type NeighbourhoodRow = {
    id: number;
    name: {
        en: string;
        he: string;
    };
    streets_count: number;
};

defineProps({
    neighbourhoods: {
        type: Array as PropType<NeighbourhoodRow[]>,
        required: true,
    },
});
</script>

<template>
    <Head title="Admin - Neighbourhoods" />

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold">Neighbourhoods</h1>
        <Button as-child>
            <Link :href="create()">Add neighbourhood</Link>
        </Button>
    </div>

    <div
        v-if="neighbourhoods.length === 0"
        class="mt-6 text-sm text-muted-foreground"
    >
        No neighbourhoods have been added yet.
    </div>

    <div v-else class="mt-6 overflow-x-auto rounded-lg border border-input">
        <table class="w-full text-sm">
            <thead class="bg-muted/40 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Name (EN)</th>
                    <th class="px-4 py-3 font-medium">Name (HE)</th>
                    <th class="px-4 py-3 font-medium">Streets</th>
                    <th class="px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="neighbourhood in neighbourhoods"
                    :key="neighbourhood.id"
                    class="border-t border-input"
                >
                    <td class="px-4 py-3">{{ neighbourhood.name.en }}</td>
                    <td class="px-4 py-3" dir="rtl">
                        {{ neighbourhood.name.he }}
                    </td>
                    <td class="px-4 py-3">{{ neighbourhood.streets_count }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <Button as-child variant="secondary" size="sm">
                                <Link :href="edit(neighbourhood.id)">Edit</Link>
                            </Button>
                            <Form
                                v-bind="destroy.form(neighbourhood.id)"
                                class="inline"
                            >
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    size="sm"
                                    :disabled="neighbourhood.streets_count > 0"
                                >
                                    Delete
                                </Button>
                            </Form>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
