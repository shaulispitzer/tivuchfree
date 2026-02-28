import type { FormKitNode } from '@formkit/core';
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

/**
 * Validates Israeli, UK, or US phone numbers. Skips when empty (for optional fields).
 *
 * How it works:
 * 1. Non-digits (spaces, dashes, parentheses, etc.) are stripped; only digits are checked.
 * 2. The remaining string is matched against known patterns:
 *
 *    Israeli (9-digit local number, area code 2–9):
 *    - 9 digits: 501234567
 *    - 10 digits with leading 0: 0501234567
 *    - 12 digits with 972: 972501234567
 *
 *    US (10-digit number, area code 2–9):
 *    - 10 digits: 5551234567
 *    - 11 digits with leading 1: 15551234567
 *
 *    UK (10-digit local number):
 *    - 10 digits: 7123456789
 *    - 11 digits with leading 0: 07123456789
 *    - 12 digits with 44: 447123456789
 *
 * 3. If the digits match one of these patterns and the local part has a valid first digit
 *    (Israeli: 2–9; US/UK: 1–9), the value is valid.
 */
function phone_uk_us_il(node: FormKitNode): boolean {
    const raw = (node.value as string | undefined) ?? '';
    if (raw.trim() === '') return true;

    // Remove spaces, dashes, parentheses
    let value = raw.trim().replace(/[\s\-()]/g, '');

    // Convert 00 prefix to international format
    if (value.startsWith('00')) {
        value = value.slice(2);
    }

    // Remove leading +
    if (value.startsWith('+')) {
        value = value.slice(1);
    }

    // Must now contain only digits
    if (!/^\d+$/.test(value)) return false;

    // ---------------------------
    // 🇮🇱 ISRAEL
    // ---------------------------

    // International IL (972...)
    if (value.startsWith('972')) {
        const local = value.slice(3);

        // Mobile: 5X + 7 digits
        if (/^5[0-8]\d{7}$/.test(local)) return true;

        // Landline: 2|3|4|8|9 + 7 digits
        if (/^[23489]\d{7}$/.test(local)) return true;
    }

    // Local IL (0...)
    if (value.startsWith('0')) {
        const local = value.slice(1);

        if (/^5[0-8]\d{7}$/.test(local)) return true; // mobile
        if (/^[23489]\d{7}$/.test(local)) return true; // landline
    }

    // ---------------------------
    // 🇺🇸 UNITED STATES (NANP)
    // ---------------------------

    // With country code
    if (value.startsWith('1') && value.length === 11) {
        const local = value.slice(1);

        // Area code 2–9, central office 2–9
        if (/^[2-9]\d{2}[2-9]\d{6}$/.test(local)) return true;
    }

    // Without country code
    if (value.length === 10) {
        if (/^[2-9]\d{2}[2-9]\d{6}$/.test(value)) return true;
    }

    // ---------------------------
    // 🇬🇧 UNITED KINGDOM
    // ---------------------------

    // International UK (44...)
    if (value.startsWith('44')) {
        const local = value.slice(2);

        // Mobile (7XXXXXXXXX)
        if (/^7\d{9}$/.test(local)) return true;

        // Geographic (basic rule)
        if (/^[1-9]\d{9}$/.test(local)) return true;
    }

    // Local UK (0...)
    if (value.startsWith('0')) {
        const local = value.slice(1);

        if (/^7\d{9}$/.test(local)) return true; // mobile
        if (/^[1-9]\d{9}$/.test(local)) return true; // geo (loose)
    }

    return false;
}

// make the label of the input black
export default defaultConfig({
    locales: { en, he },
    locale: resolveLocale(),
    rules: { phone_uk_us_il },
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
                    '$reset rounded mx-1.5 bg-white ring-primary/90 peer-checked:border-primary relative block text-lg w-[1em] aspect-[1/1] border border-gray-300 text-transparent peer-checked:bg-primary/10 peer-checked:text-primary peer-focus-visible:ring-2 peer-focus-visible:ring-offset-1 select-none group-data-[disabled]:!cursor-not-allowed peer-disabled:bg-gray-100 group-data-[disabled]:grayscale shadow-sm peer-disabled:cursor-not-allowed group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none  formkit-decorator after:content-["✓"] after:absolute after:inset-0 after:flex after:items-center after:justify-center after:opacity-0 after:text-current after:text-[0.65em] after:leading-none peer-checked:after:opacity-100',
                input: '$reset appearance-none [color-scheme:light]  selection:bg-red-100 selection:text-gray-700 group-data-[has-overlay]:selection:!text-transparent peer pointer-events-none absolute h-0 w-0 overflow-hidden opacity-0 formkit-input',
            },
            radio: {
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
            email: {
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
            },
            tel: {
                inner: '$reset text-base flex items-center w-full py-2 px-3 rounded-lg bg-white focus-within:ring-1 focus-within:!ring-primary focus-within:!border-primary group-data-[invalid]:border-primary group-data-[invalid]:ring-1 group-data-[invalid]:ring-primary group-data-[disabled]:bg-gray-100 group-data-[disabled]:!cursor-not-allowed shadow-sm group-[&]/repeater:shadow-none group-[&]/multistep:shadow-none ',
                input: '$reset [color-scheme:light] selection:text-gray-700 group-data-[has-overlay]:selection:!text-transparent grow  text-base text-gray-700 text-ellipsis min-w-0 outline-hidden bg-transparent group-data-[disabled]:!cursor-not-allowed group-data-[prefix-icon]:!pl-0 group-data-[suffix-icon]:!pr-0 data-[placeholder]:text-gray-400 group-data-[multiple]:!p-0 selection:bg-red-100 border-none focus:ring-0 bg-none formkit-input',
            },
        }),
        rootClasses,
    },
});
