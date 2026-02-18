<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import { Button } from '@/components/ui/button';
import { destroy, makeAdmin } from '@/routes/admin/users';

// type UserRow = {
//     id: number;
//     name: string;
//     email: string;
//     is_admin: boolean;
// //     created_at: string | null;
// // };

const props = defineProps({
    users: {
        type: Array as PropType<App.Data.UserData[]>,
        required: true,
    },
});
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
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-left font-medium">Role</th>
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
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button v-if="!user.is_admin" size="sm" variant="secondary" as-child>
                                    <Link
                                        :href="makeAdmin(user.id)"
                                        method="patch"
                                        as="button"
                                    >
                                        Mark as admin
                                    </Link>
                                </Button>

                                <Button size="sm" variant="destructive" as-child>
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
