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
                input: '$reset [color-scheme:light] selection:text-gray-700 group-data-[has-overlay]:selection:!text-transparent grow p-2 py-2 px-3  text-base text-gray-700 text-ellipsis min-w-0 outline-hidden bg-transparent group-data-[disabled]:!cursor-not-allowed group-data-[prefix-icon]:!pl-0 group-data-[suffix-icon]:!pr-0 data-[placeholder]:text-gray-400 group-data-[multiple]:!p-0 selection:bg-red-100 border-none focus:ring-0 bg-none formkit-input',
            },
            number: {
                inner: '$reset  text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
                input: '$reset [color-scheme:light] selection:text-gray-700 group-data-[has-overlay]:selection:!text-transparent text-base text-gray-700 min-w-0 min-h-[1.5em] grow outline-hidden bg-transparent selection:bg-red-100 placeholder:text-gray-400 group-data-[disabled]:!cursor-not-allowed border-none p-0 focus:ring-0 formkit-input',
            },
            textarea: {
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
            date: {
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
            checkbox: {
                decorator:
                    ' $reset mx-1.5 bg-white ring-primary/90 peer-checked:border-primary relative block text-lg w-[1em] aspect-[1/1] border border-gray-300 text-transparent peer-checked:bg-primary/10 peer-checked:text-primary peer-focus-visible:ring-2 peer-focus-visible:ring-offset-1 select-none group-data-[disabled]:!cursor-not-allowed peer-disabled:bg-gray-100 group-data-[disabled]:grayscale shadow-sm peer-disabled:cursor-not-allowed group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none  formkit-decorator after:content-["✓"] after:absolute after:inset-0 after:flex after:items-center after:justify-center after:opacity-0 after:text-current after:text-[0.65em] after:leading-none peer-checked:after:opacity-100',
                input: '$reset appearance-none [color-scheme:light]  selection:bg-red-100 selection:text-gray-700 group-data-[has-overlay]:selection:!text-transparent peer pointer-events-none absolute h-0 w-0 overflow-hidden opacity-0 formkit-input',
            },
            radio: {
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
            email: {
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
        }),
        rootClasses,
    },
});
