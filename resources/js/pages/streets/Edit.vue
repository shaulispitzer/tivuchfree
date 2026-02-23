<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, update } from '@/routes/admin/streets';

type Option = {
    value: string;
    label: string;
};

type Street = {
    id: number;
    neighbourhood: string;
    name: {
        en: string;
        he: string;
    };
};

defineProps({
    street: {
        type: Object as PropType<Street>,
        required: true,
    },
    neighbourhoods: {
        type: Array as PropType<Option[]>,
        required: true,
    },
});
</script>

<template>
    <Head title="Edit street" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Edit street</h1>
        <Button as-child variant="secondary">
            <Link :href="index()">Back to list</Link>
        </Button>
    </div>

    <Form
        v-bind="update.form(street.id)"
        class="mt-6 space-y-6"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="neighbourhood">Neighbourhood</Label>
            <select
                id="neighbourhood"
                name="neighbourhood"
                required
                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                :value="street.neighbourhood"
            >
                <option value="" disabled>Select neighbourhood</option>
                <option
                    v-for="option in neighbourhoods"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <InputError :message="errors.neighbourhood" />
        </div>

        <div class="grid gap-2">
            <Label for="name_en">Name (English)</Label>
            <Input
                id="name_en"
                name="name[en]"
                :default-value="street.name.en"
                required
            />
            <InputError :message="errors['name.en']" />
        </div>

        <div class="grid gap-2">
            <Label for="name_he">Name (Hebrew)</Label>
            <Input
                id="name_he"
                name="name[he]"
                :default-value="street.name.he"
                required
            />
            <InputError :message="errors['name.he']" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Update</Button>
        </div>
    </Form>
</template>
