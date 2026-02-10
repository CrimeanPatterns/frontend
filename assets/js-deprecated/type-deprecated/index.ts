import {MutableRefObject, RefCallback} from 'react';

export type MutableRef<T> = RefCallback<T> | MutableRefObject<T> | null;
/*
declare global {
    interface JQuery {
        dialog(options: any): void;

        autocomplete(
            param: {
                search: (event: autocompleteEventSearchInterface) => void; delay: number;
                select: (event: autocompleteSelectEventInterface,
                         ui: autocompleteSelectUiInterface) => void;
                minLength: number;
                create: () => void;
                source: () => { id: number; value: string };
                open: (event: any) => void
            }
        ): void;
    }
}


interface autocompleteEventSearchInterface {
    target: any,
}

interface autocompleteSelectEventInterface {
    preventDefault: () => void,
    target: any,
}

interface autocompleteSelectUiInterface {
    item: { value: string | number | string[] | ((this: any, index: number, value: string) => string); };
}
*/
