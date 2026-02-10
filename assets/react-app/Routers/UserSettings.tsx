import { ErrorBoundary } from '@Root/Routers/Components/ErrorBoundary';
import { Router } from '@Services/Router';
import { createBrowserRouter } from 'react-router-dom';
import React, { lazy } from 'react';

const DeleteUserAccountPage = lazy(() => import('@Root/Pages/UserSettings/DeleteAccount'));
const GmailForwardingPage = lazy(() => import('@Root/Pages/UserSettings/GmailForwarding'));
const PrePaymentPage = lazy(() => import('@Root/Pages/UserSettings/PrePayment'));
const CancelSubscriptionPage = lazy(() => import('@Root/Pages/UserSettings/CancelSubscription'));
const CancelAppleSubscriptionPage = lazy(() => import('@Root/Pages/UserSettings/AppleSubscriptionCancellation'));

export const userSettingsRouter = createBrowserRouter([
    {
        path: Router.generate('aw_user_delete'),
        element: <DeleteUserAccountPage />,
        errorElement: <ErrorBoundary />,
    },
    {
        path: `:lang?${Router.generate('aw_pre_payment')}`,
        element: <PrePaymentPage />,
        errorElement: <ErrorBoundary />,
    },
    {
        path: Router.generate('aw_gmail_forwarding'),
        element: <GmailForwardingPage />,
        errorElement: <ErrorBoundary />,
    },
    {
        path: Router.generate('aw_user_subscription_get_cancel'),
        element: <CancelSubscriptionPage />,
        errorElement: <ErrorBoundary />,
    },
    {
        path: Router.generate('aw_user_applesubscription_cancel'),
        element: <CancelAppleSubscriptionPage />,
        errorElement: <ErrorBoundary />,
    },
]);
