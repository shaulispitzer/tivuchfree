<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import type { ButtonVariants } from '@/components/ui/button';

interface Props {
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    class?: HTMLAttributes['class'];
    title?: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    processing?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Are you sure?',
    message: 'This action cannot be undone.',
});

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();

const isOpen = ref(false);

function handleCancel() {
    setTimeout(() => {
        isOpen.value = false;
        emit('cancel');
    }, 10);
}

function handleConfirm() {
    emit('confirm');
    isOpen.value = false;
}
</script>

<template>
    <Button
        :variant="variant"
        :size="size"
        :class="props.class"
        @click="isOpen = true"
    >
        <slot />
    </Button>

    <Modal
        :open="isOpen"
        :title="title"
        :confirm-label="confirmLabel"
        :cancel-label="cancelLabel"
        :processing="processing"
        @close="handleCancel"
        @confirm="handleConfirm"
    >
        <p class="text-sm text-muted-foreground">{{ message }}</p>
    </Modal>
</template>
