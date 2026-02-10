import PropTypes from 'prop-types';
import React from 'react';

const PlanStart = React.forwardRef((props, ref) => {
    const {
        id,
    } = props;

    return (
        <div>
        </div>
    );
});

PlanStart.displayName = 'PlanStart';
PlanStart.propTypes = {
    // id: PropTypes.string.isRequired,
    // startDate: PropTypes.number.isRequired, // unix timestamp
    // endDate: PropTypes.number.isRequired, // unix timestamp
    // breakAfter: PropTypes.bool.isRequired, // can we set past/future breakpoint after this item?
    //
    // name: PropTypes.string.isRequired,
    // planId: PropTypes.number.isRequired,
    // canEdit: PropTypes.bool.isRequired,
    // map: PropTypes.shape({
    //     points: PropTypes.arrayOf(PropTypes.string),
    //     arrTime: PropTypes.string,
    // }).isRequired,
    // localDate: PropTypes.string.isRequired,
    // lastUpdated: PropTypes.number,
    // shareCode: PropTypes.string,
};

export default PlanStart;