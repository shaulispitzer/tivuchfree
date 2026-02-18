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
            },
            text: {
                // only applies to text input type
                outer: 'shmerel',
                input: ' shmerel',
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
            // email: {
            //     // only applies to email input type
            //     outer: 'appearance-none bg-transparent w-full text-gray-900 focus:outline-none focus:ring-0',
            //     input: '$reset bop',
            // },
            select: {
                option: '$reset bg-white text-gray-700 group-data-[disabled]:opacity-50 group-data-[disabled]:select-none group-data-[multiple]:checked:bg-red-100 group-data-[multiple]:focus:bg-red-100 group-data-[multiple]:text-sm group-data-[multiple]:outline-hidden group-data-[multiple]:border-none group-data-[multiple]:py-1.5 group-data-[multiple]:px-2   bg-red-300 text-gray-900 hover:bg-red-400',
                inner: '$reset text-base flex items-center w-full px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
            number: {
                inner: '$reset  text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
                input: '$reset appearance-none [color-scheme:light] selection:text-gray-700 group-data-[has-overlay]:selection:!text-transparent text-base text-gray-700 min-w-0 min-h-[1.5em] grow outline-hidden bg-transparent selection:bg-red-100 placeholder:text-gray-400 group-data-[disabled]:!cursor-not-allowed dark:placeholder-gray-400/50 dark:text-gray-300 border-none p-0 focus:ring-0 formkit-input',
            },
            textarea: {
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
            date: {
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
            checkbox: {
                // inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
            radio: {
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
        }),
        rootClasses,
    },
});
