<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { index } from '@/routes/properties';
import { confirmUnsubscribe } from '@/routes/subscriptions';

const props = defineProps<{
    email: string;
    token: string;
}>();

const { t } = useI18n();

const confirming = ref(false);

function confirm(): void {
    confirming.value = true;
    router.post(
        confirmUnsubscribe.url(props.token),
        {},
        {
            onFinish: () => (confirming.value = false),
        },
    );
}

function cancel(): void {
    router.visit(index());
}
</script>

<template>
    <Head :title="t('subscription.unsubscribeConfirm')" />
    <div class="mx-auto max-w-md px-4 py-12">
        <h1 class="mb-4 text-xl font-semibold">
            {{ t('subscription.unsubscribeConfirm') }}
        </h1>
        <p class="mb-6 text-muted-foreground">
            {{ t('subscription.unsubscribeConfirm') }}
            <strong>{{ props.email }}</strong
            >?
        </p>
        <div class="flex gap-3">
            <Button variant="outline" @click="cancel">
                {{ t('common.no') }}
            </Button>
            <Button
                variant="destructive"
                :disabled="confirming"
                @click="confirm"
            >
                {{ confirming ? t('common.sending') : t('common.yes') }}
            </Button>
        </div>
    </div>
</template>
