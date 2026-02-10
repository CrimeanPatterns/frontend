import insightsClient from 'search-insights';

interface SearchApiConfig {
    apiKey: string;
    appId: string;
    indexName: string;
}

interface WindowWithSearchApi extends Window {
    apiSearch?: SearchApiConfig;
    userRef?: string;
}

export class InsightsService {
    private static instance: InsightsService | null = null;
    private initialized = false;

    private constructor() {}

    static getInstance(): InsightsService {
        if (!InsightsService.instance) {
            InsightsService.instance = new InsightsService();
        }
        return InsightsService.instance;
    }

    initialize(): boolean {
        if (this.initialized) {
            return true;
        }

        const windowWithApi = window as WindowWithSearchApi;

        if (!windowWithApi.apiSearch?.apiKey || !windowWithApi.apiSearch?.appId) {
            return false;
        }

        try {
            insightsClient('init', {
                appId: windowWithApi.apiSearch.appId,
                apiKey: windowWithApi.apiSearch.apiKey,
                useCookie: windowWithApi.userRef === undefined,
            });

            if (windowWithApi.userRef) {
                insightsClient('setUserToken', windowWithApi.userRef);
            }

            this.initialized = true;
            return true;
        } catch (error) {
            return false;
        }
    }

    getClient() {
        if (!this.initialized) {
            console.warn('InsightsService: Client not initialized. Call initialize() first.');
            return null;
        }
        return insightsClient;
    }

    isInitialized(): boolean {
        return this.initialized;
    }

    setUserToken(userToken: string): void {
        if (!this.initialized) {
            return;
        }

        insightsClient('setUserToken', userToken);
    }
}
