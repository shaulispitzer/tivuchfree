<template>
    <Modal
        :open="open"
        :as-page="true"
        :title="t('common.keepPropertyAvailableTitle')"
        :confirm-label="t('common.keepNotTaken')"
        :processing="processing"
        @confirm="confirm"
    >
        <p
            class="text-sm text-muted-foreground"
            v-html="t('common.keepPropertyAvailableBody')"
        />
    </Modal>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { keepAvailable } from '@/actions/App/Http/Controllers/PropertyController';
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
        keepAvailable(props.propertyId).url,
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
