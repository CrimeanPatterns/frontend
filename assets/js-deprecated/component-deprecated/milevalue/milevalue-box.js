import React from 'react';
import Router from '../../../bem/ts/service/router';
import Translator from '../../../bem/ts/service/translator';
import map from 'lodash/map';

function MileValueBox(props) {

    return (
        <div style={{padding : '15px'}}>
            <div id="mileValueBox" className="chart__filter">
                <p dangerouslySetInnerHTML={{
                    __html : Translator.trans('we-calculate-points-evaluating-bookings-points', {
                        'link_on' : `<a href=${Router.generate('aw_points_miles_values')}>`,
                        'link_off' : `</a>`
                    })
                }}/>
                <div className="chart__filter_container">
                    <div className="chart__filter_wrap">
                        {map((props.providers), (provider) =>
                            <div className="chart__filter_block" key={provider.providerId}>
                                <span>{provider.brandName}</span>
                                <div className="curr-value">
                                    <strong>{provider.formattedAvgPointValue}</strong>
                                </div>
                            </div>
                        )}
                        <div className="t-right">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default MileValueBox;