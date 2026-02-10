import _ from 'lodash';
import axios from 'axios';
import retry, { isNetworkOrIdempotentRequestError } from './retry';

const X_XSRF_TOKEN = 'x-xsrf-token';
const X_XSRF_FAILED = 'x-xsrf-failed';
const instance = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
});
let xsrfToken = null;

function getXsrfToken() {
    if (xsrfToken === null) {
        xsrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
    }

    return xsrfToken;
}

function saveXsrfToken(response) {
    if (response && response.headers && response.headers[X_XSRF_TOKEN]) {
        console.log('saving xsrf token', response.headers[X_XSRF_TOKEN]);
        xsrfToken = response.headers[X_XSRF_TOKEN];
    }
}

function isXsrfFailed(response) {
    return _.get(response, `headers[${X_XSRF_FAILED}]`) === 'true';
}

function checkXsrfFailed(response) {
    if (isXsrfFailed(response)) {
        console.log('xsrf failed');

        return false;
    }

    return true;
}

retry(instance, {
    retries: 3,
    retryCondition: (error) => {
        if (isNetworkOrIdempotentRequestError(error)) {
            console.log('network or idempotent request error');

            return true;
        }

        return !checkXsrfFailed(_.has(error, 'response') ? error.response : error);
    },
});

instance.interceptors.request.use((config) => {
    const headers = {};
    const xsrfToken = getXsrfToken();

    if (xsrfToken) {
        headers[X_XSRF_TOKEN.toUpperCase()] = xsrfToken;
    }

    config.headers = { ...config.headers, ...headers };

    return config;
});
instance.interceptors.response.use(
    (response) => {
        saveXsrfToken(response);

        return response;
    },
    (rejection) => {
        const { response } = rejection;

        saveXsrfToken(response);

        // redirect to login
        if (
            _.get(rejection, 'response.data') === 'unauthorized' ||
            _.get(rejection, 'response.headers["ajax-error"]') === 'unauthorized'
        ) {
            try {
                if (window.parent !== window) {
                    parent.location.href = '/security/unauthorized.php?BackTo=' + encodeURI(parent.location.href);
                    return Promise.resolve();
                }
            } catch (e) {
                // eslint-disable-next-line
            }
            location.href = '/security/unauthorized.php?BackTo=' + encodeURI(location.href);
            return Promise.resolve();
        }

        if (
            !_.has(rejection, `response.headers['x-aw-reauth-required']`) &&
            !_.has(rejection, `response.headers['x-aw-reauth-error']`)
        ) {
            import('../errorDialog').then(({ default: showErrorDialog }) => {
                showErrorDialog(rejection, _.get(rejection, 'config.disableErrorDialog', false));
            });
        }

        return Promise.reject(rejection);
    },
);

export default instance;
