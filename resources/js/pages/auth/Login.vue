<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { redirect as googleRedirect } from '@/routes/auth/google';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import MaterialIconThemeGoogle from '~icons/material-icon-theme/google';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const { t } = useI18n();
</script>

<template>
    <AuthBase
        :title="t('auth.loginTitle')"
        :description="t('auth.loginDescription')"
    >
        <Head :title="t('auth.loginHeadTitle')" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>
        <Button variant="outline" class="w-full" as-child>
            <a
                :href="googleRedirect.url()"
                class="inline-flex items-center justify-center gap-2"
            >
                <MaterialIconThemeGoogle class="h-5 w-5" />
                {{ t('auth.google') }}
            </a>
        </Button>

        <div class="relative my-2">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t" />
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground">
                    {{ t('common.or') }}
                </span>
            </div>
        </div>

        <Form
            :action="store.url()"
            method="post"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.emailAddress') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">{{ t('auth.password') }}</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm"
                            :tabindex="5"
                        >
                            {{ t('auth.forgotPassword') }}
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>{{ t('auth.remember') }}</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.logIn') }}
                </Button>
            </div>

            <div
                class="text-center text-sm text-muted-foreground"
                v-if="canRegister"
            >
                {{ t('auth.dontHaveAccount') }}
                <TextLink :href="register()" :tabindex="5">{{
                    t('auth.signUp')
                }}</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
