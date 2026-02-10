import * as _ from 'lodash';
import DateTimeDiff from '../../../../bem/ts/service/date-time-diff';
import PropTypes from 'prop-types';
import React from 'react';
import classNames from 'classnames';

const DateSegment = React.forwardRef((props, ref) => {
    const {
        id,
        startDate,
        localDate,
        localDateISO,
    } = props;

    function getRelativeDate() {
        return DateTimeDiff.longFormatViaDates(new Date(), new Date(localDateISO));
    }

    function getDaysNumberFromToday() {
        const diff = Math.abs(new Date(startDate * 1000) - new Date());

        return Math.floor(diff / 1000 / 60 / 60 / 24);
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function getDateBlock() {
        const relativeDate = getRelativeDate();

        if (getDaysNumberFromToday() <= 30) {
            return (
                <>
                    {capitalize(relativeDate)}
                    <span className="date">{capitalize(localDate)}</span>
                </>
            );
        }

        return (
            <>
                {capitalize(localDate)}
                <span className="date">{capitalize(relativeDate)}</span>
            </>
        );
    }

    const className = classNames({
        'trip-blk': true,
        disable: (() => {
            const dayStart = new Date();
            dayStart.setHours(0,0,0,0);
            return startDate <= (dayStart / 1000);
        })(),
    });

    return (
        <div className={className} ref={ref} id={id}>
            <div data-id={id} className="date-blk">
                <div>{getDateBlock()}</div>
            </div>
        </div>
    );
});

DateSegment.displayName = 'Date';
DateSegment.propTypes = {
    // id: PropTypes.string.isRequired,
    // startDate: PropTypes.number.isRequired, // unix timestamp
    // endDate: PropTypes.number.isRequired, // unix timestamp
    // startTimezone: PropTypes.string.isRequired,
    // breakAfter: PropTypes.bool.isRequired, // can we set past/future breakpoint after this item?
    //
    // localDate: PropTypes.string.isRequired,
    // localDateISO: PropTypes.string.isRequired,
    // localDateTimeISO: PropTypes.string.isRequired,
    // createPlan: PropTypes.bool.isRequired,
};

export default DateSegment;