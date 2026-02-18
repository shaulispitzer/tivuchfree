<script setup lang="ts">
import { home } from '@/routes';
</script>

<template>
    <Link :href="home()" class="h-full w-56">
        <img src="@assets/transparent-logo.png" v-bind="$attrs" />
    </Link>
</template>
