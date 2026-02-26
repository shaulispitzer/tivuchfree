import { plugin as formkitPlugin } from '@formkit/vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import Toast, { POSITION, type PluginOptions } from 'vue-toastification';
import AppLayout from '@/layouts/app/AppLayout.vue';
import formkitConfig from '../../formkit.config';
import { modal } from '../../vendor/emargareten/inertia-modal';
import i18nInstance from '../js/plugins/i18n';

import { notifications } from './plugins/notifications';

// import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const toastOptions: PluginOptions = {
    position: POSITION.BOTTOM_CENTER,
    rtl: true,
    newestOnTop: true,
    timeout: 3000,
};

type AppLocale = 'en' | 'he';

const normalizeLocale = (locale?: string): AppLocale =>
    locale === 'he' ? 'he' : 'en';

const setI18nLocale = (locale: AppLocale) => {
    i18nInstance.global.locale.value = locale;
    document.documentElement.lang = locale;
    document.documentElement.dir = locale === 'he' ? 'rtl' : 'ltr';
};

const getInitialLocale = (props: {
    initialPage?: { props?: { locale?: string } };
}): AppLocale => normalizeLocale(props.initialPage?.props?.locale);

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ).then((page) => {
            const pagesWithoutLayout = [
                'auth/Login',
                'auth/Register',
                'auth/VerifyEmail',
                'auth/ResetPassword',
                'auth/ForgotPassword',
            ];
            if (!pagesWithoutLayout.includes(name)) {
                page.default.layout = AppLayout;
            }
            return page;
        }),
    setup({ el, App, props, plugin }) {
        setI18nLocale(getInitialLocale(props));

        // #region agent log
        router.on('success', (event) => {
            const locale = normalizeLocale(
                (event.detail.page.props as { locale?: string }).locale,
            );

            setI18nLocale(locale);
        });

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18nInstance)
            .use(Toast, toastOptions)
            .use(notifications)
            .use(formkitPlugin, formkitConfig)
            .use(modal, {
                resolve: (name: string) =>
                    resolvePageComponent(
                        `./pages/${name}.vue`,
                        import.meta.glob<DefineComponent>('./pages/**/*.vue'),
                    ),
            })
            .mount(el);
    },
    progress: {
        color: '#038071',
    },
});

// This will set light / dark mode on page load...
// initializeTheme();
