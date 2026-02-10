export type AppLocale = 'en' | 'pt' | 'es' | 'de' | 'zh_TW' | 'zh_CN' | 'fr' | 'ru';
export type LocaleForIntl = 'en' | 'pt' | 'es' | 'de' | 'zh-TW' | 'zh-CN' | 'fr' | 'ru';
interface EnvironmentDataSet {
    authorized?: string;
    booking?: string;
    business?: string;
    debug?: string;
    enableTransHelper?: string;
    roleTranslator?: string;
    impersonated?: string;
    lang?: string;
    locale?: AppLocale;
    theme?: string;
    loadExternalScripts?: boolean;
}
export interface Environment {
    defaultLang: string;
    defaultLocale: string;
    authorized: boolean;
    booking: boolean;
    business: boolean;
    debug: boolean;
    enabledTransHelper: boolean;
    hasRoleTranslator: boolean;
    impersonated: boolean;
    lang: string;
    locale: AppLocale;
    localeForIntl: LocaleForIntl;
    theme?: string;
    loadExternalScripts: boolean;
}

export function extractOptions(): Environment {
    const env: EnvironmentDataSet = document.body.dataset;
    const defaultLang = 'en';
    const defaultLocale = 'en';
    env.locale;
    const appLocale: AppLocale = (env.locale as AppLocale | undefined) || defaultLocale;

    const result: Environment = {
        defaultLang: defaultLang,
        defaultLocale: defaultLocale,
        authorized: env.authorized === 'true',
        booking: env.booking === 'true',
        business: env.business === 'true',
        debug: env.debug === 'true',
        enabledTransHelper: env.enableTransHelper === 'true',
        hasRoleTranslator: env.roleTranslator === 'true',
        impersonated: env.impersonated === 'true',
        lang: env.lang || defaultLang,
        locale: env.locale || defaultLocale,
        loadExternalScripts: env.loadExternalScripts || false,
        localeForIntl: (env.locale?.replace('_', '-') as LocaleForIntl | undefined) || defaultLocale,
    };

    if (env.theme) {
        result.theme = env.theme;
    }

    return result;
}

export function isIos(): boolean {
    return /iPad|iPhone|iPod/i.test(navigator.userAgent);
}

export function isAndroid(): boolean {
    return /android/i.test(navigator.userAgent.toLowerCase());
}
