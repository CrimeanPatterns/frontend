import { bodyScrollManager } from '@Bem/ts/service/body-scroll-manager';
import './bg-layout.scss';

type ShowBgOptions = {
    variant?: 'light' | 'medium' | 'dark';
    blur?: boolean;
    onClick?: (e: MouseEvent) => void;
};

export class BackgroundLayoutController {
    private layoutElement: HTMLElement | null = null;
    private isVisible: boolean = false;
    private currentClickHandler: ((e: MouseEvent) => void) | null = null;

    constructor(private selector: string = '.bg-layout') {
        this.init();
    }

    private init(): void {
        this.layoutElement = document.querySelector(this.selector);
    }

    private removeCurrentClickHandler(): void {
        if (!this.layoutElement || !this.currentClickHandler) return;

        this.layoutElement.removeEventListener('click', this.currentClickHandler);
        this.currentClickHandler = null;
    }

    public show(options: ShowBgOptions = { variant: 'medium' }): void {
        if (!this.layoutElement) {
            console.error('Bg-layout element not found');
            return;
        }

        this.layoutElement.className = 'bg-layout';
        this.layoutElement.classList.add('bg-layout--visible');

        if (options.variant) {
            this.layoutElement.classList.add(`bg-layout--${options.variant}`);
        }

        if (options.blur) {
            this.layoutElement.classList.add('bg-layout--blur');
        }

        this.removeCurrentClickHandler();

        if (options.onClick) {
            this.currentClickHandler = options.onClick;
            this.layoutElement.addEventListener('click', options.onClick);
        }

        this.isVisible = true;

        bodyScrollManager.lockScroll();
    }

    public hide(): void {
        if (!this.layoutElement) return;

        this.removeCurrentClickHandler();

        this.layoutElement.classList.remove('bg-layout--visible');

        this.isVisible = false;

        bodyScrollManager.unlockScroll();
    }

    public toggle(options: ShowBgOptions = {}): void {
        if (this.isVisible) {
            this.hide();
        } else {
            this.show(options);
        }
    }

    public isActive(): boolean {
        return this.isVisible;
    }

    public destroy(): void {
        this.removeCurrentClickHandler();

        if (this.isVisible) {
            bodyScrollManager.unlockScroll();
            this.isVisible = false;
        }
    }
}
