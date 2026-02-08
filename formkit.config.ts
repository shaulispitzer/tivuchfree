import { defaultConfig } from '@formkit/vue';
import { en, he } from '@formkit/i18n';
import { rootClasses } from './formkit.theme';
import { generateClasses } from '@formkit/themes';

const resolveLocale = (): 'en' | 'he' => {
    if (typeof document === 'undefined') {
        return 'en';
    }

    return document.documentElement.lang === 'he' ? 'he' : 'en';
};
// make the label of the input black
export default defaultConfig({
    locales: { en, he },
    locale: resolveLocale(),
    config: {
        classes: generateClasses({
            global: {
                // applies to all input types
                outer: 'shmerel',
            },
            text: {
                // only applies to text input type
                outer: 'shmerel',
                input: ' shmerel',
            },
            // email: {
            //     // only applies to email input type
            //     outer: 'appearance-none bg-transparent w-full text-gray-900 focus:outline-none focus:ring-0',
            //     input: '$reset bop',
            // },
        }),
        rootClasses,
    },
});
