/*global $*/
/*eslint no-undef: "error"*/

import API from '../../../bem/ts/service/axios';
import React, { useEffect, useReducer, useState } from 'react';
import Router from '../../../bem/ts/service/router';
import Translator from '../../../bem/ts/service/translator';
import classNames from 'classnames';
import filter from 'lodash/filter';
import has from 'lodash/has';
import isEmpty from 'lodash/isEmpty';
/*eslint no-unused-vars: "jqueryui"*/
import ToolTip from '../ToolTip';
import jqueryui from 'jqueryui';
import map from 'lodash/map';
import mapValues from 'lodash/mapValues';
import uniqBy from 'lodash/uniqBy';
import values from 'lodash/values';

import { extractOptions } from '../../../bem/ts/service/env';
import MileValueBox from '../../component-deprecated/milevalue/milevalue-box';
import main from '../../../entry-point-deprecated/main';

const locale = extractOptions().locale;
const cache = {};
const numberFormat = new Intl.NumberFormat(locale.replace('_', '-'));
const currencyFormat = new Intl.NumberFormat(locale.replace('_', '-'), { style: 'currency', currency: 'USD' });

function HotelBrand(props) {
    if (isEmpty(props.providers)) {
        return (<div/>);
    }

    return (
        <div className="hotel-reward-data">
            {map((props.providers), (provider) =>
                <div key={provider.providerId} className={'hotel-reward'}>
                    <div className="header-data d-inline-block w-100">
                        <div className="float-left">
                            <h3>{provider.brandName}</h3>
                        </div>
                        <div className="float-right">
                            <span className={'hotel-brand-value-avg'}>
                                {Translator.trans('average-value')}: <b>{provider.formattedAvgPointValue}</b>
                                {Translator.trans('per-point')}</span>
                        </div>
                    </div>

                    <div className="table-scroll-container">

                        <table className="main-table no-border brand-hotels mobile-table-v2">
                            <thead>
                            <tr>
                                <th className={'hotel-name'}>{Translator.trans('itineraries.reservation.phones.title', {}, 'trips')}</th>
                                <th className={'hotel-value-redemption text-center'}>{Translator.trans('redemption-value')}</th>
                                <th className={'hotel-value-avg-percent text-center'}>{Translator.trans('percent-above-avg')}</th>
                                <th className={'hotel-value-avg-cashprice text-center'}>{Translator.trans('avg-cash-price-night')}</th>
                                <th className={'hotel-value-avg-pointprice text-center'}>{Translator.trans('avg-point-price-night')}</th>
                                <th className={'hotel-check-link'}></th>
                            </tr>
                            </thead>
                            <tbody>
                            {map((provider.hotels), (hotel) =>
                                <tr key={hotel.hotelId} className={classNames({
                                    'above-positive': hotel.avgAboveValue > 0,
                                    'above-negative': hotel.avgAboveValue < 0,
                                })}>
                                    <td className={'hotel-name'}>
                                        <b>{hotel.name}</b>
                                        <span className={'silver-text'}>{hotel.location}</span>
                                    </td>
                                    <td className={'text-center'} title={
                                        hotel.matchCount
                                            ? Translator.trans('based-on-last-bookings', {
                                                'number': numberFormat.format(hotel.matchCount),
                                                'as-of-date': ''
                                            })
                                            : ''
                                    }><span data-tip={''}
                                            data-role="tooltip">{numberFormat.format(hotel.pointValue)} ¢</span></td>
                                    <td className={'col-above text-center'}>{numberFormat.format(hotel.avgAboveValue)}%</td>
                                    <td className={'text-center'}>{currencyFormat.format(hotel.cashPrice)}</td>
                                    <td className={'text-center'}>{numberFormat.format(hotel.pointPrice)}</td>
                                    <td>{isEmpty(hotel.link)
                                        ? ''
                                        : <a className={'blue-link'} target="_blank"
                                             href={hotel.link} rel="noreferrer">{Translator.trans('check-availability')}</a>}</td>
                                </tr>
                            )}
                            </tbody>
                        </table>

                    </div>
                </div>
            )}
        </div>
    );
}

function FormAddress(props) {
    useEffect(() => {
        $('.search-input[name="q"]')
            .autocomplete({
                delay: 500,
                minLength: 2,
                search: function(event) {
                    if ($(event.target).val().length >= 2)
                        $(event.target).addClass('loading-input');
                    else
                        $(event.target).removeClass('loading-input');
                },
                open: function(event) {
                    $(event.target).removeClass('loading-input')
                },
                source: function(request, response) {
                    const self = this;
                    $(this).closest('.input-item').find('.address-timezone').text('');
                    let fragmentNamePos = request.term.indexOf('#'), hotelNameFragment = '';
                    if (-1 !== fragmentNamePos) {
                        hotelNameFragment = request.term.substr(1 + fragmentNamePos);
                        request.term = request.term.substr(0, fragmentNamePos);
                    }
                    $
                        .get(Router.generate('aw_hotelreward_geo', {
                            query: encodeURIComponent(request.term)
                        }))
                        .done(function(data) {
                            $(self.element).removeClass('loading-input');
                            if (!data) return;
                            response(data.map(function(item) {
                                let result = {};
                                if ('undefined' !== typeof item.place_id) {
                                    result.place_id = item.place_id;
                                }
                                if ('undefined' !== typeof item.formatted_address) {
                                    result.formatted_address =
                                        result.label =
                                            result.value = item.formatted_address;
                                }
                                if ('undefined' !== typeof item.extend) {
                                    result.extend = item.extend;
                                }
                                result.fragmentName = hotelNameFragment;
                                return result;
                            }));
                        })
                        .fail(function() {
                            return [];
                        });
                },
                select: function(event, ui) {
                    event.preventDefault();
                    props.setAddress(ui.item);
                    $(event.target)
                        .val(ui.item.value)
                        .trigger('change');
                    props.formAddressSubmit(event, ui.item);
                },
                create: function() {
                    $(this).data('ui-autocomplete')._renderItem = function(ul, item) {
                        let regex = new RegExp('(' + this.element.val() + ')', 'gi');
                        let itemLabel = item.label.replace(regex, "<b>$1</b>");
                        return $('<li></li>')
                            .data('item.autocomplete', item)
                            .append($('<a></a>').html(itemLabel))
                            .appendTo(ul);
                    };
                }
            });
    }, []);

    return (
        <form onSubmit={props.formAddressSubmit}>
            <div className="search column">
                <div className="row">
                    <div className="input">
                        <input className={classNames({
                            'input-item': true,
                            'search-input': true,
                            'search-input-fill': !isEmptyAddress(props.address)
                        })}
                               name="q"
                               type="text"
                               placeholder={Translator.trans('enter-city-state-country-search')}
                               autoComplete="off"
                               value={props.address.value}
                               onChange={(event) => props.setAddress(event.target.value)}
                        />
                        <a className={'clear-search'} href="" onClick={(event) => {
                            event.preventDefault();
                            props.setAddress({ 'value': '', 'place_id': '' });
                        }}><i className="icon-close-silver"></i></a>
                    </div>
                </div>
            </div>
        </form>
    );
}

function SearchResult(props) {
    let hotels = props.hotelsList;

    if (has(hotels, 'notFound') || has(hotels, 'success')) {
        return <div>
            <p id="notFound" className="no-result">
                <i className="icon-warning-small"></i>
                <span>{Translator.trans('no_results_found')}</span>
            </p>
        </div>;
    }

    let brands = uniqBy(values(mapValues(hotels, 'brandName')));
    let brandsMaxLengthName = 0;
    brands.map((name) => {
        if (name.length > brandsMaxLengthName) {
            brandsMaxLengthName = name.length;
        }
    });

    let filteredHotels = hotels;
    if ('' !== props.filterOptions.brand) {
        filteredHotels = filter(hotels, (hotel) => {
            return hotel.brandName == props.filterOptions.brand;
        });
    }
    let rowIndex = 0, isFirstNeutral = null, isFirstNegative = null;

    if (filteredHotels[0]?.avgAboveValue < 0) {
        isFirstNegative = isFirstNegative = false;
    }

    return (
        <div className="hotel-reward-place">
            <div className={'hotel-reward'}>
                <h1>{Translator.trans('search-result-near', { 'query': props.address.selected })}</h1>
                <div className="table-scroll-container">

                    <table className="main-table no-border brand-hotels mobile-table-v2">
                        <thead>
                        <tr>
                            <th className={'hotel-name'}>{Translator.trans('itineraries.reservation.phones.title', {}, 'trips')}</th>
                            <th className={'hotel-value-redemption'}>
                                <div className={'styled-select'} style={{ 'width': brandsMaxLengthName * 11 }}>
                                    <div>
                                        <select value={props.filterOptions.brand} onChange={(event) => {
                                            props.setFilterOptions({ 'brand': event.target.value });
                                        }}>
                                            <option value={''}>{Translator.trans('status.all')}</option>
                                            {map((brands), (brand) =>
                                                <option key={brand} value={brand}>{brand}</option>
                                            )}
                                        </select>
                                    </div>
                                </div>
                            </th>
                            <th className={'text-center'}>{Translator.trans('redemption-value')}</th>
                            <th className={'hotel-value-avg-percent text-center'}>{Translator.trans('percent-above-avg')}</th>
                            <th className={'hotel-value-avg-cashprice text-center'}>{Translator.trans('avg-cash-price-night')}</th>
                            <th className={'hotel-value-avg-pointprice text-center'}>{Translator.trans('avg-point-price-night')}</th>
                            <th className={'hotel-check-link'}></th>
                        </tr>
                        </thead>
                        <tbody>
                        {map((filteredHotels), (hotel) => {
                                ++rowIndex;
                                if (0 === hotel.avgAboveValue) {
                                    isFirstNeutral = null === isFirstNeutral;
                                }
                                if (hotel.avgAboveValue < 0) {
                                    isFirstNegative = null === isFirstNegative;
                                }

                                let cssClasses = classNames({
                                    'above-positive': hotel.avgAboveValue > 0,
                                    'above-negative': hotel.avgAboveValue < 0,
                                    'first-neutral': isFirstNeutral,
                                    'first-negative': isFirstNegative,
                                });

                                if (isFirstNeutral) isFirstNeutral = false;
                                if (isFirstNegative) isFirstNegative = false;

                                return <tr key={hotel.hotelId} className={cssClasses}>
                                    <td className={'hotel-name'}>
                                        <b>{hotel.name}</b>
                                        <span className={'silver-text'}>{hotel.location}</span>
                                    </td>
                                    <td>{hotel.brandName}</td>
                                    <td className={'text-center'} title={
                                        hotel.matchCount
                                            ? Translator.trans('based-on-last-bookings', {
                                                'number': numberFormat.format(hotel.matchCount),
                                                'as-of-date': '',
                                            })
                                            : ''
                                    }><span data-tip={''}
                                            data-role="tooltip">{numberFormat.format(hotel.pointValue)} ¢</span></td>
                                    <td className={'col-above text-center'}>{numberFormat.format(hotel.avgAboveValue)}%</td>
                                    <td className={'text-center'}>{currencyFormat.format(hotel.cashPrice)}</td>
                                    <td className={'text-center'}>{numberFormat.format(hotel.pointPrice)}</td>
                                    <td>{isEmpty(hotel.link)
                                        ? ''
                                        : <a className={'blue-link'} target="_blank"
                                             href={hotel.link} rel="noreferrer">{Translator.trans('check-availability')}</a>}</td>
                                </tr>
                            }
                        )}
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    );
}

function ContentData(props) {
    if (!isEmptyAddress(props.address) && !isEmpty(props.hotelsList)) {
        return SearchResult(props);
    }

    return <HotelBrand providers={props.primaryList}/>;
}

function HotelReward(props) {
    const [isLoading, setLoading] = useState(false);
    const [hotelsList, setHotelsList] = useState([]);
    const initialAddress = { label: '', place_id: '', value: '', selected: '' };

    function handlerSearchAddressState(prev, state) {
        if (isEmptyAddress(state)) {
            setHotelsList([]);
            setLocationState(null);
            return initialAddress;
        }
        if ('string' === typeof state) {
            return { ...initialAddress, ...{ value: state, selected: prev.selected } };
        }

        return state;
    }

    const [address, setAddress] = useReducer(handlerSearchAddressState, initialAddress);

    useEffect(() => {
        const route = Router.generate('aw_hotelreward_index');
        const [placeId, placeName] = decodeURI(decodeURIComponent(location.pathname.substr(location.pathname.indexOf(route) + route.length)))
            .replace(/^\/+|\/+$/g, '')
            .replace(/\+/g, ' ')
            .split('/');
        if (!isEmpty(placeId) && !isEmpty(placeName)) {
            const fetchAddress = { place_id: placeId, value: placeName, label: placeName, selected: placeName };
            setAddress(fetchAddress);
            handleFormAddressSubmit({ preventDefault: () => false }, fetchAddress);
        }

        window.onpopstate = function(event) {
            if (has(event.state, 'place')) {
                return handleFormAddressSubmit({ preventDefault: () => false }, event.state.place);
            }
            //setHotelsList([]);
            setAddress(initialAddress);
        };

    }, []);

    useEffect(() => {
        ToolTip();
    });

    const handleFormAddressSubmit = async (event, addr) => {
        event.preventDefault();
        setFilterOptions({ brand: '' });
        const placeData = has(addr, 'place_id') ? addr : address;
        setAddress({ ...placeData, ...{ selected: placeData.value } });
        const cacheKey = getCacheKey(placeData);
        if (has(cache, cacheKey)) {
            setLocationState(placeData.placeId, placeData.value);
            return setHotelsList(cache[cacheKey]);
        }

        setLoading(true);
        const response = (await API.get(Router.generate('aw_hotelreward_place', { place: placeData }))).data;
        if (has(response, 'placeId')) {
            cache[response.placeId] = response.hotels;
            setHotelsList(cache[response.placeId]);
            setLocationState(response.placeId, placeData.value);
        } else {
            setHotelsList(response);
        }
        setLoading(false);
    };

    const initialFilterOptions = { 'brand': '' };
    const [filterOptions, setFilterOptions] = useReducer((prev, state) => {
        return { ...prev, ...state }
    }, initialFilterOptions);

    return (
        <div className="main-blk hotel-reward-page">
            <h1>{Translator.trans('award-hotel-research-tool')}</h1>
            <div className="main-blk-content">
                <FormAddress address={address} setAddress={setAddress} formAddressSubmit={handleFormAddressSubmit}
                             setHotels={setHotelsList}
                />
                <ContentData address={address}
                             primaryList={props.primaryList}
                             hotelsList={hotelsList}
                             filterOptions={filterOptions} setFilterOptions={setFilterOptions}
                />
                <div className={classNames({ 'ajax-loader': true, 'ajax-loader-process': isLoading })}>
                    <div className="loading"></div>
                </div>
                <MileValueBox providers={props.primaryList}/>
            </div>
        </div>
    );
}

function isEmptyAddress(state) {
    return '' === state || (has(state, 'place_id') && isEmpty(state.place_id) && isEmpty(state.value));
}

function getCacheKey(data) {
    const fragmentName = has(data, 'fragmentName') ? data.fragmentName : '';
    if (has(data, 'place_id') && !isEmpty(data.place_id)) {
        return data.place_id + fragmentName;
    }
    if (!isEmpty(data.value)) {
        return '_' + data.value + fragmentName;
    }

    return 0;
}

function setLocationState(placeId, placeName) {
    if (null === placeId) {
        return window.history.pushState({}, '', Router.generate('aw_hotelreward_index'));
    }

    return window.history.pushState({
        place: {
            place_id: placeId,
            label: placeName,
            value: placeName,
            selected: placeName
        }
    }, '', Router.generate('aw_hotelreward_index_place', {
        placeName: encodeURIComponent(placeName)
            .replace(/%20/g, '+')
            .replace(/%2C/g, ',')
    }));
}

export default HotelReward;