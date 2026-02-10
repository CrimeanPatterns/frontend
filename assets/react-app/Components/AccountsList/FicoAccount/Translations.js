import Translator from '@Services/Translator';

Translator.trans('bad');
Translator.trans('fair');
Translator.trans('good');
Translator.trans('excellent');

Translator.trans(/** @Desc("Very Good") */ 'very-good');
