<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import PropertyFormFields from '@/components/PropertyFormFields.vue';
import { Button } from '@/components/ui/button';
import { index, update } from '@/routes/properties';

defineProps({
    property: {
        type: Object as PropType<App.Data.PropertyData>,
        required: true,
    },
    options: {
        type: Object as PropType<App.Data.PropertyFormOptionsData>,
        required: true,
    },
});
</script>

<template>
    <Head title="Edit property" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Edit property</h1>
        <Button as-child variant="secondary">
            <Link :href="index()">Back to list</Link>
        </Button>
    </div>

    <div
        v-if="property.main_image_url"
        class="mt-4 rounded-md border border-input p-4"
    >
        <p class="text-sm font-medium">Current main image</p>
        <img
            :src="property.main_image_url"
            alt="Main property image"
            class="mt-3 h-40 w-full rounded-md object-cover"
        />
    </div>

    <Form
        v-bind="update.form(property.id)"
        enctype="multipart/form-data"
        class="mt-6 space-y-6"
        v-slot="{ errors, processing }"
    >
        <PropertyFormFields
            :errors="errors"
            :options="options"
            :property="property"
        />

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Update</Button>
        </div>
    </Form>
</template>
