<template>
    <Modal
        :open="open"
        :as-page="true"
        :title="t('common.confirmReportedTakenTitle')"
        :confirm-label="t('common.confirmTaken')"
        :processing="processing"
        @confirm="confirm"
    >
        <p class="text-sm text-muted-foreground">
            {{ t('common.confirmReportedTakenBody') }}
        </p>
    </Modal>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { confirmReportedTaken } from '@/actions/App/Http/Controllers/PropertyController';
import { useModal } from '../../../../vendor/emargareten/inertia-modal';

const props = defineProps<{
    propertyId: number;
}>();

const { t } = useI18n();
const { close: closePage } = useModal();
const open = ref(true);
const processing = ref(false);

function confirm(): void {
    processing.value = true;
    router.post(
        confirmReportedTaken(props.propertyId).url,
        {},
        {
            onFinish: () => {
                processing.value = false;
            },
            onError: () => {
                closePage();
            },
        },
    );
}
</script>
