import { ErrorBoundary } from '@Root/Routers/Components/ErrorBoundary';
import { Router } from '@Services/Router';
import { createBrowserRouter } from 'react-router-dom';
import React, { lazy } from 'react';

const HotelPage = lazy(() => import('../Pages/Hotels'));
const PodcastPage = lazy(() => import('../Pages/Podcast'));
const ExtensionInstallPage = lazy(() => import('../Pages/ExtensionInstall'));

export const pageRouter = createBrowserRouter([
    {
        path: Router.generate('aw_hotels_index'),
        element: <HotelPage />,
        errorElement: <ErrorBoundary />,
    },
    {
        path: Router.generate('aw_podcast'),
        element: <PodcastPage />,
        errorElement: <ErrorBoundary />,
    },
    {
        path: Router.generate('aw_extension_install'),
        element: <ExtensionInstallPage />,
        errorElement: <ErrorBoundary />,
    },
]);
