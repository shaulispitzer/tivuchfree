<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthCardLayout.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayout :title="title" :description="description">
        <template v-if="$slots.banner" #banner>
            <slot name="banner" />
        </template>
        <slot />
    </AuthLayout>
</template>
