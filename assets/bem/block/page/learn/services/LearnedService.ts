import RouterService from '@Bem/ts/service/router';
import { addPostToLearned, removePostFromLearned } from '../services/api';
import { showErrorToast, showInfoToast } from '@Bem/ts/service/toast/toast';
import { Router } from '@Services/Router';
import { bem } from '@Bem/ts/service/bem';
import { learnedButtonIndicatorService } from './LearnedButtonIndicatorService';

export class LearnedService {
    constructor(
        private latestNewsController?: any,
        private blogPostInfinityScroll?: any,
    ) {}

    public setDependencies(latestNewsController: any, blogPostInfinityScroll: any) {
        this.latestNewsController = latestNewsController;
        this.blogPostInfinityScroll = blogPostInfinityScroll;
    }

    public async toggleLearned(button: Element, postId: string, isLearned: boolean): Promise<boolean> {
        const postElement = button.closest<HTMLDivElement>('.post');
        const swiperSlide = postElement?.closest<HTMLDivElement>('.swiper-slide');
        const categoryPostContainer = postElement?.closest<HTMLDivElement>('.page-learn__category-posts');
        const isInfinityContent = categoryPostContainer?.getAttribute('id') === 'infinity-content';
        const postsTitleBlock: HTMLDivElement | null = categoryPostContainer?.previousElementSibling?.matches(
            '.page-learn__category-title-block',
        )
            ? (categoryPostContainer.previousElementSibling as HTMLDivElement)
            : null;
        const postsGroupMobileReadMore = categoryPostContainer?.nextElementSibling?.matches(
            '.page-learn__category-link',
        )
            ? (categoryPostContainer.nextElementSibling as HTMLDivElement)
            : null;

        const isFavoritesPage = document.location.pathname === Router.generate('aw_blog_learn_favorite');

        if (!isFavoritesPage) {
            this.hidePost(swiperSlide || null, categoryPostContainer || null, isInfinityContent, {
                postElement,
                postsTitleBlock,
                postsGroupMobileReadMore,
                postId,
            });
        } else {
            this.updateLearnedUI(postElement, !isLearned);
        }

        try {
            const isSuccess = await this.sendLearnedRequest(postId, isLearned);

            if (isSuccess) {
                let removeTimer: ReturnType<typeof setTimeout>;
                let toast: ReturnType<typeof showInfoToast> | null = null;
                const toastContent = this.createToastContent(isLearned, () => {
                    if (!isFavoritesPage) {
                        this.revertChanges(swiperSlide || null, categoryPostContainer || null, isInfinityContent, {
                            postElement,
                            postsTitleBlock,
                            postsGroupMobileReadMore,
                            postId,
                        });
                    } else {
                        this.updateLearnedUI(postElement, isLearned);
                    }
                    clearTimeout(removeTimer);
                    if (toast) {
                        toast.hideToast();
                    }
                    try {
                        this.sendLearnedRequest(postId, !isLearned);
                    } catch (error) {}
                });

                toast = showInfoToast('', { duration: 15000, customContent: toastContent });
                removeTimer = setTimeout(() => {
                    if (!isFavoritesPage) {
                        this.removePost(categoryPostContainer || null, isInfinityContent, {
                            postElement,
                            postsTitleBlock,
                            postsGroupMobileReadMore,
                            postId,
                        });
                    }
                }, 16000);

                return true;
            } else {
                if (!isFavoritesPage) {
                    this.revertChanges(swiperSlide || null, categoryPostContainer || null, isInfinityContent, {
                        postElement,
                        postsTitleBlock,
                        postsGroupMobileReadMore,
                        postId,
                    });
                } else {
                    this.updateLearnedUI(postElement, isLearned);
                }
                return false;
            }
        } catch (error) {
            if (isLearned) {
                showErrorToast('Failed to remove from learned');
            } else {
                showErrorToast('Failed to add to learned');
            }

            if (!isFavoritesPage) {
                this.revertChanges(swiperSlide || null, categoryPostContainer || null, isInfinityContent, {
                    postElement,
                    postsTitleBlock,
                    postsGroupMobileReadMore,
                    postId,
                });
            } else {
                this.updateLearnedUI(postElement, isLearned);
            }

            return false;
        }
    }

    private updateLearnedUI(postElement: HTMLElement | null, makeMarked: boolean): void {
        if (!postElement) return;

        const learnedButton = postElement.querySelector<HTMLElement>('.post__learned-button');
        const menuLearnedButton = postElement.querySelector<HTMLElement>(
            `.${bem('post', 'mobile-menu-item-button--learned')}`,
        );

        if (learnedButton) {
            this.updateLearnedButtonUI(learnedButton, makeMarked);
        }

        if (menuLearnedButton) {
            this.updateMenuLearnedButtonUI(menuLearnedButton, makeMarked);
        }
    }

    private updateLearnedButtonUI(button: HTMLElement, makeMarked: boolean): void {
        button.classList.toggle('post__learned-button--undo', makeMarked);

        const tooltip = button.closest('.post')?.querySelector('.post__action-tooltip--learn');
        if (tooltip) {
            tooltip.textContent = makeMarked ? 'Remove from "Learned"' : 'Mark as "Learned"';
        }
    }

    private updateMenuLearnedButtonUI(button: HTMLElement, makeMarked: boolean): void {
        button.classList.toggle('post__mobile-menu-item-button--active', makeMarked);

        const textElement = button.querySelector('span');
        if (textElement) {
            textElement.textContent = makeMarked ? 'Remove from "Learned"' : 'Mark as "Learned"';
        }
    }

    private createToastContent(isLearned: boolean, onUndoClick: () => void) {
        const container = document.createElement('div');
        container.className = bem('post', 'toast-content-container');

        const message = document.createElement('p');
        message.textContent = isLearned ? 'Marked as not learned.' : 'Moved into "Learned" section.';
        message.className = bem('post', 'toast-content-text');

        const undoButton = document.createElement('button');
        undoButton.innerHTML = `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M9.16634 17.4583C7.48579 17.25 6.09342 16.5173 4.98926 15.2604C3.88509 14.0035 3.33301 12.5278 3.33301 10.8333C3.33301 9.91665 3.51356 9.03817 3.87467 8.1979C4.23579 7.35762 4.74967 6.62498 5.41634 5.99998L6.60384 7.18748C6.07606 7.6597 5.67676 8.20831 5.40592 8.83331C5.13509 9.45831 4.99967 10.125 4.99967 10.8333C4.99967 12.0555 5.38856 13.1354 6.16634 14.0729C6.94412 15.0104 7.94412 15.5833 9.16634 15.7916V17.4583ZM10.833 17.4583V15.7916C12.0413 15.5694 13.0379 14.993 13.8226 14.0625C14.6073 13.1319 14.9997 12.0555 14.9997 10.8333C14.9997 9.44442 14.5136 8.26387 13.5413 7.29165C12.5691 6.31942 11.3886 5.83331 9.99968 5.83331H9.93718L10.8538 6.74998L9.68718 7.91665L6.77051 4.99998L9.68718 2.08331L10.8538 3.24998L9.93718 4.16665H9.99968C11.8608 4.16665 13.4372 4.81248 14.7288 6.10415C16.0205 7.39581 16.6663 8.9722 16.6663 10.8333C16.6663 12.5139 16.1143 13.9826 15.0101 15.2396C13.9059 16.4965 12.5136 17.2361 10.833 17.4583Z" fill="currentColor"/>
</svg>
`;
        undoButton.className = bem('post', 'toast-undo-button');
        undoButton.addEventListener('click', onUndoClick);

        const buttonText = document.createElement('span');
        buttonText.textContent = 'Undo';

        undoButton.append(buttonText);

        container.append(message);
        container.append(undoButton);

        return container;
    }

    private async sendLearnedRequest(postId: string, isLearned: boolean): Promise<boolean> {
        let response = null;

        learnedButtonIndicatorService.updateIndicator(isLearned);

        if (isLearned) {
            response = await removePostFromLearned(postId);
        } else {
            response = await addPostToLearned(postId);
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

    private hidePost(
        swiperSlide: HTMLDivElement | null,
        categoryPostContainer: HTMLDivElement | null,
        isInfinityContent: boolean,
        {
            postElement,
            postsTitleBlock,
            postsGroupMobileReadMore,
            postId,
        }: {
            postElement: HTMLDivElement | null;
            postsTitleBlock: HTMLDivElement | null;
            postsGroupMobileReadMore: HTMLDivElement | null;
            postId: string;
        },
    ) {
        if (swiperSlide) {
            this.hideSlide(swiperSlide);
            return;
        }

        if (categoryPostContainer && !isInfinityContent) {
            this.hideGeneralCategoryPost(postElement, {
                postsTitleBlock,
                postsGroupMobileReadMore,
                categoryPostContainer,
            });
        } else if (this.blogPostInfinityScroll) {
            this.blogPostInfinityScroll.removePost(postId);
        }
        return;
    }

    private hideSlide(swiperSlide: HTMLDivElement) {
        if (!this.latestNewsController) return;

        swiperSlide.classList.add('page-learn__latest-news-swiper-slide--hidden');

        const slideCurrentIndex = swiperSlide.getAttribute('data-slide-index');
        const slideRemove = this.latestNewsController.removeSlide;

        this.latestNewsController.prepareSlideForRemoval();

        setTimeout(() => {
            if (slideCurrentIndex) {
                slideRemove(Number(slideCurrentIndex));
            }
        }, 200);

        const swiperContainer = swiperSlide.closest('.swiper-wrapper');

        if (swiperContainer) {
            const allSlides = swiperContainer.querySelectorAll('.swiper-slide');

            const hasVisibleSlide = Array.from(allSlides).some(
                (slide) => !slide.classList.contains('page-learn__latest-news-swiper-slide--hidden'),
            );

            if (!hasVisibleSlide) {
                this.hideLatestsNewsBlock();
            }
        }
    }

    private hideLatestsNewsBlock() {
        const latestNewsBlock = document.querySelector('.page-learn__latest-news');
        latestNewsBlock?.classList.add('page-learn__latest-news--removed');
    }

    private hideGeneralCategoryPost(
        postElement: HTMLDivElement | null,
        {
            postsTitleBlock,
            postsGroupMobileReadMore,
            categoryPostContainer,
        }: {
            postsTitleBlock: HTMLDivElement | null;
            postsGroupMobileReadMore: HTMLDivElement | null;
            categoryPostContainer: HTMLDivElement | null;
        },
    ) {
        if (postElement && categoryPostContainer && postsTitleBlock) {
            postElement.classList.add('post--removed');

            const remainingPostsCount = Array.from(categoryPostContainer.querySelectorAll('.post')).filter(
                (post) => !post.classList.contains('post--removed'),
            ).length;

            if (remainingPostsCount < 1) {
                this.hidePostsBlock(postsTitleBlock, postsGroupMobileReadMore);
            }
        }
    }

    private hidePostsBlock(postsTitleBlock: HTMLElement, postsGroupMobileReadMore: HTMLDivElement | null) {
        postsTitleBlock.classList.add('page-learn__category-title-block--removed');
        postsGroupMobileReadMore?.classList.add('page-learn__category-link--removed');
    }

    private revertChanges(
        swiperSlide: HTMLDivElement | null,
        categoryPostContainer: HTMLDivElement | null,
        isInfinityContent: boolean,
        {
            postElement,
            postsTitleBlock,
            postsGroupMobileReadMore,
            postId,
        }: {
            postElement: HTMLDivElement | null;
            postsTitleBlock: HTMLDivElement | null;
            postsGroupMobileReadMore: HTMLDivElement | null;
            postId: string;
        },
    ): void {
        if (swiperSlide) {
            this.showSlide(swiperSlide);
        }

        if (categoryPostContainer && !isInfinityContent) {
            this.showGeneralCategoryPost(postElement, {
                postsTitleBlock,
                postsGroupMobileReadMore,
                categoryPostContainer,
            });
        } else if (this.blogPostInfinityScroll) {
            this.blogPostInfinityScroll.restorePost(postId);
        }
    }

    private showSlide(swiperSlide: HTMLDivElement) {
        if (!this.latestNewsController) return;

        swiperSlide.classList.remove('page-learn__latest-news-swiper-slide--hidden');

        this.latestNewsController.addSlide(swiperSlide);

        const swiperContainer = swiperSlide.closest('.swiper-wrapper');

        if (swiperContainer) {
            const allSlides = swiperContainer.querySelectorAll('.swiper-slide');

            const visibleSlides = Array.from(allSlides).filter(
                (slide) => !slide.classList.contains('page-learn__latest-news-swiper-slide--hidden'),
            );

            if (visibleSlides.length === 1) {
                this.showLatestsNewsBlock();
            }
        }
    }

    private showLatestsNewsBlock() {
        const latestNewsBlock = document.querySelector('.page-learn__latest-news');
        latestNewsBlock?.classList.remove('page-learn__latest-news--removed');
    }

    private showGeneralCategoryPost(
        postElement: HTMLDivElement | null,
        {
            postsTitleBlock,
            postsGroupMobileReadMore,
            categoryPostContainer,
        }: {
            postsTitleBlock: HTMLDivElement | null;
            postsGroupMobileReadMore: HTMLDivElement | null;
            categoryPostContainer: HTMLDivElement | null;
        },
    ) {
        if (postElement && categoryPostContainer && postsTitleBlock) {
            const remainingPostsCount = Array.from(categoryPostContainer.querySelectorAll('.post')).filter(
                (post) => !post.classList.contains('post--removed'),
            ).length;

            postElement.classList.remove('post--removed');

            if (remainingPostsCount < 1) {
                this.showPostsBlock(postsTitleBlock, postsGroupMobileReadMore);
            }
        }
    }

    private showPostsBlock(postsTitleBlock: HTMLElement, postsGroupMobileReadMore: HTMLElement | null) {
        postsTitleBlock.classList.remove('page-learn__category-title-block--removed');
        postsGroupMobileReadMore?.classList.remove('page-learn__category-link--removed');
    }

    private removePost(
        categoryPostContainer: HTMLDivElement | null,
        isInfinityContent: boolean,
        {
            postElement,
            postsTitleBlock,
            postsGroupMobileReadMore,
            postId,
        }: {
            postElement: HTMLDivElement | null;
            postsTitleBlock: HTMLDivElement | null;
            postsGroupMobileReadMore: HTMLDivElement | null;
            postId: string;
        },
    ) {
        if (postElement && categoryPostContainer && postsTitleBlock && postsGroupMobileReadMore && !isInfinityContent) {
            this.removePostsBlock(postElement, categoryPostContainer, postsTitleBlock, postsGroupMobileReadMore);
        } else if (this.blogPostInfinityScroll) {
            this.blogPostInfinityScroll.removePost(postId);
        }
    }

    private removePostsBlock(
        postElement: HTMLElement,
        postsGroup: HTMLElement,
        postsTitleBlock: HTMLElement,
        postsGroupMobileReadMore: HTMLElement,
    ) {
        postElement.remove();

        const remainingPostsCount = Array.from(postsGroup.querySelectorAll('.post')).filter(
            (post) => !post.classList.contains('post--removed'),
        ).length;

        if (remainingPostsCount === 0) {
            if (postsTitleBlock) {
                postsTitleBlock.remove();
            }

            if (postsGroupMobileReadMore) {
                postsGroupMobileReadMore.remove();
            }

            if (postsGroup) {
                postsGroup.remove();
            }
        }
    }
}

export const learnedService = new LearnedService();
