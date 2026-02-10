import '../../bem/ts/starter';
import '../../less-deprecated/hotel-reward.less';
import {render} from 'react-dom';
import HotelReward from '../../js-deprecated/component-deprecated/hotel-reward/HotelReward';
import React from 'react';

const contentElement = document.getElementById('content');
const primaryList = JSON.parse(contentElement.dataset.primaryList);

render(
    <React.StrictMode>
        <HotelReward primaryList={primaryList}/>
    </React.StrictMode>,
    contentElement
);