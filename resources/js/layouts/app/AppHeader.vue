<script setup lang="ts">
import { Menu } from 'lucide-vue-next';
import { dashboard, home, locale, login } from '@/routes';
import { create as propertiesCreate } from '@/routes/properties';
import AppHeaderNav from './AppHeaderNav.vue';
import FlagpackGbUkm from '~icons/flagpack/gb-ukm';
import TwemojiFlagIsrael from '~icons/twemoji/flag-israel';
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
    currentLocale.value === 'he' ? FlagpackGbUkm : TwemojiFlagIsrael,
);
const localeToggleLabel = computed(() =>
    currentLocale.value === 'he' ? 'Switch to English' : 'Switch to Hebrew',
);
const logoHref = computed(() => (user.value ? dashboard() : home()));
const shortTermModalOpen = ref(false);
const handleNavClick = (action: string) => {
    switch (action) {
        case 'home':
            router.visit(home().url);
            return;
        case 'listings-long-term':
            router.visit('/properties?type=long_term');
            return;
        case 'listings-medium-term':
            router.visit('/properties?type=medium_term');
            return;
        case 'listings-short-term':
            shortTermModalOpen.value = true;
            return;
        case 'about-us':
            router.visit('/about-us');
            return;
        default:
            console.log(`${action} clicked`);
    }
};
const closeShortTermModal = () => {
    shortTermModalOpen.value = false;
};
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

const showTivuchimNotice = ref(false);
let tivuchimNoticeTimeout: ReturnType<typeof setTimeout> | null = null;

onMounted(() => {
    tivuchimNoticeTimeout = setTimeout(() => {
        showTivuchimNotice.value = true;
    }, 2000);
});

onBeforeUnmount(() => {
    if (tivuchimNoticeTimeout) {
        clearTimeout(tivuchimNoticeTimeout);
    }
});

const closeTivuchimNotice = () => {
    showTivuchimNotice.value = false;
};
</script>

<template>
    <div>
        <div
            class="border-b border-sidebar-border/80 bg-white/80 backdrop-blur-sm"
        >
            <div
                class="relative mx-auto flex h-16 items-center px-4 md:max-w-7xl xl:px-0"
            >
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

                <Link
                    :href="logoHref"
                    class="absolute left-1/2 flex -translate-x-1/2 items-center gap-x-2 lg:static lg:translate-x-0"
                >
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

                <div class="ms-auto flex items-center space-x-2">
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
                            <component :is="localeToggleFlag" class="h-4 w-4" />
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
                            <Link :href="propertiesCreate()">{{
                                t('common.addListing')
                            }}</Link>
                        </Button>
                    </template>
                </div>
            </div>
        </div>
        <!-- <div
            class="bg-red-700 text-white transition-all duration-300 ease-out"
            :class="
                showTivuchimNotice
                    ? 'max-h-24 translate-y-0 opacity-100'
                    : 'max-h-0 -translate-y-3 overflow-hidden opacity-0'
            "
        >
            <div
                class="mx-auto flex min-h-10 flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm md:max-w-7xl xl:px-0"
            >
                <p class="font-medium">
                    {{ t('common.tivuchnotice') }}
                </p>
                <Button
                    variant="secondary"
                    size="sm"
                    class="h-8 border border-white/40 bg-white/15 px-3 text-xs text-white hover:bg-white/25"
                    @click="closeTivuchimNotice"
                >
                    {{ t('common.acceptTc') }}
                </Button>
            </div>
        </div> -->
        <Modal
            :open="shortTermModalOpen"
            title="Short Term Properties"
            :actions="false"
            :as-page="false"
            @close="closeShortTermModal"
        >
            <div class="space-y-5">
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Please note: Tivuch Free do not have a short term feature
                    ourselves (yet). We endorse using this third party, minimal
                    costing, platform.
                </p>

                <a
                    href="https://www.heimishe.apartments"
                    class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                >
                    go to Www.Heimishe.Apartments
                </a>
            </div>
        </Modal>
    </div>
</template>
