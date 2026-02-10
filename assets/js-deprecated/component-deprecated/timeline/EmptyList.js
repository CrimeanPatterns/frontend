import PropTypes from 'prop-types';
import React from 'react';
import Translator from '../../../bem/ts/service/translator';

const EmptyList = ({message = null}) => {
    if (!message) {
        message = Translator.trans('trips.no-trips.text');
    }

    return (
        <div className="no-result">
            <div className="no-result-item">
                <i className="icon-warning-small"/>
                <p>{message}</p>
            </div>
        </div>
    );
};

EmptyList.propTypes = {
    message: PropTypes.string,
};

export default EmptyList;