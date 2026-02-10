var a = {
    b: 45,
    c: 9000,
    delta: 'hello'
};
Translator.trans('hello.world3', {'%replace%': a.b, '%replace2%': a.delta});
a.xxx = Translator.trans(
    /** @Meaning("Bla bla bla") */
    'hello.world4', {
    'replace': a.b,
    'replace2': a.delta
}, 'my', 'fr');

var hello = Translator.trans('hello.world', {}, 'booking');

var hello2 = Translator.trans('hello.world2');

function abc()
{
    return Translator.trans(/** @Meaning("Bla bla bla") */ 'example.test', {}, 'messages', "fr");
}


Translator.trans(
    'example.test2', {}, /** @Desc("this is a test") */ 'booking' /** @Meaning("Bla bla bla") */);

/*

 */
var callback = function() {
    return Translator.transChoice(/** @Desc("{1} apple|]1,Inf] apples") */'example.number.apples', 5, {}, 'messages');
};


