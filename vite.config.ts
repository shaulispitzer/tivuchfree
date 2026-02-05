import DefineOptions from 'unplugin-vue-define-options/vite';
import { FileSystemIconLoader } from 'unplugin-icons/loaders';
import Icons from 'unplugin-icons/vite';
import IconsResolver from 'unplugin-icons/resolver';
import autoImport from 'unplugin-auto-import/vite';
import components from 'unplugin-vue-components/vite';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
// import vueI18nPlugin from '@intlify/unplugin-vue-i18n/vite';
import { watch } from 'vite-plugin-watch';
import tailwindcss from '@tailwindcss/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';

export default defineConfig({
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    lodash: ['lodash'],
                    swiper: ['swiper'],
                    'date-fns': ['date-fns'],
                    'vue-tippy': ['vue-tippy'],
                    // '@formkit/vue': ['@formkit/vue'],
                    'vue-i18n': ['vue-i18n'],
                    axios: ['axios'],
                },
            },
        },
    },

    plugins: [
        components({
            dirs: ['resources/js/components'],
            dts: 'resources/js/types/components.d.ts',
            resolvers: [
                IconsResolver({
                    prefix: 'icon',
                    customCollections: ['custom'],
                }),
                (name: string) => {
                    const components = ['Link', 'Head'];
                    if (components.includes(name)) {
                        return {
                            name,
                            from: '@inertiajs/vue3',
                        };
                    }
                },
            ],
        }),
        Icons({
            scale: 1,
            autoInstall: true,
            compiler: 'vue3',
            iconCustomizer(collection, icon, props) {
                // we want to use tailwind classes for sizing
                props.width = '100%';
                props.height = '100%';
            },
            customCollections: {
                custom: FileSystemIconLoader('./resources/assets/icons'),
            },
        }),
        autoImport({
            vueTemplate: true,
            dts: 'resources/js/types/auto-imports.d.ts',
            dirs: ['resources/js/composables'],
            imports: [
                'vue',
                { 'momentum-trail-patch': ['route', 'current'] },
                { 'momentum-lock': ['can'] },
                {
                    '@inertiajs/vue3': ['router', 'useForm', 'usePage'],
                },
                { 'vue-i18n': ['useI18n'] },
            ],
        }),
        // vueI18nPlugin({
        //     strictMessage: false,
        //     runtimeOnly: false,
        // }),
        watch({
            pattern: 'app/{Data,Enums}/**/*.php',
            command: 'php artisan typescript:transform',
        }),
        watch({
            pattern: 'routes/*.php',
            command: 'php artisan trail:generate',
        }),

        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        tailwindcss(),
        // wayfinder({
        //     formVariants: true,
        // }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        DefineOptions(),
    ],
});
