<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { destroy } from '@/routes/admin/reviews';
import type { Paginator } from '@/types';
import { format, formatDistanceToNow } from 'date-fns';
import { he, enGB } from 'date-fns/locale';

type ReviewRow = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    message: string;
    created_at: string | null;
};

const props = defineProps<{
    reviews: Paginator<ReviewRow>;
}>();

const { t } = useI18n();

const dateLocale = (): string | undefined =>
    typeof document !== 'undefined' &&
    document.documentElement?.lang?.startsWith('he')
        ? 'he'
        : typeof navigator !== 'undefined' &&
            navigator.language?.startsWith('he')
          ? 'he'
          : undefined;

function formatDateTime(
    value: string | null,
): { formattedDate: string; fullDate: string } | null {
    if (!value) { return null; }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) { return null; }
    const locale = dateLocale() === 'he' ? he : enGB;
    return {
        formattedDate: formatDistanceToNow(date, {
            addSuffix: true,
            locale,
        }),
        fullDate: format(date, 'do MMM yyyy, HH:mm', { locale }),
    };
}
</script>

<template>
    <Head :title="`Admin - ${t('common.adminReviews')}`" />

    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ t('common.manageReviews') }}
            </h1>
        </div>

        <div
            v-if="reviews.data.length === 0"
            class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground"
        >
            {{ t('common.noReviewsFound') }}
        </div>

        <template v-else>
            <div class="overflow-hidden rounded-lg border border-input shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Name
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Email
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Role
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Message
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Submitted
                            </th>
                            <th class="px-4 py-3 text-right font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-input">
                        <tr
                            v-for="review in reviews.data"
                            :key="review.id"
                            class="bg-background transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ review.name }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ review.email }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ review.role ?? '—' }}
                            </td>
                            <td class="max-w-xs px-4 py-3">
                                <p class="line-clamp-2 text-sm text-foreground">
                                    {{ review.message }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                <template
                                    v-if="formatDateTime(review.created_at)"
                                >
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <span class="cursor-help">
                                                    <span
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        {{
                                                            formatDateTime(
                                                                review.created_at,
                                                            )!.formattedDate
                                                        }}
                                                    </span>
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <span class="font-medium">{{
                                                    formatDateTime(
                                                        review.created_at,
                                                    )!.fullDate
                                                }}</span>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </template>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end">
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        as-child
                                    >
                                        <Link
                                            :href="destroy(review.id)"
                                            method="delete"
                                            as="button"
                                            preserve-scroll
                                        >
                                            Delete
                                        </Link>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationNav
                :links="reviews.links"
                :from="reviews.from"
                :to="reviews.to"
                :total="reviews.total"
            />
        </template>
    </div>
</template>
