export default function onReady(callback: () => void): void {
    if (document.readyState === 'loading') {
        // The DOM is not yet ready.
        document.addEventListener('DOMContentLoaded', callback);
    } else {
        // The DOM is already ready.
        callback();
    }
}