let patched = false;

function patchHistoryMethod(methodName: 'pushState' | 'replaceState') {
    window.history[methodName] = new Proxy(window.history[methodName], {
        apply: (target, thisArg, argArray) => {
            // @ts-ignore
            target.apply(thisArg, argArray);

            const e = new Event(methodName);
            // @ts-ignore
            e.data = argArray;
            window.dispatchEvent(e);
        },
    });
}

export const patchHistoryMethods = () => {
    if (!patched) {
        patchHistoryMethod('pushState')
        patchHistoryMethod('replaceState')
        patched = true;
    }
}
