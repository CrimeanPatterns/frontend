import { bem } from '@Bem/ts/service/bem';
import { PostAuthorOrReviewer } from '../types/post';

export class AuthorsController {
    private authorLinksWithListeners: HTMLAnchorElement[] = [];
    private resizeTimeout: number | null = null;
    private observedElements: Element[] = [];

    private boundClickHandler = this.authorsClickHandler.bind(this);
    private boundResizeHandler = this.handleWindowResize.bind(this);

    private authorPopupSelector = bem('post', 'author-names-popup');

    public initAuthorsPopover() {
        this.removeAllListeners();
        const authorsWrapperElements: NodeListOf<Element> = document.querySelectorAll('.post__author-names');

        window.addEventListener('resize', this.boundResizeHandler);

        authorsWrapperElements.forEach(this.processAuthorWrapper.bind(this));
    }

    private handleWindowResize() {
        if (this.resizeTimeout) {
            window.clearTimeout(this.resizeTimeout);
        }

        this.resizeTimeout = window.setTimeout(() => {
            this.recalculateAllWrappers();
        }, 250);
    }

    private recalculateAllWrappers() {
        this.observedElements.forEach((element) => {
            this.recalculateWrapper(element);
        });
    }

    private recalculateWrapper(element: Element) {
        const existingLinks = element.querySelectorAll('a');
        Array.from(existingLinks).forEach((link) => {
            const index = this.authorLinksWithListeners.indexOf(link as HTMLAnchorElement);
            if (index !== -1) {
                link.removeEventListener('click', this.boundClickHandler);
                this.authorLinksWithListeners.splice(index, 1);
            }
        });

        const existingPopup = element.querySelector(`.${this.authorPopupSelector}`);
        if (existingPopup) {
            existingPopup.remove();
        }

        const authorLinks = element.querySelectorAll('a');
        const authorData = this.getLinkAuthorData(element);

        if (!authorData) {
            return;
        }

        let authorParsedData: PostAuthorOrReviewer[];
        try {
            authorParsedData = JSON.parse(authorData) as PostAuthorOrReviewer[];
        } catch (error) {
            return;
        }

        const linksWidth = this.calculateLinksWidth(authorLinks);
        const wrapperWidth = element.getBoundingClientRect().width;

        if (linksWidth > wrapperWidth) {
            this.setupPopover(element, authorParsedData, authorLinks);
        }
    }

    private processAuthorWrapper(authorWrapperElement: Element): void {
        const authorLinks = authorWrapperElement.querySelectorAll('a');
        const authorData = this.getLinkAuthorData(authorWrapperElement);

        if (!authorData) {
            console.error('Author data not found for element', authorWrapperElement);
            return;
        }

        let authorParsedData: PostAuthorOrReviewer[];
        try {
            authorParsedData = JSON.parse(authorData) as PostAuthorOrReviewer[];
        } catch (error) {
            console.error('Failed to parse author data:', error, authorData);

            return;
        }

        this.observedElements.push(authorWrapperElement);

        const linksWidth = this.calculateLinksWidth(authorLinks);
        const wrapperWidth = authorWrapperElement.getBoundingClientRect().width;

        if (linksWidth > wrapperWidth) {
            this.setupPopover(authorWrapperElement, authorParsedData, authorLinks);
        }
    }

    private setupPopover(
        wrapperElement: Element,
        authors: PostAuthorOrReviewer[],
        links: NodeListOf<HTMLAnchorElement>,
    ): void {
        const fragment = document.createDocumentFragment();
        const popover = this.createPopup();

        const authorPopupLinks = this.createPopupLinks(authors);
        authorPopupLinks.forEach((link) => fragment.appendChild(link));

        popover.appendChild(fragment);

        wrapperElement.prepend(popover);

        this.attachEventListeners(links);
    }

    private createPopupLinks(authors: PostAuthorOrReviewer[]): HTMLAnchorElement[] {
        return authors
            .map((author) => this.createPopupAuthorLink(author))
            .sort((a, b) => (a.textContent?.length || 0) - (b.textContent?.length || 0));
    }

    private calculateLinksWidth(authorLinks: NodeListOf<HTMLAnchorElement>) {
        return Array.from(authorLinks).reduce((totalWidth, linkElement) => {
            return totalWidth + linkElement.getBoundingClientRect().width;
        }, 0);
    }

    private createPopupAuthorLink(author: PostAuthorOrReviewer) {
        const authorLink = document.createElement('a');
        authorLink.setAttribute('href', author.link);
        authorLink.textContent = author.name;
        authorLink.className = bem('post', 'author-names-popup-link');

        return authorLink;
    }

    private createPopup() {
        const popover = document.createElement('div');
        popover.className = this.authorPopupSelector;

        return popover;
    }

    private attachEventListeners(links: NodeListOf<HTMLAnchorElement>): void {
        Array.from(links).forEach((link) => {
            link.addEventListener('click', this.boundClickHandler);
            this.authorLinksWithListeners.push(link);
        });
    }

    private authorsClickHandler(event: Event) {
        event.preventDefault();
        event.stopPropagation();

        const popover = (event.currentTarget as HTMLElement)
            .closest(`.${bem('post', 'author-names')}`)
            ?.querySelector(`.${this.authorPopupSelector}`);

        const documentClickHandler = () => {
            if (popover) {
                popover.classList.remove('post__author-names-popup--visible');
                document.removeEventListener('click', documentClickHandler);
            }
        };

        document.addEventListener('click', documentClickHandler);

        if (popover) {
            popover.classList.toggle('post__author-names-popup--visible');
        }
    }

    private getLinkAuthorData(authorWrapperElement: Element) {
        return authorWrapperElement.getAttribute('data-authors');
    }

    private removeAllListeners() {
        if (this.authorLinksWithListeners.length > 0) {
            this.authorLinksWithListeners.forEach((link) => {
                link.removeEventListener('click', this.boundClickHandler);
            });
            this.authorLinksWithListeners = [];
        }

        window.removeEventListener('resize', this.boundResizeHandler);

        if (this.resizeTimeout) {
            window.clearTimeout(this.resizeTimeout);
            this.resizeTimeout = null;
        }
    }
}
