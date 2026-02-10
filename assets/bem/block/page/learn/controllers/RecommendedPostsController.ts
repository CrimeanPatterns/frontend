import { bem } from '@Bem/ts/service/bem';
import { pageName } from '../consts';
import { Post } from '../types/post';
import { ApiResponse, addPostToLearned, removePostFromLearned } from '../services/api';
import { showErrorToast, showInfoToast } from '@Bem/ts/service/toast/toast';
import { createPostElement } from '../components/PostElement';
import { learnedButtonIndicatorService } from '../services/LearnedButtonIndicatorService';

interface PostAction {
    postId: string;
    postIndex: number;
    action: 'learned' | 'unlearned';
    timestamp: number;
}

interface PostAnimationState {
    isAnimating: boolean;
    element: HTMLElement | null;
}

export class RecommendedPostsController {
    private postsContainerElement: HTMLDivElement | null = null;
    private postsData: Post[] | null = null;
    private currentPostIndex: number = 0;
    private actionHistory: PostAction[] = [];
    private animationStates: Map<string, PostAnimationState> = new Map();
    private readonly ANIMATION_DURATION = 300;
    private readonly TOAST_DURATION = 15000;
    private activeToasts: Map<string, { hideToast: () => void }> = new Map();

    constructor(private containerElement: HTMLDivElement) {
        this.postsContainerElement = this.containerElement.querySelector(`.${pageName}__recommended-posts`);
        this.getPostsData();
    }

    private getPostsData() {
        const postsData = this.postsContainerElement?.getAttribute('data-recommended-posts');
        if (postsData) {
            try {
                this.postsData = JSON.parse(postsData);
            } catch (error) {
                console.log(error);
            }
        }
    }

    private async sendLearnedRequest(postId: string, isUndo: boolean): Promise<ApiResponse> {
        learnedButtonIndicatorService.updateIndicator(isUndo);

        if (isUndo) {
            return await removePostFromLearned(postId);
        } else {
            return await addPostToLearned(postId);
        }
    }

    public handleLearnedButtonClick = (e: MouseEvent, initialLearnButton?: HTMLButtonElement) => {
        const target = e.target as HTMLElement;
        const learnButton = target.closest<HTMLElement>(`.${bem('post', 'learned-button')}`);
        const that = this;
        if (initialLearnButton) {
            e.preventDefault();
            that.handleLearnedClick(initialLearnButton);
            return;
        }

        if (learnButton) {
            e.preventDefault();
            that.handleLearnedClick(learnButton);
            return;
        }
    };

    private async handleLearnedClick(button: HTMLElement) {
        const postElement = button.closest<HTMLElement>('.post');
        if (!postElement) return;

        const postId = postElement.getAttribute('data-post-id');
        if (!postId) return;

        if (this.animationStates.get(postId)?.isAnimating) {
            return;
        }

        this.animationStates.set(postId, {
            isAnimating: true,
            element: postElement,
        });

        try {
            await this.animatePostOut(postElement);
            const response = await this.sendLearnedRequest(postId, false);

            if (response.success) {
                this.recordAction(postId, this.currentPostIndex, 'learned');

                this.showUndoToast(postId, this.currentPostIndex);

                await this.moveToNextPost();
                this.clearFixedHeight();
            } else {
                await this.animatePostIn(postElement);
                showErrorToast(response.message || 'Failed to mark as learned');
            }
        } catch (error) {
            await this.animatePostIn(postElement);
            showErrorToast('Failed to mark as learned');
        } finally {
            this.animationStates.delete(postId);
        }
    }

    private setFixedHeight() {
        const currentHeight = this.containerElement.offsetHeight;
        this.containerElement.style.minHeight = `${currentHeight}px`;
    }

    private clearFixedHeight() {
        this.containerElement.style.minHeight = '';
    }

    private async animatePostOut(element: HTMLElement) {
        return new Promise((resolve) => {
            element.style.transition = `opacity ${this.ANIMATION_DURATION}ms ease-out, transform ${this.ANIMATION_DURATION}ms ease-out`;
            element.style.opacity = '0';
            element.style.transform = 'translateX(-20px) scale(0.95)';

            setTimeout(resolve, this.ANIMATION_DURATION);
        });
    }

    private recordAction(postId: string, postIndex: number, action: 'learned' | 'unlearned') {
        this.actionHistory.push({
            postId,
            postIndex,
            action,
            timestamp: Date.now(),
        });
    }

    private showUndoToast(postId: string, postIndex: number) {
        const toastContent = this.createUndoToastContent(() => {
            this.handleUndoClick(postId, postIndex);
        });

        const toast = showInfoToast('', {
            duration: this.TOAST_DURATION,
            customContent: toastContent,
        });

        this.activeToasts.set(postId, toast);

        setTimeout(() => {
            this.activeToasts.delete(postId);
        }, this.TOAST_DURATION + 1000);
    }

    private createUndoToastContent(onUndoClick: () => void) {
        const container = document.createElement('div');
        container.className = bem('post', 'toast-content-container');

        const message = document.createElement('p');
        message.textContent = 'Moved into "Learned" section.';
        message.className = bem('post', 'toast-content-text');

        const undoButton = document.createElement('button');
        undoButton.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9.16634 17.4583C7.48579 17.25 6.09342 16.5173 4.98926 15.2604C3.88509 14.0035 3.33301 12.5278 3.33301 10.8333C3.33301 9.91665 3.51356 9.03817 3.87467 8.1979C4.23579 7.35762 4.74967 6.62498 5.41634 5.99998L6.60384 7.18748C6.07606 7.6597 5.67676 8.20831 5.40592 8.33331C5.13509 9.45831 4.99967 10.125 4.99967 10.8333C4.99967 12.0555 5.38856 13.1354 6.16634 14.0729C6.94412 15.0104 7.94412 15.5833 9.16634 15.7916V17.4583ZM10.833 17.4583V15.7916C12.0413 15.5694 13.0379 14.993 13.8226 14.0625C14.6073 13.1319 14.9997 12.0555 14.9997 10.8333C14.9997 9.44442 14.5136 8.26387 13.5413 7.29165C12.5691 6.31942 11.3886 5.83331 9.99968 5.83331H9.93718L10.8538 6.74998L9.68718 7.91665L6.77051 4.99998L9.68718 2.08331L10.8538 3.24998L9.93718 4.16665H9.99968C11.8608 4.16665 13.4372 4.81248 14.7288 6.10415C16.0205 7.39581 16.6663 8.9722 16.6663 10.8333C16.6663 12.5139 16.1143 13.9826 15.0101 15.2396C13.9059 16.4965 12.5136 17.2361 10.833 17.4583Z" fill="currentColor"/>
            </svg>
            <span>Undo</span>
        `;
        undoButton.className = bem('post', 'toast-undo-button');
        undoButton.addEventListener('click', onUndoClick);

        container.appendChild(message);
        container.appendChild(undoButton);

        return container;
    }

    private async handleUndoClick(targetPostId: string, targetPostIndex: number) {
        const toast = this.activeToasts.get(targetPostId);
        if (toast) {
            toast.hideToast();
            this.activeToasts.delete(targetPostId);
        }

        try {
            const response = await this.sendLearnedRequest(targetPostId, true);

            if (response.success) {
                this.actionHistory = this.actionHistory.filter(
                    (action) => !(action.postId === targetPostId && action.action === 'learned'),
                );

                await this.restoreToIndex(targetPostIndex);
            } else {
                showErrorToast(response.message || 'Failed to undo');
            }
        } catch (error) {
            showErrorToast('Failed to undo');
        }
    }

    private async restoreToIndex(targetIndex: number) {
        if (!this.postsData || targetIndex >= this.postsData.length) return;

        const needsUIRestore = targetIndex < this.currentPostIndex;

        if (needsUIRestore) {
            this.currentPostIndex = targetIndex;

            await this.showSection();

            const currentPostElement = this.containerElement?.querySelector<HTMLElement>('.post');

            if (currentPostElement) {
                await this.animatePostOut(currentPostElement);
            }

            const post = this.postsData[targetIndex];
            if (post) {
                const postElement = await createPostElement(post);
                if (this.postsContainerElement) {
                    this.postsContainerElement.innerHTML = '';
                    this.postsContainerElement.appendChild(postElement);
                    await this.animatePostIn(postElement);
                }
            }
        }
    }

    private async showSection() {
        if (this.containerElement && this.containerElement.style.display === 'none') {
            await this.animateSectionIn(this.containerElement);
        }
    }

    private async animatePostIn(element: HTMLElement): Promise<void> {
        return new Promise((resolve) => {
            element.style.opacity = '0';
            element.style.transform = 'translateX(20px) scale(0.95)';
            element.style.transition = `opacity ${this.ANIMATION_DURATION}ms ease-out, transform ${this.ANIMATION_DURATION}ms ease-out`;

            element.offsetHeight;

            requestAnimationFrame(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateX(0) scale(1)';

                setTimeout(() => {
                    element.style.transition = '';
                    element.style.transform = '';
                    resolve();
                }, this.ANIMATION_DURATION);
            });
        });
    }

    private async moveToNextPost() {
        do {
            this.currentPostIndex++;
        } while (
            this.postsData &&
            this.currentPostIndex < this.postsData.length &&
            this.isPostLearned(this.postsData[this.currentPostIndex])
        );

        if (this.postsData && this.currentPostIndex < this.postsData.length) {
            await this.renderCurrentPost();
        } else {
            await this.hideSection();
        }
    }

    private isPostLearned(post: Post | undefined) {
        if (!post || !post.id) return false;

        const learnedAction = this.actionHistory
            .filter((action) => action.postId === post.id.toString())
            .sort((a, b) => b.timestamp - a.timestamp)[0];

        return learnedAction?.action === 'learned';
    }

    private async renderCurrentPost() {
        if (!this.postsData || this.currentPostIndex >= this.postsData.length) {
            await this.hideSection();
            return;
        }

        const currentPost = this.postsData[this.currentPostIndex];
        if (currentPost && this.postsContainerElement) {
            const postElement = await createPostElement(currentPost);

            this.setFixedHeight();

            this.postsContainerElement.innerHTML = '';
            this.postsContainerElement.appendChild(postElement);

            this.animatePostIn(postElement);

            await this.showSection();
        }
    }

    private async animateSectionIn(element: HTMLElement): Promise<void> {
        return new Promise((resolve) => {
            element.style.display = 'block';
            element.style.opacity = '0';
            element.style.transform = 'translateY(-20px)';
            element.style.maxHeight = '0';
            element.style.overflow = 'hidden';
            element.style.transition = `opacity 400ms ease-out, transform 400ms ease-out, max-height 400ms ease-out`;

            element.offsetHeight;

            requestAnimationFrame(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
                element.style.maxHeight = 'none';
                element.style.overflow = 'visible';

                setTimeout(() => {
                    element.style.transition = '';
                    element.style.transform = '';
                    element.style.maxHeight = '';
                    element.style.overflow = '';
                    resolve();
                }, 400);
            });
        });
    }

    private async animateSectionOut(element: HTMLElement): Promise<void> {
        return new Promise((resolve) => {
            element.style.transition = `opacity 400ms ease-out, transform 400ms ease-out, max-height 400ms ease-out`;
            element.style.opacity = '0';
            element.style.transform = 'translateY(-20px)';
            element.style.maxHeight = '0';
            element.style.overflow = 'hidden';

            setTimeout(() => {
                element.style.display = 'none';
                resolve();
            }, 400);
        });
    }

    private async hideSection() {
        if (this.containerElement && this.containerElement.style.display !== 'none') {
            await this.animateSectionOut(this.containerElement);
        }
    }
}
