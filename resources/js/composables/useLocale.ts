import type { Ref } from 'vue';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';

export type Locale = 'en' | 'id';

export type UseLocaleReturn = {
    locale: Ref<Locale>;
    updateLocale: (value: Locale) => void;
};

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

export function useLocale(): UseLocaleReturn {
    const { locale: i18nLocale } = useI18n();
    // Initialize from i18n's current value (which was set from server in app.ts)
    const locale = ref<Locale>(i18nLocale.value as Locale);

    const updateLocale = (value: Locale) => {
        locale.value = value;
        i18nLocale.value = value;
        localStorage.setItem('locale', value);
        setCookie('locale', value);
        
        // Persist to server - save user's language preference to database
        router.patch('/settings/locale', 
            { locale: value }, 
            { 
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Sync locale ref with i18n locale changes
    watch(i18nLocale, (newLocale) => {
        locale.value = newLocale as Locale;
    });

    return {
        locale,
        updateLocale,
    };
}
