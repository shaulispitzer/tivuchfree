<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineProps<{
    status?: string;
}>();

const { t } = useI18n();
</script>

<template>
    <AuthLayout
        :title="t('auth.verifyEmailTitle')"
        :description="t('auth.verifyEmailDescription')"
    >
        <Head :title="t('auth.verifyEmailHeadTitle')" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ t('auth.verificationLinkSent') }}
        </div>

        <Form
            v-bind="send.form()"
            class="flex flex-col items-center space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <Spinner v-if="processing" />
                {{ t('auth.resendVerificationEmail') }}
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
            >
                {{ t('auth.logout') }}
            </TextLink>
        </Form>
    </AuthLayout>
</template>
