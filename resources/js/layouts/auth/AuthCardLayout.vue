<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { home, locale } from '@/routes';
import FlagpackGbUkm from '~icons/flagpack/gb-ukm';
import TwemojiFlagIsrael from '~icons/twemoji/flag-israel';

defineProps<{
    title?: string;
    description?: string;
}>();

const { t } = useI18n();
const page = usePage();
const currentLocale = computed(() => (page.props.locale as string) ?? 'en');
const localeToggleFlag = computed(() =>
    currentLocale.value === 'he' ? FlagpackGbUkm : TwemojiFlagIsrael,
);
const localeToggleLabel = computed(() =>
    currentLocale.value === 'he'
        ? t('auth.switchToEnglish')
        : t('auth.switchToHebrew'),
);
const handleLocaleChange = () => {
    router.post(
        locale(),
        { locale: currentLocale.value === 'en' ? 'he' : 'en' },
        { preserveScroll: true, preserveState: false },
    );
};
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10"
    >
        <div class="flex w-full max-w-md flex-col gap-6">
            <div class="flex items-center justify-between">
                <Link
                    :href="home()"
                    class="flex items-center gap-2 font-medium"
                >
                    <div class="flex items-center justify-center">
                        <AppLogoIcon />
                    </div>
                </Link>
                <Button
                    variant="ghost"
                    size="icon"
                    :aria-label="localeToggleLabel"
                    @click="handleLocaleChange"
                >
                    <component :is="localeToggleFlag" class="h-5 w-5" />
                </Button>
            </div>

            <div class="flex flex-col gap-6">
                <Card class="rounded-xl">
                    <CardHeader class="px-10 pt-8 pb-0 text-center">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-10 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
