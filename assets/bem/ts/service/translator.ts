import Translator from '../../../../web/assets/common/js/translator'
import type {Translator as TranslatorNs} from '../global';

// global variable for legacy code only
const Service = Translator as unknown as TranslatorNs.ITranslator;
window.Translator = Service;
require('../../../../web/assets/translations/config');

export default Service;