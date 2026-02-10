declare module 'lib/dialog' {
    interface Dialog {
        isOpen: boolean;
        moveToTop: unknown;
        open: unknown;
        close: () => void;
        destroy: unknown;
        getOption: (option: string) => unknown;
        setOption: (option: string, value: unknown) => void;
    }

    interface DialogButton {
        text?: string;
        icon?: string;
        class?: string;
        click?: () => void;
    }

    interface DialogOptions {
        show?: boolean | number | string;
        autoOpen?: boolean;
        modal?: boolean;
        minWidth?: number;
        buttons?: Array<DialogButton>;
    }
    export const createNamed: (name: string, elem: JQuery<Element>, options: DialogOptions) => Dialog;
}
