import PropTypes from 'prop-types';
import React from 'react';
import Translator from '../../../bem/ts/service/translator';

const ShowDeletedSegmentsLink = ({reverse}) => (
    <a href="#" className="deleted f-right">
        {
            !reverse &&
            <span>{Translator.trans('show.deleted.segments')}</span>
        }
        {
            reverse &&
            <span>{Translator.trans('hide.deleted.segments')}</span>
        }
    </a>
);

ShowDeletedSegmentsLink.propTypes = {
    reverse: PropTypes.bool,
};
ShowDeletedSegmentsLink.defaultProps = {
    reverse: false
};

export default ShowDeletedSegmentsLink;