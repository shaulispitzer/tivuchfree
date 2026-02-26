<script setup lang="ts">
import { home } from '@/routes';
</script>

<template>
    <img
        src="@assets/logo.png"
        alt=""
        class="max-h-12 w-auto object-contain p-1 sm:max-h-14"
        v-bind="$attrs"
    />
</template>
