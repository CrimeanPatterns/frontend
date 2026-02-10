import PropTypes from 'prop-types';
import React from 'react';
import Translator from '../../../../bem/ts/service/translator';
import _ from 'lodash';
import classNames from 'classnames';

const Segment = React.forwardRef((props, ref) => {
    const {
        id,
        icon,
        changed,
        endDate,
        details,
        deleted = false,
        startTimezone,
        prevTime,
        localTime,
        title,
        confNo,
        map,
    } = props;
    const className = classNames({
        disable: endDate <= Date.now() / 1000,
        'no-hand': !details,
        'deleted-segment': deleted,
    });

    const tripRowClassIcon = icon.split(' ').shift();
    const tripRowClass = classNames({
        'trip-row': true,
        ['trip--' + tripRowClassIcon]: true,
        error: changed,
        active: false,
    });

    function getLocalTime(time) {
        const parts = time.split(' ');

        if (parts.length > 1) {
            return (
                <>
                    {parts[0]}
                    <span>{parts[1]}</span>
                </>
            );
        }

        return time;
    }

    return (
        <div className={className} ref={ref}>
            {deleted && (
                <div className="deleted-message">
                    <span>{Translator.trans('segment.deleted')}</span>
                </div>
            )}
            <div className={tripRowClass}>
                <div className="time">
                    <div className="time-zone">{startTimezone}</div>
                    <div className="time-item">
                        {_.isString(prevTime) && changed && <p className="old-time">{prevTime}</p>}
                        <p>{getLocalTime(localTime)}</p>
                    </div>
                </div>
                <div
                    className={classNames({
                        'trip-title': true,
                        [icon]: true,
                    })}
                    data-id={id}
                >
                    <div className="item">
                        <div className="arrow">
                            <i className="icon-silver-arrow-down"></i>
                        </div>
                        <div className="prev">
                            <div className="prev-item">
                                <i
                                    className={classNames({
                                        ['icon-' + icon]: true,
                                    })}
                                ></i>
                            </div>
                        </div>
                        <div className="title">
                            <h3 dangerouslySetInnerHTML={{ __html: title }}></h3>
                        </div>
                        <div className="number">
                            {_.isString(confNo) && (
                                <>
                                    {Translator.trans('timeline.section.conf')} {confNo}
                                </>
                            )}
                        </div>
                        {_.isArray(map) && (
                            <div className="map">
                                <img
                                    style={{ width: '44px', height: '44px' }}
                                    src="/trips/gcmap.php?code=45.4420641%2C%2013.5237425&size=88x88"
                                    alt="map"
                                />
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
});

Segment.displayName = 'Segment';
Segment.propTypes = {
    id: PropTypes.string.isRequired,
    startDate: PropTypes.number.isRequired, // unix timestamp
    endDate: PropTypes.number.isRequired, // unix timestamp
    startTimezone: PropTypes.string.isRequired,
    breakAfter: PropTypes.bool.isRequired, // can we set past/future breakpoint after this item?

    icon: PropTypes.string.isRequired,
    localTime: PropTypes.string.isRequired,
    localDate: PropTypes.number.isRequired,
    localDateISO: PropTypes.string.isRequired,
    localDateTimeISO: PropTypes.string.isRequired,
    map: PropTypes.shape({
        points: PropTypes.arrayOf(PropTypes.string),
        arrTime: PropTypes.oneOfType([PropTypes.string, PropTypes.bool]),
    }),
    details: PropTypes.shape({
        accountId: PropTypes.number,
        agentId: PropTypes.number,
        refreshLink: PropTypes.string,
        autoLoginLink: PropTypes.string,
        bookingLink: PropTypes.shape({
            info: PropTypes.string.isRequired,
            url: PropTypes.string.isRequired,
            formFields: PropTypes.shape({
                destination: PropTypes.string.isRequired,
                checkinDate: PropTypes.string.isRequired,
                checkoutDate: PropTypes.string.isRequired,
                url: PropTypes.string.isRequired,
            }).isRequired,
        }),
        canEdit: PropTypes.bool,
        canCheck: PropTypes.bool,
        canAutoLogin: PropTypes.bool,
        Status: PropTypes.string,
        shareCode: PropTypes.string,
        monitoredStatus: PropTypes.string,
        columns: PropTypes.arrayOf(
            PropTypes.oneOfType([
                PropTypes.shape({
                    type: 'arrow',
                }),
                PropTypes.shape({
                    type: 'info',
                    rows: PropTypes.arrayOf(
                        PropTypes.oneOfType([
                            PropTypes.shape({
                                type: 'arrow',
                            }),
                            PropTypes.shape({
                                type: 'checkin',
                                date: PropTypes.string.isRequired,
                                nights: PropTypes.number.isRequired,
                            }),
                            PropTypes.shape({
                                type: 'datetime',
                                date: PropTypes.string.isRequired,
                                time: PropTypes.string.isRequired,
                                prevTime: PropTypes.string,
                                prevDate: PropTypes.string,
                                timestamp: PropTypes.number,
                                timezone: PropTypes.string,
                                formattedDate: PropTypes.string,
                                arrivalDay: PropTypes.string,
                            }),
                            PropTypes.shape({
                                type: 'text',
                                text: PropTypes.string.isRequired,
                                geo: PropTypes.shape({
                                    country: PropTypes.string,
                                    state: PropTypes.string,
                                    city: PropTypes.string,
                                }),
                            }),
                            PropTypes.shape({
                                type: 'pairs',
                                pairs: PropTypes.arrayOf(PropTypes.object).isRequired,
                            }),
                            PropTypes.shape({
                                type: 'pair',
                                name: 'Guests',
                                value: PropTypes.number.isRequired,
                            }),
                            PropTypes.shape({
                                type: 'parkingStart',
                                date: PropTypes.string.isRequired,
                                days: PropTypes.number.isRequired,
                            }),
                            PropTypes.shape({
                                type: 'pickup',
                                date: PropTypes.string.isRequired,
                                days: PropTypes.number.isRequired,
                            }),
                            PropTypes.shape({
                                type: 'pickup.taxi',
                                date: PropTypes.string.isRequired,
                                time: PropTypes.string.isRequired,
                            }),
                            PropTypes.shape({
                                type: 'dropoff',
                                date: PropTypes.string.isRequired,
                                time: PropTypes.string.isRequired,
                            }),
                            PropTypes.shape({
                                type: 'airport',
                                text: PropTypes.shape({
                                    place: PropTypes.string.isRequired,
                                    code: PropTypes.string.isRequired,
                                }).isRequired,
                            }),
                        ]),
                    ),
                }),
            ]),
        ),
        Fax: PropTypes.string,
        GuestCount: PropTypes.number,
        KidsCount: PropTypes.number,
        Rooms: PropTypes.number,
        RoomLongDescriptions: PropTypes.string,
        RoomShortDescriptions: PropTypes.string,
        RoomRate: PropTypes.string,
        RoomRateDescription: PropTypes.string,
        TravelerNames: PropTypes.string,
        CancellationPolicy: PropTypes.string,
        CarDescription: PropTypes.string,
        LicensePlate: PropTypes.string,
        SpotNumber: PropTypes.string,
        CarModel: PropTypes.string,
        CarType: PropTypes.string,
        PickUpFax: PropTypes.string,
        DropOffFax: PropTypes.string,
        DinerName: PropTypes.string,
        CruiseName: PropTypes.string,
        Deck: PropTypes.string,
        CabinNumber: PropTypes.string,
        ShipCode: PropTypes.string,
        ShipName: PropTypes.string,
        ShipCabinClass: PropTypes.string,
        Smoking: PropTypes.string,
        Stops: PropTypes.number,
        ServiceClasses: PropTypes.string,
        ServiceName: PropTypes.string,
        CarNumber: PropTypes.string,
        AdultsCount: PropTypes.number,
        Aircraft: PropTypes.string,
        TicketNumbers: PropTypes.string,
        TravelledMiles: PropTypes.string,
        Meal: PropTypes.string,
        BookingClass: PropTypes.string,
        CabinClass: PropTypes.string,
        phone: PropTypes.string,
    }),
    origins: PropTypes.shape({
        auto: PropTypes.arrayOf(
            PropTypes.oneOfType([
                PropTypes.shape({
                    type: 'account',
                    accountId: PropTypes.number.isRequired,
                    provider: PropTypes.string.isRequired,
                    accountNumber: PropTypes.string.isRequired,
                    owner: PropTypes.string.isRequired,
                }),
                PropTypes.shape({
                    type: 'confNumber',
                    provider: PropTypes.string.isRequired,
                    confNumber: PropTypes.string.isRequired,
                }),
                PropTypes.shape({
                    type: 'email',
                    from: PropTypes.number.isRequired,
                    email: PropTypes.string,
                }),
            ]),
        ),
        manual: PropTypes.bool,
    }),
    confNo: PropTypes.string,
    group: PropTypes.string,
    changed: PropTypes.bool,
    deleted: PropTypes.bool,
    lastSync: PropTypes.number,
    lastUpdated: PropTypes.number,
    title: PropTypes.string,
    prevTime: PropTypes.string,
    segments: PropTypes.number,
};

export default Segment;
