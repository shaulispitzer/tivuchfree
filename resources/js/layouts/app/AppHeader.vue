<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { dashboard, home, locale, login } from '@/routes';
import AppHeaderNav from './AppHeaderNav.vue';
const { t } = useI18n();

// type Props = {
//     breadcrumbs?: BreadcrumbItem[];
// };

// const props = withDefaults(defineProps<Props>(), {
//     breadcrumbs: () => [],
// });

const page = usePage();
const user = computed(() => page.props.user ?? null);
const currentLocale = computed(() => page.props.locale ?? 'en');
const localeToggleFlag = computed(() =>
    currentLocale.value === 'he' ? '🇬🇧' : '🇮🇱',
);
const localeToggleLabel = computed(() =>
    currentLocale.value === 'he' ? 'Switch to English' : 'Switch to Hebrew',
);
const handleNavClick = (label: string) => {
    console.log(`${label} clicked`);
};
const logoHref = computed(() => (user.value ? dashboard() : home()));
const handleLocaleChange = () => {
    router.post(
        locale(),
        {
            locale: currentLocale.value === 'en' ? 'he' : 'en',
        },
        {
            preserveScroll: true,
            preserveState: false,
        },
    );
};
</script>

<template>
    <div>
        <div
            class="border-b border-sidebar-border/80 bg-white/80 backdrop-blur-sm"
        >
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
                                <AppHeaderNav
                                    nav-class="-mx-3 space-y-1"
                                    button-class="w-full justify-start px-3"
                                    dropdown-align="start"
                                    dropdown-content-class="w-56"
                                    :on-nav-click="handleNavClick"
                                />
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="logoHref" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <AppHeaderNav
                        nav-class="ml-8 flex h-full items-center gap-2"
                        button-class="h-9 px-3"
                        dropdown-align="start"
                        dropdown-content-class="w-56"
                        :on-nav-click="handleNavClick"
                    />
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div class="relative flex items-center space-x-1">
                        <div class="hidden space-x-1 lg:flex"></div>
                    </div>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-9 w-9"
                        :aria-label="localeToggleLabel"
                        @click="handleLocaleChange"
                    >
                        <span class="text-lg" aria-hidden="true">
                            {{ localeToggleFlag }}
                        </span>
                    </Button>
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
                                            class="rounded-lg bg-neutral-200 font-semibold text-black"
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
                        <Button as-child variant="outline">
                            <Link :href="login()">{{ t('auth.login') }}</Link>
                        </Button>
                        <Button as-child>
                            <Link>{{ t('common.addListing') }}</Link>
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
