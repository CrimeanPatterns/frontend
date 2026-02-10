import { currencyFormat, numberFormat } from '../../../bem/ts/service/formatter';
import React, { useMemo, useState } from 'react';
import Translator from '../../../bem/ts/service/translator';
import classNames from 'classnames';

function GroupList(props) {
    const [groups, setGroups] = useState(props.groups);

    return (
        <div className={'flight-search-wrap'}>
            <div className="flight-search">
                {0 !== props.groups.length
                    ? <ListResult groups={groups} skyLink={props.skyLink}/>
                    : (props.isFormFilled ? <SearchNotFound/> : '')
                }
            </div>
        </div>
    );
}

const ListResult = React.memo(function ListResult(props) {
    return (
        <div id="searchResult" className="flight-search-result">
            <div className="flight-search-wrap">
                <div className="flight-search-result-caption">
                    <div className="flight-search-dep">{Translator.trans('loyalty-program')}</div>
                    <div className="flight-search-layover">
                        {Translator.trans('layover', {}, 'trips')}
                    </div>
                    <div className="flight-search-arr"></div>
                    <div className="flight-search-operating">
                        {Translator.trans('itineraries.trip.air.airline-name', {}, 'trips')}
                    </div>
                    <div className="flight-search-miles-spent"
                         title="TotalMilesSpent">{Translator.trans('points')}</div>
                    <div className="flight-search-taxes" title="TotalTaxesSpent">{Translator.trans('taxes')}</div>
                    <div className="flight-search-altcost" title="AlternativeCost">
                        {Translator.trans('itineraries.cost', {}, 'trips')}
                    </div>
                    <div className="flight-search-mile-value" title="MileValue">
                        {Translator.trans('coupon.value')}
                    </div>
                    <div className="flight-search-reduce"></div>
                    <div className="flight-search-debug-id">id</div>
                </div>
                {props.groups.map((provider, index) =>
                    <ProviderStack provider={provider} index={index} key={provider.providerId} skyLink={props.skyLink}/>
                )}
            </div>
        </div>
    );
});

const ProviderStack = React.memo(function ProviderStack(props) {
    const provider = props.provider;
    const [isExpanded, setExpanded] = useState(0 === props.index);

    return (
        <div className={classNames({
            'flight-search-provider': true,
            'flight-search-provider--expanded': isExpanded,
        })}>
            <div className="flight-search-head">
                <div className="flight-search-airline">
                    <a className="flight-search-items-toggle" href="#"
                       onClick={useMemo(() => () => setExpanded(!isExpanded))}>
                        <i className="icon-arrow-right-dark"></i> {provider.name}
                    </a>
                </div>
                <div className="flight-search-miles-spent">
                    {numberFormat(provider.avg.TotalMilesSpent)}
                </div>
                <div className="flight-search-taxes">
                    {currencyFormat(provider.avg.TotalTaxesSpent)}
                </div>
                <div className="flight-search-altcost">
                    <a href={props.skyLink} target="_blank" rel="noreferrer">{currencyFormat(provider.avg.AlternativeCost, 'USD', { maximumFractionDigits: 0 })}</a>
                </div>
                <div className="flight-search-mile-value">
                    {numberFormat(provider.avg.MileValue)}
                    {Translator.trans('us-cent-symbol')}
                </div>
                <div className="flight-search-reduce"></div>
                <div className="flight-search-debug-id"></div>
            </div>
            <ListItems items={provider.items} skyLink={props.skyLink} />
        </div>
    );
});

const ListItems = React.memo(function ListItems(props) {
    return (
        <div className="flight-search-body">
            {props.items.map(item =>
                <div key={`${item.ProviderID}-${item.MileRoute}`} className="flight-search-item">
                    <div className="flight-search-dep">
                        <div className="flight-search-location">
                            <div className="flight-search-code">{item.dep.code}</div>
                            <div className="flight-search-name">{item.dep.location}</div>
                        </div>
                    </div>
                    <div className="flight-search-layover flight-search-stops">
                        {item.stops.map(stop =>
                            <div key={stop.code} className="flight-search-location">
                                <div className="flight-search-code">{stop.code}</div>
                                <div className="flight-search-name">{stop.location}</div>
                            </div>
                        )}
                    </div>
                    <div className="flight-search-arr">
                        <div className="flight-search-location">
                            <div className="flight-search-code">{item.arr.code}</div>
                            <div className="flight-search-name">{item.arr.location}</div>
                        </div>
                    </div>
                    <div className="flight-search-operating" dangerouslySetInnerHTML={{ __html: item.airline }}></div>
                    <div className="flight-search-miles-spent">{numberFormat(item.TotalMilesSpent)}</div>
                    <div className="flight-search-taxes">{currencyFormat(item.TotalTaxesSpent)}</div>
                    <div className="flight-search-altcost">
                        <a href={props.skyLink} target="_blank" rel="noreferrer">{currencyFormat(item.AlternativeCost, 'USD', { maximumFractionDigits: 0 })}</a>
                    </div>
                    <div className="flight-search-mile-value">
                        {item.MileValue.raw}
                        {Translator.trans('us-cent-symbol')}
                    </div>
                    <div className="flight-search-reduce">
                        {undefined !== item.arr.reduce
                            ? <a className="btn-silver"
                                 href={item.arr.reduce.link}>{Translator.trans('search')} {item.arr.reduce.location}</a>
                            : null
                        }
                    </div>
                    <div className="flight-search-debug-id">
                        {item._debug.MileValueID.map(id =>
                            <div key={id}>
                                <a href={`/manager/list.php?Schema=MileValue&MileValueID=` + id}
                                   target="mv">{id}</a>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
});

function SearchNotFound() {
    return (
        <div className="routes-not-found">
            <div className="alternative-path">
                <i className="icon-warning-small"></i>
                <p dangerouslySetInnerHTML={{ __html: Translator.trans('we-not-find-any-result', { 'break': '<br/>' }) }}></p>
            </div>
        </div>
    );
}

export default GroupList;