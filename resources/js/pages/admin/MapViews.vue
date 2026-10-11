<script setup lang="ts">
import type { PropType } from 'vue';

const { t } = useI18n();

type Summary = {
    today: number;
    last_7_days: number;
    last_30_days: number;
    all_time: number;
};

type DailyRow = {
    date: string;
    visitors: number;
};

defineProps({
    summary: {
        type: Object as PropType<Summary>,
        required: true,
    },
    daily: {
        type: Array as PropType<DailyRow[]>,
        required: true,
    },
});
</script>

<template>
    <Head :title="t('common.mapViews')" />

    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ t('common.mapViews') }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ t('common.mapViewsDescription') }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-input p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">
                    {{ t('common.today') }}
                </p>
                <p class="mt-1 text-2xl font-semibold tracking-tight">
                    {{ summary.today }}
                </p>
            </div>
            <div class="rounded-lg border border-input p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">
                    {{ t('common.last7Days') }}
                </p>
                <p class="mt-1 text-2xl font-semibold tracking-tight">
                    {{ summary.last_7_days }}
                </p>
            </div>
            <div class="rounded-lg border border-input p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">
                    {{ t('common.last30Days') }}
                </p>
                <p class="mt-1 text-2xl font-semibold tracking-tight">
                    {{ summary.last_30_days }}
                </p>
            </div>
            <div class="rounded-lg border border-input p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">
                    {{ t('common.allTime') }}
                </p>
                <p class="mt-1 text-2xl font-semibold tracking-tight">
                    {{ summary.all_time }}
                </p>
            </div>
        </div>

        <div>
            <h2 class="mb-3 text-sm font-medium">
                {{ t('common.last30DaysDaily') }}
            </h2>

            <div
                v-if="daily.length === 0"
                class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground"
            >
                {{ t('common.noResults') }}
            </div>

            <div
                v-else
                class="overflow-hidden rounded-lg border border-input shadow-sm"
            >
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">
                                {{ t('common.date') }}
                            </th>
                            <th class="px-4 py-3 text-start font-medium">
                                {{ t('common.uniqueVisitors') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in daily"
                            :key="row.date"
                            class="border-t border-input"
                        >
                            <td class="px-4 py-3">
                                {{ row.date }}
                            </td>
                            <td class="px-4 py-3">
                                {{ row.visitors }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
