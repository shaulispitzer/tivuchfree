<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import { Button } from '@/components/ui/button';
import { destroy, makeAdmin, revokeAdmin } from '@/routes/admin/users';
import { usePage } from '@inertiajs/vue3';

import { format, formatDistanceToNow } from 'date-fns';
import { he, enGB } from 'date-fns/locale';
import IconShieldOff from '~icons/lucide/shield-off';
import IconShieldPlus from '~icons/lucide/shield-plus';

type AdminUserRow = App.Data.UserData & {
    email_verified_at: string | null;
};

const page = usePage();
const currentUserId = computed(
    () => (page.props.user as App.Data.UserData | null)?.id ?? null,
);

const props = defineProps({
    users: {
        type: Array as PropType<AdminUserRow[]>,
        required: true,
    },
});

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
    if (!value) return null;
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return null;
    const locale = dateLocale() === 'he' ? he : enGB;
    return {
        formattedDate: formatDistanceToNow(date, {
            addSuffix: true,
            locale,
        }),
        fullDate: format(date, 'do MMM yyyy', { locale }),
    };
}
</script>

<template>
    <Head title="Admin - Users" />

    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Users</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Manage all users. Only admins can access this page.
            </p>
        </div>

        <div
            v-if="users.length === 0"
            class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground"
        >
            No users found.
        </div>

        <div
            v-else
            class="overflow-hidden rounded-lg border border-input shadow-sm"
        >
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Avatar</th>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-left font-medium">Role</th>
                        <th class="px-4 py-3 text-left font-medium">
                            Created at
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Email verified at
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-input">
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="bg-background transition-colors hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <img
                                v-if="user.google_avatar"
                                :src="user.google_avatar"
                                :alt="user.name"
                                class="h-8 w-8 rounded-full object-cover"
                            />
                            <span
                                v-else
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground"
                            >
                                {{ user.name.charAt(0).toUpperCase() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-3">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    user.is_admin
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'bg-slate-100 text-slate-700'
                                "
                            >
                                {{ user.is_admin ? 'Admin' : 'User' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            <template v-if="formatDateTime(user.created_at)">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <span class="cursor-help"
                                                ><span
                                                    class="text-xs text-muted-foreground"
                                                    >{{
                                                        formatDateTime(
                                                            user.created_at,
                                                        )!.formattedDate
                                                    }}</span
                                                ></span
                                            >
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <span class="font-medium">{{
                                                formatDateTime(user.created_at)!
                                                    .fullDate
                                            }}</span>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </template>
                            <span v-else>—</span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            <template
                                v-if="formatDateTime(user.email_verified_at)"
                            >
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <span class="cursor-help"
                                                ><span
                                                    class="text-xs text-muted-foreground"
                                                    >{{
                                                        formatDateTime(
                                                            user.email_verified_at,
                                                        )!.formattedDate
                                                    }}</span
                                                ></span
                                            >
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <span class="font-medium">{{
                                                formatDateTime(
                                                    user.email_verified_at,
                                                )!.fullDate
                                            }}</span>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </template>
                            <span v-else>—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    v-if="!user.is_admin"
                                    size="sm"
                                    variant="secondary"
                                    as-child
                                >
                                    <Link
                                        :href="makeAdmin(user.id)"
                                        method="patch"
                                        as="button"
                                        preserve-scroll
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        <IconShieldPlus class="h-4 w-4" />
                                        Make Admin
                                    </Link>
                                </Button>
                                <Button
                                    v-else-if="user.id !== currentUserId"
                                    size="sm"
                                    variant="secondary"
                                    as-child
                                >
                                    <Link
                                        :href="revokeAdmin(user.id)"
                                        method="patch"
                                        as="button"
                                        preserve-scroll
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        <IconShieldOff class="h-4 w-4" />
                                        Revoke Admin
                                    </Link>
                                </Button>

                                <Button
                                    size="sm"
                                    variant="destructive"
                                    as-child
                                >
                                    <Link
                                        :href="destroy(user.id)"
                                        method="delete"
                                        as="button"
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
    </div>
</template>
