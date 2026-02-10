class BodyScrollManager {
    private static instance: BodyScrollManager;
    private lockCount: number = 0;
    private originalStyles: {
        overflow?: string;
        paddingRight?: string;
    } = {};
    private scrollbarWidth: number = 0;

    private constructor() {
        this.calculateScrollbarWidth();
    }

    public static getInstance(): BodyScrollManager {
        if (!BodyScrollManager.instance) {
            BodyScrollManager.instance = new BodyScrollManager();
        }
        return BodyScrollManager.instance;
    }

    private calculateScrollbarWidth(): void {
        const scrollDiv = document.createElement('div');
        scrollDiv.style.cssText = 'width: 100px; height: 100px; overflow: scroll; position: absolute; top: -9999px;';
        document.body.appendChild(scrollDiv);

        this.scrollbarWidth = scrollDiv.offsetWidth - scrollDiv.clientWidth;

        document.body.removeChild(scrollDiv);
    }

    public lockScroll(): void {
        this.lockCount++;

        if (this.lockCount === 1) {
            this.originalStyles = {
                overflow: document.body.style.overflow,
                paddingRight: document.body.style.paddingRight,
            };

            const isScrollable = document.documentElement.scrollHeight > document.documentElement.clientHeight;

            document.body.style.overflow = 'hidden';

            if (isScrollable) {
                document.body.style.paddingRight = `${this.scrollbarWidth}px`;
            }
        }
    }

    public unlockScroll(): void {
        if (this.lockCount > 0) {
            this.lockCount--;
        }

        if (this.lockCount === 0) {
            document.body.style.overflow = this.originalStyles.overflow || '';
            document.body.style.paddingRight = this.originalStyles.paddingRight || '';
        }
    }

    public isLocked(): boolean {
        return this.lockCount > 0;
    }
}

export const bodyScrollManager = BodyScrollManager.getInstance();
