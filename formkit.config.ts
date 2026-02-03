import { defaultConfig } from '@formkit/vue';
import { en, he } from '@formkit/i18n';
import { rootClasses } from './formkit.theme';

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
        rootClasses,
    },
});
