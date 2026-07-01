<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    create,
    subscribe,
    unsubscribe,
    destroy,
    index,
} from '@/routes/admin/subscriptions';
import type { Paginator } from '@/types';
import ConfirmableButton from '@/components/ConfirmableButton.vue';
import { format, formatDistanceToNow } from 'date-fns';
import { enGB, he } from 'date-fns/locale';
import { ExternalLink } from 'lucide-vue-next';

type SubscriptionFilterState = {
    neighbourhoods: string[];
    hide_taken_properties: boolean;
    bedrooms_range: [number, number];
    furnished: string;
    type: string;
    available_from: string;
    available_to: string;
};

type SubscriptionRow = {
    id: number;
    email: string;
    is_active: boolean;
    subscribed_at: string | null;
    unsubscribed_at: string | null;
    update_filters_url: string;
    filters: SubscriptionFilterState;
};

type ListFilters = {
    search: string;
    status: 'all' | 'active' | 'unsubscribed';
};

const props = defineProps({
    subscriptions: {
        type: Object as PropType<Paginator<SubscriptionRow>>,
        required: true,
    },
    filters: {
        type: Object as PropType<ListFilters>,
        required: true,
    },
});

const { t } = useI18n();

const search = ref(props.filters.search);
const status = ref<ListFilters['status']>(props.filters.status);

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
    if (!value) {
        return null;
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return null;
    }
    const locale = dateLocale() === 'he' ? he : enGB;
    return {
        formattedDate: formatDistanceToNow(date, {
            addSuffix: true,
            locale,
        }),
        fullDate: format(date, 'do MMM yyyy, HH:mm', { locale }),
    };
}

function formatBedroomsRange(range: [number, number]): string {
    const formatValue = (value: number): string =>
        Number.isInteger(value) ? value.toString() : value.toFixed(1);
    return `${formatValue(range[0])} - ${formatValue(range[1])}`;
}

function formatFiltersSummary(filters: SubscriptionFilterState): string {
    const parts: string[] = [];
    const neighbourhoodCount = filters.neighbourhoods?.length ?? 0;
    if (neighbourhoodCount === 0) {
        parts.push('All neighbourhoods');
    } else {
        parts.push(
            `${neighbourhoodCount} neighbourhood${neighbourhoodCount === 1 ? '' : 's'}`,
        );
    }
    parts.push(`${formatBedroomsRange(filters.bedrooms_range)} beds`);
    if (filters.hide_taken_properties) {
        parts.push('Available only');
    }
    return parts.join(' · ');
}

let isOpeningSubscriptionTab = false;

function openUpdateFilters(url: string, event: MouseEvent): void {
    event.preventDefault();
    event.stopImmediatePropagation();

    if (isOpeningSubscriptionTab) {
        return;
    }

    isOpeningSubscriptionTab = true;
    window.open(url, '_blank', 'noopener,noreferrer');

    queueMicrotask(() => {
        isOpeningSubscriptionTab = false;
    });
}

function applyFilters(): void {
    router.get(
        index(),
        {
            search: search.value.trim() || undefined,
            status: status.value === 'all' ? undefined : status.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(status, () => {
    applyFilters();
});

watch(
    () => props.filters,
    (value) => {
        search.value = value.search;
        status.value = value.status;
    },
    { deep: true },
);

const activeActionId = ref<number | null>(null);

function submitUnsubscribe(subscriptionId: number): void {
    activeActionId.value = subscriptionId;

    router.post(unsubscribe(subscriptionId).url, undefined, {
        preserveScroll: true,
        onFinish: () => {
            activeActionId.value = null;
        },
    });
}

function submitSubscribe(subscriptionId: number): void {
    activeActionId.value = subscriptionId;

    router.post(subscribe(subscriptionId).url, undefined, {
        preserveScroll: true,
        onFinish: () => {
            activeActionId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Admin - Subscriptions" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold tracking-tight">
                {{ t('common.admin') }} - Subscriptions
            </h1>
            <Button as-child>
                <Link :href="create()">Add subscription</Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-end gap-4">
            <div class="grid w-full max-w-xs gap-2 sm:max-w-sm">
                <Label for="subscription-search">{{ t('common.search') }}</Label>
                <Input
                    id="subscription-search"
                    v-model="search"
                    type="search"
                    class="w-full"
                    :placeholder="t('common.searchHere')"
                />
            </div>
            <div class="grid w-40 gap-2">
                <Label for="subscription-status">Status</Label>
                <Select v-model="status">
                    <SelectTrigger id="subscription-status" class="w-full">
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All</SelectItem>
                        <SelectItem value="active">{{
                            t('common.active')
                        }}</SelectItem>
                        <SelectItem value="unsubscribed"
                            >Unsubscribed</SelectItem
                        >
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div
            v-if="subscriptions.data.length === 0"
            class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground"
        >
            {{ t('common.noResults') }}
        </div>

        <template v-else>
            <div
                class="overflow-hidden rounded-lg border border-input shadow-sm"
            >
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                {{ t('common.email') }}
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Status
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Filters
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Subscribed
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Unsubscribed
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-input">
                        <tr
                            v-for="subscription in subscriptions.data"
                            :key="subscription.id"
                            class="bg-background transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ subscription.email }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        subscription.is_active
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-100 text-slate-700'
                                    "
                                >
                                    {{
                                        subscription.is_active
                                            ? t('common.active')
                                            : 'Unsubscribed'
                                    }}
                                </span>
                            </td>
                            <td
                                class="max-w-xs px-4 py-3 text-muted-foreground"
                            >
                                {{
                                    formatFiltersSummary(subscription.filters)
                                }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                <template
                                    v-if="
                                        formatDateTime(
                                            subscription.subscribed_at,
                                        )
                                    "
                                >
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <span class="cursor-help">
                                                    {{
                                                        formatDateTime(
                                                            subscription.subscribed_at,
                                                        )!.formattedDate
                                                    }}
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {{
                                                    formatDateTime(
                                                        subscription.subscribed_at,
                                                    )!.fullDate
                                                }}
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </template>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                <template
                                    v-if="
                                        formatDateTime(
                                            subscription.unsubscribed_at,
                                        )
                                    "
                                >
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <span class="cursor-help">
                                                    {{
                                                        formatDateTime(
                                                            subscription.unsubscribed_at,
                                                        )!.formattedDate
                                                    }}
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {{
                                                    formatDateTime(
                                                        subscription.unsubscribed_at,
                                                    )!.fullDate
                                                }}
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
                                    <button
                                        v-if="subscription.is_active"
                                        type="button"
                                        class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md bg-secondary px-3 text-sm font-medium text-secondary-foreground transition-all hover:bg-secondary/80"
                                        @click.stop.prevent="
                                            openUpdateFilters(
                                                subscription.update_filters_url,
                                                $event,
                                            )
                                        "
                                    >
                                        <ExternalLink
                                            class="h-3.5 w-3.5"
                                        />
                                        Edit filters
                                    </button>
                                    <ConfirmableButton
                                        v-if="subscription.is_active"
                                        variant="secondary"
                                        size="sm"
                                        :processing="
                                            activeActionId === subscription.id
                                        "
                                        :message="t('common.unsubscribeConfirm')"
                                        :confirm-label="t('common.yes')"
                                        :cancel-label="t('common.no')"
                                        @confirm="
                                            submitUnsubscribe(subscription.id)
                                        "
                                    >
                                        Unsubscribe
                                    </ConfirmableButton>
                                    <Button
                                        v-else
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        :disabled="
                                            activeActionId === subscription.id
                                        "
                                        @click="
                                            submitSubscribe(subscription.id)
                                        "
                                    >
                                        Subscribe again
                                    </Button>
                                    <Form
                                        v-bind="
                                            destroy.form(subscription.id)
                                        "
                                        class="inline"
                                    >
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            size="sm"
                                        >
                                            Delete
                                        </Button>
                                    </Form>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationNav
                :links="subscriptions.links"
                :from="subscriptions.from"
                :to="subscriptions.to"
                :total="subscriptions.total"
            />
        </template>
    </div>
</template>
