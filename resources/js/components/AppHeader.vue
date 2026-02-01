<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Building, House, Info, Mail, Menu } from 'lucide-vue-next';

import { dashboard, home, locale, login, register } from '@/routes';
import type { NavItem } from '@/types';

// type Props = {
//     breadcrumbs?: BreadcrumbItem[];
// };

// const props = withDefaults(defineProps<Props>(), {
//     breadcrumbs: () => [],
// });

const page = usePage();
const user = computed(() => page.props.user ?? null);
const currentLocale = page.props.locale ?? 'en';
const { whenCurrentUrl } = useCurrentUrl();

const activeItemStyles =
    'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100';

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'About Us',
        // href: aboutUs(),
        icon: Info,
    },
    {
        title: 'Contact Us',
        // href: contactUs(),
        icon: Mail,
    },
    {
        title: 'Long Term Rental',
        // href: services(),
        icon: House,
    },
    {
        title: 'Short Term Rental',
        // href: services(),
        icon: Building,
    },
    {
        title: 'Medium Term Rental',
        // href: services(),
        icon: House,
    },
]);
const logoHref = computed(() => (user.value ? dashboard() : home()));
const handleLocaleChange = () => {
    router.post(
        locale(),
        {
            locale: currentLocale === 'en' ? 'he' : 'en',
        },
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                document.getElementsByTagName('html')[0].lang =
                    currentLocale === 'en' ? 'he' : 'en';
                document.getElementsByTagName('html')[0].dir =
                    currentLocale === 'en' ? 'rtl' : 'ltr';
            },
        },
    );
};
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only"
                                >Navigation Menu</SheetTitle
                            >
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon
                                    class="size-6 fill-current text-black dark:text-white"
                                />
                            </SheetHeader>
                            <div
                                class="flex h-full flex-1 flex-col justify-between space-y-4 py-6"
                            >
                                <nav
                                    v-if="mainNavItems.length"
                                    class="-mx-3 space-y-1"
                                >
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href ?? ''"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                        :class="
                                            whenCurrentUrl(
                                                item.href ?? '',
                                                activeItemStyles,
                                            )
                                        "
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="logoHref" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <h1>{{ currentLocale || 'no locale' }}</h1>
                <Button @click="handleLocaleChange"> Change Locale </Button>
                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <!-- <NavigationMenu class="ml-10 flex h-full items-stretch">
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-2"
                        >
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        // navigationMenuTriggerStyle(),
                                        whenCurrentUrl(
                                            item.href ?? '',
                                            activeItemStyles,
                                        ),
                                        'fpx-3 flex h-9 cursor-pointer items-center',
                                    ]"
                                    :href="item.href ?? ''"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentUrl(item.href ?? '')"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu> -->
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div class="relative flex items-center space-x-1">
                        <div class="hidden space-x-1 lg:flex"></div>
                    </div>

                    <template v-if="user">
                        <DropdownMenu>
                            <DropdownMenuTrigger :as-child="true">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                                >
                                    <Avatar
                                        class="size-8 overflow-hidden rounded-full"
                                    >
                                        <AvatarFallback
                                            class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                        >
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <UserMenuContent :user="user" />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>
                    <template v-else>
                        <Button as-child variant="ghost">
                            <Link :href="login()">Log in</Link>
                        </Button>
                        <Button as-child>
                            <Link :href="register()">Sign up</Link>
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
