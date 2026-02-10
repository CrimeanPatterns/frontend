import { render } from 'react-dom';
import React from 'react';

import './FlightSearch.less';
import FlightSearch from './FlightSearch';

(async () => {
    await import('../../../bem/ts/starter');

    const root = document.getElementById('content');
    const data = JSON.parse(document.getElementById('data').textContent);

    render(
        <React.StrictMode>
            <FlightSearch data={data}/>
        </React.StrictMode>,
        root
    );

})();