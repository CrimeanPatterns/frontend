import React, { useEffect } from 'react';
import Translator from '../../../bem/ts/service/translator';

import Form from './Form';
import GroupList from './GroupList';

function FlightSearch(props) {
    const isExpandRoutesExists = 0 !== Object.keys(props.data.expandRoutes).length;
    const groups = Object.values(props.data.primaryList);
    const skyLink = 'https://skyscanner.pxf.io/c/327835/1027991/13416?associateid=AFF_TRA_19354_00001';

    return (
        <div className={'main-blk flight-search'}>
            <div className={'flight-search-skyscanner'}>
                <a href={skyLink} target="_blank" rel="noreferrer"><img src="/assets/awardwalletnewdesign/img/logo/skycanner-stacked--blue.png" alt=""/></a>
            </div>
            <h1>{Translator.trans('award-flight-research-tool')}</h1>
            <div className="main-blk-content">
                <Form form={props.data.form}/>
                {isExpandRoutesExists ? <SearchResult expandRoutes={props.data.expandRoutes}/> : null}
                <GroupList groups={groups} isFormFilled={undefined !== props.data.form?.from} skyLink={skyLink} />
            </div>
        </div>
    );
}

const SearchResult = React.memo(function SearchResult(props) {
    const expandRoutes = props.expandRoutes;

    return (
        <div className="flight-search-expand">
            <div className="flight-search-form">
                <div className="flightsearch-form-place flightsearch-form-from">
                    {'' !== expandRoutes.linkFrom && undefined !== expandRoutes.linkFrom
                        ? <a className={'btn-blue'} href={expandRoutes.linkFrom}>
                            {Translator.trans('expand-to', { name: expandRoutes.from.dep.value })}
                            <s>{typePlaceSign(expandRoutes.from.dep.type)}</s>
                        </a>
                        : null
                    }
                </div>
                <div className="flightsearch-form-gap"></div>
                <div className="flightsearch-form-place flightseach-form-to">
                    {'' !== expandRoutes.linkTo && undefined !== expandRoutes.linkTo
                        ? <a className={'btn-blue'} href={expandRoutes.linkTo}>
                            {Translator.trans('expand-to', { name: expandRoutes.to.arr.value })}
                            <s>{typePlaceSign(expandRoutes.to.arr.type)}</s>
                        </a>
                        : null
                    }
                </div>
                <div className="flightsearch-form-menu flightsearch-form-trip"></div>
                <div className="flightsearch-form-menu flightsearch-form-class"></div>
                <div className="flightsearch-form-submit"></div>
            </div>
        </div>
    );
});


function typePlaceSign(type) {
    if (2 === type) {
        return ` (${Translator.trans('city')})`;
    } else if (3 === type) {
        return ` (${Translator.trans('cart.state')})`;
    } else if (4 === type) {
        return ` (${Translator.trans('cart.country')})`;
    }

    return ''
}

export default FlightSearch;