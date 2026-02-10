import React, { useEffect, useMemo, useRef, useState } from 'react';

import Router from '../../../bem/ts/service/router';
import Translator from '../../../bem/ts/service/translator';
import main from '../../../entry-point-deprecated/main';

const Form = React.memo(function Form(props) {
    const form = props.form;
    const blankData = { id: '', type: '', value: '', name: '', query: '' };

    const [from, setFrom] = useState(form?.from || blankData);
    const [to, setTo] = useState(form?.to || blankData);
    const [type, setType] = useState(form?.type || blankData);
    const [classes, setClass] = useState(form?.class || blankData);

    const handleFormSubmit = useMemo(() => (event) => {
        '' === from.id ? setFrom({ ...from, ...{ id: from.value } }) : null;
        '' === to.id ? setTo({ ...to, ...{ id: to.value } }) : null;
    });

    useEffect(() => {
        main.initDropdowns('#flightSearch', {
            ofParent: 'div.flightsearch-form-menu',
            position: { my: 'left-24 top+16' }
        });
    });

    return (
        <form id="flightSearchForm" method="get" onSubmit={handleFormSubmit}>
            <div id="flightSearch" className="flight-search-form">
                <div className="flightsearch-form-place flightseach-form-from" type={from.type}>
                    <AutoComplete getValues={from} setValues={setFrom} placeholder={Translator.trans('from')}/>
                    <input name="from" type="hidden" value={from.query || from.value} data-query/>
                </div>

                <div className="flightsearch-form-gap">
                    <i className="icon-air-two-way"></i>
                </div>

                <div className="flightsearch-form-place flightseach-form-to" type={to.type}>
                    <AutoComplete getValues={to} setValues={setTo} placeholder={Translator.trans('to')}/>
                    <input name="to" type="hidden" value={to.query || to.value} data-query/>
                </div>

                <div className="flightsearch-form-menu flightsearch-form-trip">
                    <input type="hidden" name="type" value={type.id}/>
                    <a className="rel-this" href="" data-target="flight-type">
                        <span>{type.name}</span>
                        <i className="icon-silver-arrow-down"></i>
                    </a>
                    <ListSubMenu id="flight-type" items={form.types} setValue={setType}/>
                </div>

                <div className="flightsearch-form-menu flightsearch-form-class">
                    <input type="hidden" name="class" value={classes.id}/>
                    <a className="rel-this" href="" data-target="flight-class">
                        <span>{classes.name}</span>
                        <i className="icon-silver-arrow-down"></i>
                    </a>
                    <ListSubMenu id="flight-class" items={form.classes} setValue={setClass}/>
                </div>

                <div className="flightsearch-form-submit">
                    <button className="btn-blue" type="submit">{Translator.trans('search')}</button>
                </div>

            </div>
        </form>
    );
});

function AutoComplete(props) {
    const element = useRef(null);

    useEffect(() => {
        $(element.current)
            .off('keydown keyup change')
            .on('keyup change', function(e) {
                if ($(element.current).val() !== $(element.current).data('value')) {
                    $(element.current).removeAttr('data-value').parent().removeAttr('type');
                }
            })
            .on('keydown', function(e) {
                if (9 === e.keyCode && undefined !== $(this).data('ui-autocomplete')?.menu?.element[0]?.childNodes[0]) {
                    $(this).data('ui-autocomplete').menu.element[0].childNodes[0].click();
                }
                if (!$.trim($(e.target).val()) && (e.keyCode === 0 || e.keyCode === 32)) {
                    e.preventDefault();
                }
            })
            .autocomplete({
                delay: 1,
                minLength: 2,
                source: function(request, response) {
                    if (request.term && request.term.length >= 2) {
                        $.get(Routing.generate('aw_flight_search_place', { query: request.term }), function(data) {
                            $(element.current).data('data', data).removeClass('loading-input');
                            response(data.map(function(item) {
                                return {
                                    id: item.id,
                                    type: item.type,
                                    value: item.value,
                                    label: item.name,
                                    info: item.info,
                                    code: item.code,
                                };
                            }));
                        });
                    }
                },
                search: function(event, ui) {
                    props.getValues.value.length >= 2
                        ? element.current.classList.add('loading-input')
                        : element.current.classList.remove('loading-input');
                },
                open: function(event, ui) {
                    element.current.classList.remove('loading-input');
                },
                create: function() {
                    $(this).data('ui-autocomplete')._renderItem = function(ul, item) {
                        const regex = new RegExp('(' + this.element.val() + ')', 'gi');
                        let code = item.code.replace(regex, '<b>$1</b>');
                        let label = item.label.replace(regex, '<b>$1</b>');
                        const info = '' === item.info ? '<b>&nbsp;</b>' : item.info.replace(regex, '<b>$1</b>');

                        switch (item.type) {
                            case 2:
                                label += ` (${Translator.trans('city')})`;
                                break;
                            case 3:
                                label += ` (${Translator.trans('cart.state')})`;
                                break;
                            case 4:
                                label += ` (${Translator.trans('cart.country')})`;
                                break;
                            case 5:
                                label += ` (${Translator.trans('region')})`;
                                break;
                        }

                        const transpBg = -1 !== code.indexOf('icon-') ? ' icon-block' : '';
                        const html = `<span class="silver${transpBg}">${code}</span><i>${label}</i><span>${info}</span>`;

                        return $('<li></li>')
                            .data('item.autocomplete', item)
                            .append($(`<a class="address-location address-location-type-${item.type}"></a>`).html(html))
                            .appendTo(ul);
                    };
                },
                select: function(event, ui) {
                    props.setValues({
                        type: ui.item.type,
                        value: ui.item.value,
                        query: ui.item.type + '-' + ui.item.id,
                    });
                    $(element.current).data('value', ui.item.value).parent().attr('type', ui.item.type);
                }
            });
    }, []);

    return (
        <input type="text" placeholder={props.placeholder} required="required" ref={element}
               value={props.getValues.value} data-value={props.getValues?.value || ''}
               onChange={useMemo(() => (e) => props.setValues({ ...props.getValues, ...{ value: e.target.value } }))}/>
    )
}

function ListSubMenu(props) {
    return (
        <ul className="dropdown-submenu " data-role="dropdown" data-id={props.id} role="menu">
            {Object.entries(props.items).map((value, index, arr) =>
                <li className="ui-menu-item" role="presentation" key={value[0]}>
                    <a href="" onClick={(event) => {
                        event.preventDefault();
                        props.setValue({ id: value[0], name: value[1] });
                    }}><span>{value[1]}</span></a>
                </li>
            )}
        </ul>
    )
}

export default Form;