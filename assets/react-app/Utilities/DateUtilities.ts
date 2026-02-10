import { Locale } from 'date-fns';
import { LocaleForIntl } from '@Services/Env';
import { de } from 'date-fns/locale/de';
import { enUS } from 'date-fns/locale/en-US';
import { es } from 'date-fns/locale/es';
import { fr } from 'date-fns/locale/fr';
import { pt } from 'date-fns/locale/pt';
import { ru } from 'date-fns/locale/ru';
import { zhCN } from 'date-fns/locale/zh-CN';
import { zhTW } from 'date-fns/locale/zh-TW';

export function getDateFNSLocale(locale: LocaleForIntl): Locale {
    switch (locale) {
        case 'de': {
            return de;
        }
        case 'en': {
            return enUS;
        }
        case 'es': {
            return es;
        }
        case 'fr': {
            return fr;
        }
        case 'pt': {
            return pt;
        }
        case 'ru': {
            return ru;
        }
        case 'zh-CN': {
            return zhCN;
        }
        case 'zh-TW': {
            return zhTW;
        }
        default:
            return enUS;
    }
}
