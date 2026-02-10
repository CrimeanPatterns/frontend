import PropTypes from 'prop-types';
import React from 'react';

const PlanEnd = React.forwardRef((props, ref) => {
    const {
        id,
    } = props;

    return (
        <div>
        </div>
    );
});

PlanEnd.displayName = 'PlanEnd';
PlanEnd.propTypes = {
    // id: PropTypes.string.isRequired,
    // startDate: PropTypes.number.isRequired, // unix timestamp
    // endDate: PropTypes.number.isRequired, // unix timestamp
    // startTimezone: PropTypes.string.isRequired,
    // breakAfter: PropTypes.bool.isRequired, // can we set past/future breakpoint after this item?
    //
    // name: PropTypes.string.isRequired,
    // planId: PropTypes.number.isRequired,
    // localDate: PropTypes.string.isRequired,
    // canEdit: PropTypes.bool.isRequired,
};

export default PlanEnd;