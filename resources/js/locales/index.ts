import { createI18n } from 'vue-i18n';
import en from './en.json';
import id from './id.json';

export type MessageSchema = typeof en;

// Date formats matching the datetime.json configuration
// Format: DD/MM/YY hh:mm for datetime, DD/MM/YYYY for date
const datetimeFormats = {
    'en': {
        short: {
            day: '2-digit',
            month: '2-digit',
            year: '2-digit',
        },
        long: {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        },
        datetime: {
            day: '2-digit',
            month: '2-digit',
            year: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        },
        datetimeFull: {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        },
    },
    'id': {
        short: {
            day: '2-digit',
            month: '2-digit',
            year: '2-digit',
        },
        long: {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        },
        datetime: {
            day: '2-digit',
            month: '2-digit',
            year: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        },
        datetimeFull: {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        },
    },
} as const;

export const i18n = createI18n<[MessageSchema], 'en' | 'id'>({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: {
        en,
        id,
    },
    datetimeFormats,
    numberFormats: {
        'en': {
            currency: {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            },
        },
        'id': {
            currency: {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            },
        },
    },
});

export default i18n;
