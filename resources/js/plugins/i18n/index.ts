import merge from 'lodash/merge';
import { createI18n } from 'vue-i18n';
import auth from './auth';
import common from './common';
import enums from './enums';
import ui from './ui';

const instance = createI18n({
    legacy: false,
    globalInjection: false,
    // Actual locale is set in app.ts
    // See https://stackoverflow.com/a/71449708/10679649
    // I couldn't figure out how to achieve this combined with passing in the props here
    // here we need to set the locale from the props
    locale: 'en',
    fallbackLocale: 'en',
    fallbackWarn: false,
    missingWarn: false,
    messages: merge(auth, common, enums, ui),
});
export default instance;

export const i18n = instance.global;
