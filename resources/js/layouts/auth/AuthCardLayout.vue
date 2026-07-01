<script setup lang="ts">
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh items-center justify-center bg-muted p-6 md:p-10"
    >
        <div class="flex w-full max-w-md flex-col items-center">
            <Link
                :href="home()"
                class="mb-8 flex items-center justify-center font-medium"
            >
                <AppLogoIcon class="h-28 w-auto max-w-full object-contain sm:h-32" />
            </Link>

            <div class="flex w-full flex-col">
                <div
                    v-if="$slots.banner"
                    class="flex items-center justify-center gap-2 rounded-t-xl bg-yellow-100 px-4 py-3 text-center text-sm font-medium text-yellow-800"
                >
                    <slot name="banner" />
                </div>
                <Card
                    :class="
                        $slots.banner
                            ? 'rounded-t-none rounded-b-xl'
                            : 'rounded-xl'
                    "
                >
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
