import React from 'react';
import {render} from 'react-dom';

import './transaction.less';

(async () => {
    await import('../../bem/ts/starter');

    const root = document.getElementById('content');

    render(
        <React.StrictMode/>,
        root
    );

})();
