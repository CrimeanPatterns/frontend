import PropTypes from 'prop-types';
import React from 'react';
import Translator from '../../../bem/ts/service/translator';

const PastSegmentsLoaderLink = ({loading = false}) => (
    <>
        <a href="#" className="past-travel">
            <i className="icon-double-arrow-up-dark" />
            {
                !loading &&
                <span>{ Translator.trans('timeline.past.travel') }</span>
            }
        </a>

        {
            loading &&
            <a href="" className="past-travel">
                <div className="loader" />
            </a>
        }
    </>
);

PastSegmentsLoaderLink.propTypes = {
    loading: PropTypes.bool,
};

export default PastSegmentsLoaderLink;