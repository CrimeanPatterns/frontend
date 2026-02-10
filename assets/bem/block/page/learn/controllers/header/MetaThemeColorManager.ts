export class MetaThemeColorManager {
    private darkThemeMetaTag: HTMLMetaElement | null;
    private lightThemeMetaTag: HTMLMetaElement | null;
    private standardThemeMetaTag: HTMLMetaElement | null;

    constructor() {
        this.darkThemeMetaTag = document.querySelector(
            'meta[name="theme-color"][media="(prefers-color-scheme: dark)"]',
        );
        this.lightThemeMetaTag = document.querySelector(
            'meta[name="theme-color"][media="(prefers-color-scheme: light)"]',
        );

        this.standardThemeMetaTag = document.querySelector('meta[name="theme-color"]:not([media])');
    }

    public setMetaColor(color: string): void {
        if (this.darkThemeMetaTag) {
            this.darkThemeMetaTag.setAttribute('content', color);
        }

        if (this.lightThemeMetaTag) {
            this.lightThemeMetaTag.setAttribute('content', color);
        }

        if (this.standardThemeMetaTag) {
            this.standardThemeMetaTag.setAttribute('content', color);
        }
    }
}
