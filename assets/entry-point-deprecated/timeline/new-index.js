import '../../../web/assets/awardwalletnewdesign/less/pages/trips.less';
import '../../bem/ts/starter';
import '../../less-deprecated/timeline.less';
import {render} from 'react-dom';
import React from 'react';
import TimelineApp from '../../js-deprecated/component-deprecated/timeline';

const appElement = document.getElementById('react-app');
const height = document.getElementsByClassName('page')[0].offsetHeight;
const allowShowDeletedSegments = appElement.dataset.allowShowDeleted === 'true';

render(
    <React.StrictMode>
        <TimelineApp containerHeight={height} allowShowDeletedSegments={allowShowDeletedSegments} />
    </React.StrictMode>,
    appElement
);