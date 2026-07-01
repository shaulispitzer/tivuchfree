<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, index, update } from '@/routes/admin/neighbourhoods';

type Neighbourhood = {
    id: number;
    name: {
        en: string;
        he: string;
    };
    streets_count: number;
};

defineProps({
    neighbourhood: {
        type: Object as PropType<Neighbourhood>,
        required: true,
    },
});
</script>

<template>
    <Head title="Edit neighbourhood" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Edit neighbourhood</h1>
        <Button as-child variant="secondary">
            <Link :href="index()">Back to list</Link>
        </Button>
    </div>

    <Form
        v-bind="update.form(neighbourhood.id)"
        class="mt-6 space-y-6"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="name_en">Name (English)</Label>
            <Input
                id="name_en"
                name="name[en]"
                :default-value="neighbourhood.name.en"
                required
            />
            <InputError :message="errors['name.en']" />
        </div>

        <div class="grid gap-2">
            <Label for="name_he">Name (Hebrew)</Label>
            <Input
                id="name_he"
                name="name[he]"
                :default-value="neighbourhood.name.he"
                required
                dir="rtl"
            />
            <InputError :message="errors['name.he']" />
        </div>

        <p
            v-if="neighbourhood.streets_count > 0"
            class="text-sm text-muted-foreground"
        >
            This neighbourhood is used by {{ neighbourhood.streets_count }}
            street(s) and cannot be deleted until those streets are reassigned.
        </p>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Save</Button>
            <Form
                v-if="neighbourhood.streets_count === 0"
                v-bind="destroy.form(neighbourhood.id)"
                class="inline"
            >
                <Button type="submit" variant="destructive">Delete</Button>
            </Form>
        </div>
    </Form>
</template>
