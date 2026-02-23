<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { create, destroy, edit, importMethod } from '@/routes/admin/streets';

type Street = {
    id: number;
    neighbourhood: string;
    name: {
        en: string;
        he: string;
    };
    translatedName: string;
};

defineProps({
    streets: {
        type: Array as PropType<Street[]>,
        required: true,
    },
});
</script>

<template>
    <Head title="Streets" />

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold">Streets</h1>
        <div class="flex items-center gap-2">
            <Form
                v-bind="importMethod.form()"
                class="flex items-center gap-2"
                enctype="multipart/form-data"
                v-slot="{ errors, processing }"
            >
                <input
                    type="file"
                    name="file"
                    accept=".xlsx,.csv"
                    required
                    class="text-sm file:mr-2 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium file:text-secondary-foreground hover:file:bg-secondary/80"
                />
                <Button
                    type="submit"
                    variant="secondary"
                    :disabled="processing"
                >
                    Import Streets
                </Button>
                <InputError :message="errors.file" />
            </Form>
            <Button as-child>
                <Link :href="create()">Add street</Link>
            </Button>
        </div>
    </div>

    <div v-if="streets.length === 0" class="mt-6 text-sm text-muted-foreground">
        No streets have been added yet.
    </div>

    <div v-else class="mt-6 overflow-x-auto rounded-lg border border-input">
        <table class="w-full text-sm">
            <thead class="bg-muted/40 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Neighbourhood</th>
                    <th class="px-4 py-3 font-medium">Name (EN)</th>
                    <th class="px-4 py-3 font-medium">Name (HE)</th>
                    <th class="px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="street in streets"
                    :key="street.id"
                    class="border-t border-input"
                >
                    <td class="px-4 py-3">{{ street.neighbourhood }}</td>
                    <td class="px-4 py-3">{{ street.name.en }}</td>
                    <td class="px-4 py-3">{{ street.name.he }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <Button variant="secondary" size="sm" as-child>
                                <Link :href="edit(street.id)">Edit</Link>
                            </Button>
                            <Form v-bind="destroy.form(street.id)">
                                <Button variant="destructive" size="sm">
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
