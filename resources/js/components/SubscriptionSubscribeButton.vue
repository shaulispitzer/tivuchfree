<script setup lang="ts">
import { subscribe } from '@/routes';

const { t } = useI18n();
</script>

<template>
    <Teleport to="body">
        <Button
            as-child
            class="fixed top-1/2 left-9 z-50 max-w-[calc(100dvh-2rem)] origin-top-left -translate-y-1/2 rotate-90 shadow-md"
        >
            <Link :href="subscribe().url">{{
                t('subscription.subscribeToUpdates')
            }}</Link>
        </Button>
    </Teleport>
</template>
