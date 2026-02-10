import RouterService from '@Bem/ts/service/router';
import { addPostToFavorite, removePostFromFavorite } from '../services/api';
import { showErrorToast } from '@Bem/ts/service/toast/toast';

export class FavoriteService {
    private checkedIcon =
        '<path d="M5 21V5C5 4.45 5.19583 3.97917 5.5875 3.5875C5.97917 3.19583 6.45 3 7 3H17C17.55 3 18.0208 3.19583 18.4125 3.5875C18.8042 3.97917 19 4.45 19 5V21L12 18L5 21Z" fill="currentColor" />';
    private uncheckedIcon =
        '<path d="M5 21V5C5 4.45 5.19583 3.97917 5.5875 3.5875C5.97917 3.19583 6.45 3 7 3H17C17.55 3 18.0208 3.19583 18.4125 3.5875C18.8042 3.97917 19 4.45 19 5V21L12 18L5 21ZM7 17.95L12 15.8L17 17.95V5H7V17.95Z" fill="currentColor" />';

    public async toggleFavorite(button: Element, postId: string, isFavorite: boolean): Promise<boolean> {
        const isInFavoritePage = window.location.pathname === RouterService.generate('aw_blog_learn_favorite');
        let postElement: HTMLElement | null = null;
        let originalPostState: {
            isFavorite: boolean;
            postElement: HTMLElement | null;
        } = {
            isFavorite,
            postElement: null,
        };

        postElement = button.closest<HTMLElement>('.post');
        if (isInFavoritePage) {
            if (postElement) {
                originalPostState.postElement = postElement.cloneNode(true) as HTMLElement;
            }
        }

        this.updateFavoriteUI(!isFavorite, postElement!);

        if (isInFavoritePage && isFavorite && postElement) {
            postElement.classList.add('post--removed');
        }

        try {
            const isSuccess = await this.sendFavoriteRequest(postId, isFavorite);

            if (isSuccess) {
                if (isInFavoritePage && isFavorite && postElement) {
                    const animationDuration = this.getAnimationDuration(postElement);
                    await new Promise((resolve) => setTimeout(resolve, animationDuration));
                    postElement.remove();
                }
                return true;
            } else {
                this.revertChanges(button, postElement, originalPostState);
                return false;
            }
        } catch (error) {
            if (isFavorite) {
                showErrorToast('Failed to remove from favorites');
            } else {
                showErrorToast('Failed to add to favorites');
            }
            this.revertChanges(button, postElement, originalPostState);
            return false;
        }
    }

    private updateFavoriteUI(makeChecked: boolean, post: Element): void {
        const bookmarkButton = post.querySelector('.post__bookmark');
        const menuBookmarkButton = post.querySelector('.post__mobile-menu-item-button--favorite');

        if (bookmarkButton) {
            this.updateBookmarkUI(makeChecked, bookmarkButton);
        }
        if (menuBookmarkButton) {
            this.updateMenuItemUI(makeChecked, menuBookmarkButton);
        }
    }

    private updateBookmarkUI(makeChecked: boolean, button: Element): void {
        button.classList.toggle('post__bookmark--checked', makeChecked);

        const svgElement = button.querySelector('svg');

        if (svgElement) {
            if (makeChecked) {
                svgElement.innerHTML = this.checkedIcon;
            } else {
                svgElement.innerHTML = this.uncheckedIcon;
            }
        }

        const tooltip = button.querySelector('.post__action-tooltip');

        if (tooltip) {
            if (makeChecked) {
                tooltip.textContent = 'Remove from "Favorites"';
            } else {
                tooltip.textContent = 'Add to "Favorites"';
            }
        }
    }

    private updateMenuItemUI(makeChecked: boolean, button: Element): void {
        const buttonTextElement = button.querySelector('span');

        if (buttonTextElement) {
            if (makeChecked) {
                buttonTextElement.textContent = 'Remove from "Favorites"';
            } else {
                buttonTextElement.textContent = 'Add to "Favorites"';
            }
        }

        const iconElement = button.querySelector('svg');
        if (iconElement) {
            if (makeChecked) {
                iconElement.innerHTML = this.checkedIcon;
            } else {
                iconElement.innerHTML = this.uncheckedIcon;
            }
        }

        button.classList.toggle('post__mobile-menu-item-button--active', makeChecked);
    }

    private getAnimationDuration(element: HTMLElement): number {
        const computedStyle = window.getComputedStyle(element);
        const transitionDuration = parseFloat(computedStyle.transitionDuration) * 1000;
        return Math.max(transitionDuration, 300);
    }

    private revertChanges(
        button: Element | null,
        postElement: HTMLElement | null,
        originalState: { isFavorite: boolean; postElement: HTMLElement | null },
    ): void {
        if (button) {
            this.updateFavoriteUI(originalState.isFavorite, button);
        }

        if (
            window.location.pathname === RouterService.generate('aw_blog_learn_favorite') &&
            originalState.isFavorite &&
            postElement
        ) {
            postElement.classList.remove('post--removed');
        }
    }

    private updateIndicator(isFavorite: boolean) {
        const favoritePostIndicatorElement = document.querySelector('[data-favorite-post-count]');
        let favoritePostCount = Number(favoritePostIndicatorElement?.getAttribute('data-favorite-post-count'));

        if (!isNaN(favoritePostCount)) {
            if (isFavorite) {
                favoritePostCount = favoritePostCount - 1;
            } else {
                favoritePostCount = favoritePostCount + 1;
            }
        }

        if (favoritePostIndicatorElement) {
            favoritePostIndicatorElement.textContent = favoritePostCount <= 99 ? String(favoritePostCount) : '99+';
            favoritePostIndicatorElement?.setAttribute('data-favorite-post-count', String(favoritePostCount));

            if (favoritePostCount <= 0) {
                favoritePostIndicatorElement.classList.remove('page-learn__header-quick-access-indicator--visible');
            } else {
                favoritePostIndicatorElement.classList.add('page-learn__header-quick-access-indicator--visible');
            }
        }
    }

    private async sendFavoriteRequest(postId: string, isFavorite: boolean): Promise<boolean> {
        let response = null;

        this.updateIndicator(isFavorite);

        if (isFavorite) {
            response = await removePostFromFavorite(postId);
        } else {
            response = await addPostToFavorite(postId);
        }

        if (!response.success && response.message) {
            showErrorToast(response.message);
            return false;
        }

        return true;
    }

    public checkUserAuth(): boolean {
        const isUser = document.querySelector('[data-user]')?.getAttribute('data-user');

        if (isUser !== 'true') {
            const loginUrl = RouterService.generate('aw_login', {
                BackTo: encodeURIComponent(RouterService.generate('aw_blog_learn')),
            });
            window.location.href = loginUrl;
            return false;
        }

        return true;
    }
}

export const favoriteService = new FavoriteService();
