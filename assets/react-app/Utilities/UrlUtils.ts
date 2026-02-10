export function isPathSafe(url: string) {
    return /^\/[a-zA-Z0-9/_\-?&=#]*$/.test(url);
}

export function getUrlPathAndQuery(url: string): string {
    const extractUrlParts = (parsedUrl: URL): string => {
        const matchesPathPattern = /^\/?([\w-]+(\.\w+)?\/?)*/im.test(parsedUrl.pathname);
        let result = matchesPathPattern ? parsedUrl.pathname : '/';

        result += parsedUrl.search || '';
        result += parsedUrl.hash || '';

        return result;
    };

    try {
        return extractUrlParts(new URL(url));
    } catch {
        try {
            return extractUrlParts(new URL(url, 'http://example.com'));
        } catch {
            return '/';
        }
    }
}
