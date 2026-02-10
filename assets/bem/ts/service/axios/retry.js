import isRetryAllowed from 'is-retry-allowed';

const namespace = 'retry';

const SAFE_HTTP_METHODS = ['get', 'head', 'options'];
const IDEMPOTENT_HTTP_METHODS = SAFE_HTTP_METHODS.concat(['put', 'delete']);

export function isNetworkError(error) {
    return (
        !error.response &&
        Boolean(error.code) && // Prevents retrying cancelled requests
        error.code !== 'ECONNABORTED' && // Prevents retrying timed out requests
        isRetryAllowed(error)
    ); // Prevents retrying unsafe errors
}

export function isRetryableError(error) {
    return (
        error.code !== 'ECONNABORTED' &&
        (!error.response || (error.response.status >= 500 && error.response.status <= 599))
    );
}

export function isSafeRequestError(error) {
    if (!error.config) {
        // Cannot determine if the request can be retried
        return false;
    }

    return isRetryableError(error) && SAFE_HTTP_METHODS.indexOf(error.config.method) !== -1;
}

export function isIdempotentRequestError(error) {
    if (!error.config) {
        // Cannot determine if the request can be retried
        return false;
    }

    return isRetryableError(error) && IDEMPOTENT_HTTP_METHODS.indexOf(error.config.method) !== -1;
}

export function isNetworkOrIdempotentRequestError(error) {
    return isNetworkError(error) || isIdempotentRequestError(error);
}

export function exponentialDelay(retryNumber = 0) {
    const delay = Math.pow(2, retryNumber) * 100;
    const randomSum = delay * 0.2 * Math.random(); // 0-20% of the delay
    return delay + randomSum;
}

function getCurrentState(config) {
    const currentState = config[namespace] || {};
    currentState.retryCount = currentState.retryCount || 0;
    config[namespace] = currentState;
    return currentState;
}

function getRequestOptions(config, defaultOptions) {
    return { ...defaultOptions, ...config[namespace] };
}

function noDelay() {
    return 0;
}

export default function axiosRetry(axios, defaultOptions) {
    axios.interceptors.request.use((config) => {
        const currentState = getCurrentState(config);
        currentState.lastRequestTime = Date.now();
        return config;
    });

    axios.interceptors.response.use(null, (error) => {
        const config = error.config;

        // If we have no information to retry the request
        if (!config) {
            return Promise.reject(error);
        }

        const {
            retries = 3,
            retryCondition = isNetworkOrIdempotentRequestError,
            retryDelay = noDelay,
            shouldResetTimeout = false,
        } = getRequestOptions(config, defaultOptions);

        const currentState = getCurrentState(config);

        const shouldRetry = retryCondition(error) && currentState.retryCount < retries;

        if (shouldRetry) {
            currentState.retryCount += 1;
            const delay = retryDelay(currentState.retryCount, error);

            if (!shouldResetTimeout && config.timeout && currentState.lastRequestTime) {
                const lastRequestDuration = Date.now() - currentState.lastRequestTime;
                // Minimum 1ms timeout (passing 0 or less to XHR means no timeout)
                config.timeout = Math.max(config.timeout - lastRequestDuration - delay, 1);
            }

            config.transformRequest = [(data) => data];

            const delayRetryRequest = new Promise((resolve) => {
                setTimeout(resolve, config.retryDelay || 1000);
            });

            return delayRetryRequest.then(() => axios(config));
        }

        return Promise.reject(error);
    });
}
