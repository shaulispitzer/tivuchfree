<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    index,
    retry,
    retryAll,
    destroy,
    destroyAll,
} from '@/routes/admin/failed-jobs';
import type { Paginator } from '@/types';
import { format, formatDistanceToNow } from 'date-fns';
import { he, enGB } from 'date-fns/locale';

type FailedJobRow = {
    id: string;
    connection: string;
    queue: string;
    payload: string;
    exception: string;
    failed_at: string;
};

const props = defineProps<{
    failedJobs: Paginator<FailedJobRow>;
}>();

const dateLocale = () =>
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
    if (!value) {
        return null;
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return null;
    }
    const locale = dateLocale() === 'he' ? he : enGB;
    return {
        formattedDate: formatDistanceToNow(date, { addSuffix: true, locale }),
        fullDate: format(date, 'do MMM yyyy, HH:mm', { locale }),
    };
}

function resolveJobName(payload: string): string {
    try {
        const parsed = JSON.parse(payload) as { displayName?: string };
        return parsed.displayName ?? 'Unknown Job';
    } catch {
        return 'Unknown Job';
    }
}

function truncateException(exception: string): string {
    const firstLine = exception.split('\n')[0] ?? exception;
    return firstLine.length > 120 ? firstLine.slice(0, 120) + '…' : firstLine;
}
</script>

<template>
    <Head title="Admin - Failed Jobs" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">
                    Failed Jobs
                </h1>
            </div>

            <div v-if="failedJobs.total > 0" class="flex items-center gap-2">
                <Button size="sm" variant="outline" as-child>
                    <Link :href="retryAll()" method="post" as="button">
                        Retry All
                    </Link>
                </Button>
                <Button size="sm" variant="destructive" as-child>
                    <Link :href="destroyAll()" method="delete" as="button">
                        Clear All
                    </Link>
                </Button>
            </div>
        </div>

        <div
            v-if="failedJobs.data.length === 0"
            class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground"
        >
            No failed jobs — everything is running smoothly.
        </div>

        <template v-else>
            <div
                class="overflow-hidden rounded-lg border border-input shadow-sm"
            >
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Job</th>
                            <th class="px-4 py-3 text-left font-medium">
                                Queue
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Error
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Failed At
                            </th>
                            <th class="px-4 py-3 text-right font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-input">
                        <tr
                            v-for="job in failedJobs.data"
                            :key="job.id"
                            class="bg-background transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ resolveJobName(job.payload) }}
                                </div>
                                <div
                                    class="mt-0.5 font-mono text-xs text-muted-foreground"
                                >
                                    {{ job.id }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ job.queue }}
                            </td>
                            <td class="max-w-sm px-4 py-3">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <p
                                                class="cursor-help truncate text-xs text-destructive"
                                            >
                                                {{
                                                    truncateException(
                                                        job.exception,
                                                    )
                                                }}
                                            </p>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            class="max-w-lg font-mono text-xs whitespace-pre-wrap"
                                        >
                                            {{ job.exception }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                <template v-if="formatDateTime(job.failed_at)">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <span
                                                    class="cursor-help text-xs"
                                                >
                                                    {{
                                                        formatDateTime(
                                                            job.failed_at,
                                                        )!.formattedDate
                                                    }}
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <span class="font-medium">
                                                    {{
                                                        formatDateTime(
                                                            job.failed_at,
                                                        )!.fullDate
                                                    }}
                                                </span>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </template>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        as-child
                                    >
                                        <Link
                                            :href="retry(job.id)"
                                            method="post"
                                            as="button"
                                            preserve-scroll
                                        >
                                            Retry
                                        </Link>
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        as-child
                                    >
                                        <Link
                                            :href="destroy(job.id)"
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
                :links="failedJobs.links"
                :from="failedJobs.from"
                :to="failedJobs.to"
                :total="failedJobs.total"
            />
        </template>
    </div>
</template>
