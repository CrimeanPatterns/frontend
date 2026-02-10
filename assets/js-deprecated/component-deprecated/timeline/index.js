import { LazyList } from '../infiniteList';
import API from '../../../bem/ts/service/axios';
import EmptyList from './EmptyList';
import MailboxOffer from './MailboxOffer';
import PastSegmentsLoaderLink from './PastSegmentsLoaderLink';
import PropTypes from 'prop-types';
import React, { useEffect, useState } from 'react';
import Router from '../../../bem/ts/service/router';
import SegmentTypes from './segment';
import ShowDeletedSegmentsLink from './ShowDeletedSegmentsLink';
import Spinner from '../Spinner';
import _ from 'lodash';

const TimelineApp = ({ containerHeight, allowShowDeletedSegments }) => {
    const [forwardingEmail, setForwardingEmail] = useState(null);
    const [showDeleted, setShowDeleted] = useState(true);
    const [segments, setSegments] = useState([]);
    const [loadingApp, setLoadingApp] = useState(true);
    const emptyList = segments.length === 0;

    async function loadMore() {
        const params = { showDeleted: showDeleted ? 1 : 0 };
        const before = _.get(_.last(segments), 'startDate', null);

        if (!_.isNull(before)) {
            params.before = before;
        }

        let data;
        try {
            const response = await API.get(Router.generate('aw_timeline_data', params));
            data = response.data;
            // eslint-disable-next-line
        } catch (e) {}

        if (data) {
            const { segments: newSegments, forwardingEmail } = data;

            setSegments((segments) => {
                return _.unionBy(segments, newSegments ? newSegments : [], (segment) => segment.id);
            });
            setForwardingEmail(forwardingEmail);
            setLoadingApp(false);
        }
    }

    function itemKey(index) {
        return segments[index].id;
    }

    function SegmentRenderer(index, ref) {
        const segmentData = segments[index];
        // eslint-disable-next-line react/prop-types
        const { type: segmentType, ...segmentProps } = segmentData;

        if (_.has(SegmentTypes, segmentType)) {
            return React.createElement(SegmentTypes[segmentType], { ...segmentProps, ref });
        }

        return React.createElement(SegmentTypes.segment, { ...segmentProps, ref });
    }

    const TimelineContainer = React.forwardRef(({ children, ...props }, ref) => {
        return (
            <>
                {!_.isEmpty(forwardingEmail) && <MailboxOffer forwardingEmail={forwardingEmail} />}
                <PastSegmentsLoaderLink />
                {allowShowDeletedSegments && <ShowDeletedSegmentsLink reverse={showDeleted} />}
                <div ref={ref} {...props} className={'trip-list'}>
                    {children}
                </div>
            </>
        );
    });
    TimelineContainer.displayName = 'TimelineContainer';
    TimelineContainer.propTypes = {
        children: PropTypes.node,
    };

    useEffect(() => {
        loadMore();
        // eslint-disable-next-line
    }, []);

    if (loadingApp) {
        return (
            <div className="trip">
                <Spinner />
            </div>
        );
    }

    return (
        <div className="trip" style={{ height: containerHeight }}>
            {!emptyList && (
                <LazyList
                    itemCount={segments.length}
                    loadMore={loadMore}
                    height={containerHeight}
                    listProps={{
                        itemKey,
                        innerElementType: TimelineContainer,
                        style: {
                            overflowY: 'scroll',
                        },
                        innerStyle: {
                            width: 'inherit',
                        },
                    }}
                >
                    {SegmentRenderer}
                </LazyList>
            )}
            {emptyList && <EmptyList />}
        </div>
    );
};

TimelineApp.propTypes = {
    containerHeight: PropTypes.number.isRequired,
    allowShowDeletedSegments: PropTypes.bool,
};
TimelineApp.defaultProps = {
    allowShowDeletedSegments: false,
};

export default TimelineApp;
