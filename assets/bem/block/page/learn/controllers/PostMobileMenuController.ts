import { bem } from '@Bem/ts/service/bem';
import { showToast } from '@Bem/ts/service/toast/toast';
import { favoriteService } from '../services/FavoriteService';
import { learnedService } from '../services/LearnedService';
import { copyToClipboard } from '@Bem/ts/service/utils';

export class PostMobileMenuController {
    private eventListeners: Map<HTMLElement, { event: string; handler: EventListener }[]> = new Map();
    private activeMenus: Set<HTMLUListElement> = new Set();
    private documentClickHandler: (e: MouseEvent) => void;

    constructor(private customLearnCallback?: (e: MouseEvent, learnButton?: HTMLButtonElement) => void) {
        this.documentClickHandler = this.handleDocumentClick.bind(this);
        document.addEventListener('click', this.documentClickHandler);
    }

    private handleDocumentClick(e: MouseEvent): void {
        const target = e.target as HTMLElement;

        if (this.activeMenus.size === 0) {
            return;
        }

        const clickedOnMenuOrButton = this.isClickInsideActiveMenuOrButton(target);

        if (!clickedOnMenuOrButton) {
            this.closeAllPostMobileMenuPopup();
        }
    }

    private isClickInsideActiveMenuOrButton(target: HTMLElement): boolean {
        const clickedMenuButton = target.closest<HTMLButtonElement>(`.${bem('post', 'mobile-menu-button')}`);
        if (clickedMenuButton) {
            return true;
        }

        for (const menu of this.activeMenus) {
            if (menu.contains(target)) {
                return true;
            }
        }

        return false;
    }
    private addListener(element: HTMLElement, event: string, handler: EventListener): void {
        element.addEventListener(event, handler);

        if (!this.eventListeners.has(element)) {
            this.eventListeners.set(element, []);
        }

        this.eventListeners.get(element)?.push({ event, handler });
    }

    private removeAllListeners(element: HTMLElement): void {
        const listeners = this.eventListeners.get(element);
        if (listeners) {
            listeners.forEach(({ event, handler }) => {
                element.removeEventListener(event, handler);
            });
            this.eventListeners.delete(element);
        }
    }

    private cleanupAllListeners(): void {
        this.eventListeners.forEach((listeners, element) => {
            listeners.forEach(({ event, handler }) => {
                element.removeEventListener(event, handler);
            });
        });
        this.eventListeners.clear();
    }

    public handleMobileMenuButtonClick(e: Event): void {
        const target = e.target as HTMLElement;
        const mobileMenuContainer = target.closest<HTMLDivElement>(`.${bem('post', 'mobile-menu')}`);

        if (!mobileMenuContainer) {
            return;
        }

        const mobileMenuButton = mobileMenuContainer?.querySelector<HTMLButtonElement>(
            `.${bem('post', 'mobile-menu-button')}`,
        );
        const mobileMenuPopup = mobileMenuContainer?.querySelector<HTMLUListElement>(
            `.${bem('post', 'mobile-menu-list')}`,
        );

        if (!mobileMenuButton || !mobileMenuPopup) {
            return;
        }

        if (this.isClickOnMenuButton(target, mobileMenuButton)) {
            this.setUpMenuItemsClickListeners(mobileMenuPopup);
            this.togglePopup(mobileMenuButton, mobileMenuPopup);
            e.stopPropagation();
            e.preventDefault();
        }
    }

    private setupShareButtonListener(mobileMenuPopup: HTMLUListElement): void {
        const shareButton = mobileMenuPopup.querySelector<HTMLElement>(
            `.${bem('post', 'mobile-menu-item-button--share')}`,
        );
        const postLink = mobileMenuPopup?.getAttribute('data-post-link');
        const postTitle = shareButton?.getAttribute('data-post-title');

        if (shareButton) {
            if (!this.isNavigationShareSupported()) {
                const menuItem = shareButton.closest('li');
                if (menuItem) {
                    menuItem.style.display = 'none';
                }
            } else if (postLink && postTitle) {
                const handler = (e: Event) => {
                    e.preventDefault();
                    e.stopPropagation();

                    this.shareContent(postLink, postTitle);
                    this.closeAllPostMobileMenuPopup();
                };

                this.addListener(shareButton, 'click', handler);
            }
        }
    }

    private setupFavoriteButtonListener(mobileMenuPopup: HTMLUListElement): void {
        const favoriteButton = mobileMenuPopup.querySelector<HTMLButtonElement>(
            `.${bem('post', 'mobile-menu-item-button--favorite')}`,
        );
        const postId = mobileMenuPopup?.getAttribute('data-post-id');
        if (favoriteButton && postId) {
            const handler = (e: Event) => {
                e.preventDefault();
                e.stopPropagation();

                if (!favoriteService.checkUserAuth()) {
                    return;
                }

                const isFavorite = favoriteButton.classList.contains('post__mobile-menu-item-button--active');

                favoriteService.toggleFavorite(favoriteButton, postId, isFavorite);

                this.closeAllPostMobileMenuPopup();
            };
            this.addListener(favoriteButton, 'click', handler);
        }
    }

    private setupLearnedButtonListener(mobileMenuPopup: HTMLUListElement): void {
        const learnedButton = mobileMenuPopup.querySelector<HTMLButtonElement>(
            `.${bem('post', 'mobile-menu-item-button--learned')}`,
        );
        const postId = mobileMenuPopup?.getAttribute('data-post-id');

        if (learnedButton && postId) {
            const handler = (e: Event) => {
                const mouseEvent = e as MouseEvent;
                mouseEvent.preventDefault();
                mouseEvent.stopPropagation();
                if (!learnedService.checkUserAuth()) {
                    return;
                }

                const isLearned = learnedButton.classList.contains('post__mobile-menu-item-button--active');

                if (this.customLearnCallback) {
                    this.customLearnCallback(mouseEvent, learnedButton);
                } else {
                    learnedService.toggleLearned(learnedButton, postId, isLearned).finally(() => {
                        this.closeAllPostMobileMenuPopup();
                    });
                }
            };

            this.addListener(learnedButton, 'click', handler);
        }
    }

    private setupCopyLinkButtonListener(mobileMenuPopup: HTMLUListElement): void {
        const copyLinkButton = mobileMenuPopup.querySelector<HTMLElement>(
            `.${bem('post', 'mobile-menu-item-button--copy-link')}`,
        );
        const postLink = mobileMenuPopup?.getAttribute('data-post-link');

        if (copyLinkButton && postLink) {
            const handler = async (e: Event) => {
                e.preventDefault();
                e.stopPropagation();
                const isSuccessfulCopied = await copyToClipboard(postLink);

                if (isSuccessfulCopied) {
                    showToast({
                        message: 'Copied',
                        type: 'info',
                    });
                } else {
                    showToast({
                        message: 'Error copying the link',
                        type: 'error',
                    });
                }
                this.closeAllPostMobileMenuPopup();
            };

            this.addListener(copyLinkButton, 'click', handler);
        }
    }

    private isClickOnMenuButton(target: HTMLElement, menuButton: HTMLButtonElement): boolean {
        return target === menuButton || menuButton.contains(target);
    }

    private togglePopup(mobileMenuButton: HTMLButtonElement, mobileMenuPopup: HTMLUListElement): void {
        const isActive = mobileMenuButton.classList.contains('post__mobile-menu-button--active');

        if (isActive) {
            mobileMenuButton.classList.remove('post__mobile-menu-button--active');
            mobileMenuPopup.classList.remove('post__mobile-menu-list--visible');
            this.activeMenus.delete(mobileMenuPopup);
        } else {
            mobileMenuButton.classList.add('post__mobile-menu-button--active');
            mobileMenuPopup.classList.add('post__mobile-menu-list--visible');
            this.activeMenus.add(mobileMenuPopup);
        }
    }

    private setUpMenuItemsClickListeners(mobileMenuPopup: HTMLUListElement): void {
        this.setupShareButtonListener(mobileMenuPopup);
        this.setupFavoriteButtonListener(mobileMenuPopup);
        this.setupLearnedButtonListener(mobileMenuPopup);
        this.setupCopyLinkButtonListener(mobileMenuPopup);
    }

    private shareContent(postLink: string, postTitle: string) {
        if (navigator.share) {
            navigator
                .share({
                    title: postTitle,
                    url: postLink,
                })
                .catch((error) => {
                    if (error instanceof DOMException && error.name === 'AbortError') {
                        return;
                    }
                    showToast({ message: 'Unable to share content.', type: 'error' });
                });
        } else {
            showToast({ message: 'Unable to share content.', type: 'error' });
        }
    }

    private closeAllPostMobileMenuPopup(): void {
        const menuButtons = document.querySelectorAll<HTMLElement>(`.${bem('post', 'mobile-menu-button--active')}`);
        const menuPopups = document.querySelectorAll<HTMLElement>(`.${bem('post', 'mobile-menu-list--visible')}`);

        menuButtons.forEach((button) => {
            button.classList.remove('post__mobile-menu-button--active');
        });

        menuPopups.forEach((popup) => {
            popup.classList.remove('post__mobile-menu-list--visible');

            const buttons = popup.querySelectorAll<HTMLElement>('button');
            buttons.forEach((button) => this.removeAllListeners(button));
        });

        this.activeMenus.clear();
        this.cleanupAllListeners();
    }

    private isNavigationShareSupported(): boolean {
        return typeof navigator !== 'undefined' && !!navigator.share;
    }
}
