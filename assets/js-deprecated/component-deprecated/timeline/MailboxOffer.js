import PropTypes from 'prop-types';
import React from 'react';
import Router from '../../../bem/ts/service/router';
import Translator from '../../../bem/ts/service/translator';

const MailboxOffer = ({forwardingEmail}) => (
    <div
        className="trip-info"
        dangerouslySetInnerHTML={{__html: Translator.trans(
            'scanner.link_mailbox_or_forward',
            {
                'link_on': `<a href="${Router.generate('aw_usermailbox_view')}" class="blue-link">`,
                'link_off': '</a>',
                'email': `<span class="user-email">${forwardingEmail}</span>`
            }
        )}} />
);

MailboxOffer.propTypes = {
    forwardingEmail: PropTypes.string.isRequired,
};

export default MailboxOffer;