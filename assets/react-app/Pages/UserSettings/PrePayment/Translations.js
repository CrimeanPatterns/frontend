import { Translator } from '@Services/Translator';

Translator.trans('pre-payment.title', {
    email: '',
});

Translator.trans(/** @Desc("Period") */ 'pre-payment.dropdown.label');

Translator.trans(/** @Desc("Your Price for AwardWallet") */ 'pre-payment.your.price');

Translator.trans(/** @Desc("Proceed to Checkout") */ 'pre-payment.proceed.checkout');

Translator.trans(
    /**@Desc("%highlight_on%Important:%highlight_off% AwardWallet is unable to cancel recurring payments set up with Apple. After checkout, you will receive instructions to cancel payments via Apple.")*/ 'pre-payment.apple.subscriber.warning',
    {
        highlight_on: '',
        highlight_off: '',
    },
);

Translator.trans(
    /**@Desc("You are making a one-time payment today which will extend your AwardWallet Plus membership through %date%.")*/ 'pre-payment.apple.subscriber.description',
    {
        date: '',
    },
);

Translator.trans(
    /**@Desc("Uncheck this box to make a one-time payment without creating an ongoing subscription. If you choose this option, your account will be automatically downgraded on
    %date%.")*/ 'pre-payment.new.subscription.checkbox.explanation',
    {
        date: '',
    },
);

Translator.trans(
    /**@Desc("Keep me subscribed to AwardWallet Plus at $49.99 per year")*/ 'pre-payment.new.subscription.checkbox.label',
);
