import { RouterProvider } from 'react-router-dom';
import { pageRouter } from './Routers/Page';
import React from 'react';
import ReactDOM from 'react-dom/client';

ReactDOM.createRoot(document.getElementById('content') as HTMLElement).render(
    <React.StrictMode>
        <RouterProvider router={pageRouter} />
    </React.StrictMode>,
);
