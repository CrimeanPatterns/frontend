import { RouterProvider } from 'react-router-dom';
import { userSettingsRouter } from './Routers/UserSettings';
import React, { Suspense } from 'react';
import ReactDOM from 'react-dom/client';

const reactRoot = ReactDOM.createRoot(document.getElementById('content') as HTMLElement);

reactRoot.render(
    <React.StrictMode>
        <Suspense>
            <RouterProvider router={userSettingsRouter} />
        </Suspense>
    </React.StrictMode>,
);
