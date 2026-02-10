import { ErrorPane } from '@UI/Feedback/ErrorPane';
import { IS_DEV } from '@Utilities/Constants';
import { axios } from '@Services/Axios';
import { useRouteError } from 'react-router-dom';
import React, { useEffect } from 'react';

interface ReactRouterError extends Error {
    componentStack?: string;
}

export function ErrorBoundary() {
    const error = useRouteError() as ReactRouterError;

    useEffect(() => {
        if (!IS_DEV) {
            const componentStack = error.componentStack || '';

            let file = '';
            let line = '';
            let column = '';

            if (error.stack) {
                const stackLines = error.stack.split('\n');
                if (stackLines.length > 1) {
                    // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
                    const match = stackLines[1]!.match(/at\s+(.+):(\d+):(\d+)/);
                    if (match) {
                        file = match[1] || '';
                        line = match[2] || '';
                        column = match[3] || '';
                    }
                }
            }
            const errorInfo: ErrorInfoApi = {
                error: error.message,
                file: file,
                line: line,
                column: column,
                stack: error.stack ?? 'No stack available',
                componentStack: componentStack,
                type: error.name || 'Error',
                source: 'React',
            };
            sendErrorRequest(errorInfo).catch(() => {});
        }
    }, []);

    return <ErrorPane />;
}

interface ErrorInfoApi {
    error: string;
    file: string;
    line: string;
    column: string;
    stack: string;
    componentStack?: string;
    type?: string;
    source?: string;
}

async function sendErrorRequest(errorInfo: ErrorInfoApi) {
    await axios.post('/js_error', errorInfo);
}
