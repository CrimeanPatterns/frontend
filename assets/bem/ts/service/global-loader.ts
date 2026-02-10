export function hideGlobalLoader() {
    const loader = document.getElementById('global-loader');

    if (loader) {
        loader.addEventListener('transitionend', function handleTransitionEnd() {
            if (loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }
            loader.removeEventListener('transitionend', handleTransitionEnd);
        });

        loader.classList.add('global-loader--loaded');
    }
}
