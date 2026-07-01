<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store } from '@/routes/admin/neighbourhoods';
</script>

<template>
    <Head title="Create neighbourhood" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Create neighbourhood</h1>
        <Button as-child variant="secondary">
            <Link :href="index()">Back to list</Link>
        </Button>
    </div>

    <Form
        v-bind="store.form()"
        class="mt-6 space-y-6"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="name_en">Name (English)</Label>
            <Input id="name_en" name="name[en]" required />
            <InputError :message="errors['name.en']" />
        </div>

        <div class="grid gap-2">
            <Label for="name_he">Name (Hebrew)</Label>
            <Input id="name_he" name="name[he]" required dir="rtl" />
            <InputError :message="errors['name.he']" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Save</Button>
        </div>
    </Form>
</template>
