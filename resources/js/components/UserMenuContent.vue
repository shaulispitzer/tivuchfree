<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Building2,
    LayoutDashboard,
    LogOut,
    Settings,
    Users,
} from 'lucide-vue-next';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { index as adminPropertiesIndex } from '@/routes/admin/properties';
import { index as adminUsersIndex } from '@/routes/admin/users';
import { index as myPropertiesIndex } from '@/routes/my-properties';
import { edit } from '@/routes/profile';

type Props = {
    user: App.Data.UserData;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                :href="myPropertiesIndex()"
                prefetch
            >
                <Building2 class="mr-2 h-4 w-4" />
                My Properties
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="user.is_admin" :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                :href="adminPropertiesIndex()"
                prefetch
            >
                <LayoutDashboard class="mr-2 h-4 w-4" />
                Admin - Properties
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="user.is_admin" :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                :href="adminUsersIndex()"
                prefetch
            >
                <Users class="mr-2 h-4 w-4" />
                Admin - Users
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
