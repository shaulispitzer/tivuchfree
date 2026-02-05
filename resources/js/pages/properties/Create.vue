<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import { index, store } from '@/routes/properties';

defineProps({
    options: {
        type: Object as PropType<App.Data.PropertyFormOptionsData>,
        required: true,
    },
});
</script>

<template>
    <Head title="Create property" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Create property</h1>
        <Button as-child variant="secondary">
            <Link :href="index()">Back to list</Link>
        </Button>
    </div>

    <Form
        v-bind="store.form()"
        enctype="multipart/form-data"
        class="mt-6 space-y-6"
        v-slot="{ errors, processing }"
    >
        <PropertyFormFields :errors="errors" :options="options" />

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Save</Button>
        </div>
    </Form>
</template>
