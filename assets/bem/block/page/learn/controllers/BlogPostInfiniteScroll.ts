import { showErrorToast, showToast } from '@Bem/ts/service/toast/toast';
import axios from '@Bem/ts/service/axios';
import { AxiosError } from 'axios';
import { createPostElement } from '../components/PostElement';
import { LabelPostsData, Post } from '../types/post';

export class BlogPostInfiniteScroll {
    private postList = document.getElementById('infinity-content') as HTMLUListElement;
    private loader: HTMLElement = document.getElementById('infinity-loader') as HTMLElement;
    private nextLoadingLink: string | null = null;
    private isLoading: boolean = false;
    private hasMoreData: boolean = true;

    private allPosts: Post[] = [];
    private postHeights: Map<number, number> = new Map();
    private removedPosts: Map<string, { post: Post; originalIndex: number; height: number }> = new Map();
    private lastStartIndex = -1;
    private lastEndIndex = -1;
    private maxRenderCount = 50;

    private boundScrollHandler: (() => void) | null = null;
    private boundResizeHandler: (() => void) | null = null;

    constructor(initialLink?: string) {
        if (initialLink) {
            this.nextLoadingLink = initialLink;
        } else if (this.postList) {
            const nextLoadingLink = this.postList.dataset['nextpage'];
            if (nextLoadingLink) {
                this.nextLoadingLink = nextLoadingLink;
            }
        }

        this.boundScrollHandler = this.handleScroll.bind(this);
        this.boundResizeHandler = this.handleResize.bind(this);

        window.addEventListener('scroll', this.boundScrollHandler, { passive: true });
        window.addEventListener('resize', this.boundResizeHandler);

        this.initializeWithInitialContent();
    }

    private async initializeWithInitialContent(): Promise<void> {
        //@ts-expect-error set initialPosts in twig
        if (window.initialPosts && Array.isArray(window.initialPosts) && window.initialPosts.length > 0) {
            //@ts-expect-error set initialPosts in twig
            this.allPosts = [...window.initialPosts];

            this.calculateInitialPostsHeight();

            this.setupVirtualizedRendering();

            return;
        }

        if (this.nextLoadingLink) {
            await this.fetchMoreItems(true);
        }
    }

    private handleScroll(): void {
        const scrollPosition = window.scrollY || document.documentElement.scrollTop;

        if (!this.isLoading && this.hasMoreData && this.nextLoadingLink) {
            const windowHeight = window.innerHeight;
            const postListRect = this.postList.getBoundingClientRect();

            const distanceFromBottom = windowHeight - postListRect.bottom;

            const nearEnd = distanceFromBottom > -100;

            if (nearEnd) {
                this.fetchMoreItems();
            }
        }

        this.updateVisibleItems(scrollPosition);
    }

    private handleResize(): void {
        this.calculateInitialPostsHeight();

        this.lastStartIndex = -1;
        this.lastEndIndex = -1;

        const scrollPosition = window.scrollY || document.documentElement.scrollTop;
        this.updateVisibleItems(scrollPosition);
    }

    private calculateInitialPostsHeight(): void {
        const postElements = Array.from(this.postList.children) as HTMLElement[];

        this.postHeights.clear();

        if (postElements.length > 0) {
            postElements.forEach((el, index) => {
                if (el.offsetHeight > 0) {
                    const computedStyle = window.getComputedStyle(el);
                    const marginBottom = Number(computedStyle.marginBottom.replace('px', ''));
                    el.dataset.index = index.toString();

                    let topPosition = 0;
                    for (let i = 0; i < index; i++) {
                        topPosition += this.postHeights.get(i) || 0;
                    }

                    el.style.position = 'absolute';
                    el.style.top = `${topPosition}px`;
                    el.style.left = '0';
                    el.style.width = '100%';

                    const actualHeight = el.offsetHeight + (isNaN(marginBottom) ? 0 : marginBottom);
                    this.postHeights.set(index, actualHeight);
                }
            });
            let totalContainerHeight = 0;
            for (let i = 0; i < this.allPosts.length; i++) {
                totalContainerHeight += this.postHeights.get(i) || 0;
            }

            this.postList.style.height = `${totalContainerHeight}px`;
        } else {
            this.postList.style.height = 'auto';
        }
    }

    private setupVirtualizedRendering(): void {
        if (this.allPosts.length === 0) {
            return;
        }

        let totalHeight = 0;
        for (const height of this.postHeights.values()) {
            totalHeight += height;
        }

        this.postList.style.height = `${totalHeight}px`;

        this.lastStartIndex = -1;
        this.lastEndIndex = -1;

        const scrollPosition = window.scrollY || document.documentElement.scrollTop;

        this.updateVisibleItems(scrollPosition);
    }

    private updateVisibleItems(scrollPosition: number): void {
        if (!this.postList || this.allPosts.length === 0) return;

        const rect = this.postList.getBoundingClientRect();
        const postListTopCoords = rect.top + scrollPosition;
        const isPostListVisible = rect.top < window.innerHeight && rect.bottom > 0;

        if (!isPostListVisible) {
            return;
        }

        const relativeScrollPosition = Math.max(0, scrollPosition - postListTopCoords);

        let accumulatedHeight = 0;
        let firstVisibleIndex = 0;

        for (let i = 0; i < this.allPosts.length; i++) {
            const height = this.postHeights.get(i);

            if (height) {
                if (accumulatedHeight + height > relativeScrollPosition) {
                    firstVisibleIndex = i;
                    break;
                }

                accumulatedHeight += height;
            }
        }

        const halfCount = Math.floor(this.maxRenderCount / 2);
        const startIndex = Math.max(0, firstVisibleIndex - halfCount);
        const endIndex = Math.min(this.allPosts.length - 1, startIndex + this.maxRenderCount - 1);

        if (startIndex === this.lastStartIndex && endIndex === this.lastEndIndex) {
            return;
        }

        this.lastStartIndex = startIndex;
        this.lastEndIndex = endIndex;

        const shouldBeVisible = new Set<number>();
        for (let i = startIndex; i <= endIndex; i++) {
            shouldBeVisible.add(i);
        }

        Array.from(this.postList.children).forEach((child) => {
            const index = parseInt((child as HTMLElement).dataset.index || '-1');

            if (!shouldBeVisible.has(index)) {
                child.remove();
            } else {
                shouldBeVisible.delete(index);
            }
        });

        Array.from(shouldBeVisible)
            .sort((a, b) => a - b)
            .forEach(async (index) => {
                await this.renderItemAtIndex(index);
            });
    }

    private async renderItemAtIndex(index: number) {
        if (!this.postList || index >= this.allPosts.length) return;

        try {
            const post = this.allPosts[index];
            if (!post) return;

            const postElement = await createPostElement(post);

            let topPosition = 0;
            for (let i = 0; i < index; i++) {
                topPosition += this.postHeights.get(i) || this.getEstimatedHeight();
            }

            postElement.style.position = 'absolute';
            postElement.style.top = `${topPosition}px`;
            postElement.style.left = '0';
            postElement.style.width = '100%';

            postElement.dataset.index = index.toString();
            if (post.id) {
                postElement.dataset.postId = post.id.toString();
            }

            this.postList.appendChild(postElement);

            requestAnimationFrame(() => {
                if (postElement.offsetHeight > 0) {
                    const computedStyle = window.getComputedStyle(postElement);
                    const marginBottom = Number(computedStyle.marginBottom.replace('px', ''));
                    const actualHeight = postElement.offsetHeight + (isNaN(marginBottom) ? 0 : marginBottom);
                    const currentHeight = this.postHeights.get(index) || 0;

                    if (Math.abs(actualHeight - currentHeight) > 5) {
                        this.postHeights.set(index, actualHeight);

                        this.updateAllItemPositions(index + 1);
                        this.updateTotalContainerHeight();
                    }
                }
            });
        } catch (e) {
            console.error(`Error rendering item at index ${index}:`, e);
        }
    }

    private async fetchMoreItems(isInitialLoad: boolean = false): Promise<void> {
        if (!this.hasMoreData || this.isLoading || !this.nextLoadingLink) return;

        this.isLoading = true;
        if (this.loader) {
            this.loader.style.display = 'block';
        }

        try {
            const response = (await axios.post<LabelPostsData>(this.nextLoadingLink)).data;

            if (!response.posts || response.posts.length === 0) {
                this.hasMoreData = false;
                if (this.loader) {
                    this.loader.style.display = 'none';
                }

                return;
            }

            const startIndex = this.allPosts.length;
            this.allPosts = [...this.allPosts, ...response.posts];

            response.posts.forEach((_, i) => {
                const index = startIndex + i;
                this.postHeights.set(index, this.getEstimatedHeight());
            });

            if (isInitialLoad || !this.postList) {
                this.setupVirtualizedRendering();
            } else {
                this.updateTotalContainerHeight();

                this.lastStartIndex = -1;
                this.lastEndIndex = -1;

                const scrollPosition = window.scrollY || document.documentElement.scrollTop;
                this.updateVisibleItems(scrollPosition);

                setTimeout(() => this.measureNewlyAddedItems(startIndex), 100);
            }

            if (response.nextPage) {
                this.nextLoadingLink = response.nextPage;
            } else {
                this.nextLoadingLink = null;
                this.hasMoreData = false;
            }
        } catch (error) {
            console.error('Error fetching items:', error);
            if (error instanceof AxiosError) {
                showErrorToast(error.message);
            } else {
                showErrorToast('Error loading posts');
            }

            this.hasMoreData = false;
        } finally {
            this.isLoading = false;
            if (this.loader) {
                this.loader.style.display = 'none';
            }
        }
    }

    private getEstimatedHeight(): number {
        if (window.innerWidth <= 568) {
            return 400;
        } else {
            return 200;
        }
    }

    private updateTotalContainerHeight(): void {
        if (!this.postList) return;

        let totalContainerHeight = 0;
        for (let i = 0; i < this.allPosts.length; i++) {
            totalContainerHeight += this.postHeights.get(i) || this.getEstimatedHeight();
        }

        this.postList.style.height = `${totalContainerHeight}px`;
    }

    private measureNewlyAddedItems(startIndex: number): void {
        if (!this.postList) return;

        let needsPositionUpdate = false;

        Array.from(this.postList.children).forEach((element) => {
            const postElement = element as HTMLElement;
            const postIndex = parseInt(postElement.dataset.index || '-1');

            if (postIndex >= startIndex) {
                const oldHeight = this.postHeights.get(postIndex) || 0;

                const computedStyle = window.getComputedStyle(postElement);
                const marginBottom = Number(computedStyle.marginBottom.replace('px', ''));
                const actualHeight = postElement.offsetHeight + (isNaN(marginBottom) ? 0 : marginBottom);

                if (Math.abs(actualHeight - oldHeight) > 5) {
                    this.postHeights.set(postIndex, actualHeight);
                    needsPositionUpdate = true;
                }
            }
        });

        if (needsPositionUpdate) {
            this.updateAllItemPositions(startIndex);
            this.updateTotalContainerHeight();
        }
    }

    private updateAllItemPositions(fromIndex: number): void {
        if (!this.postList) return;

        Array.from(this.postList.children).forEach((element) => {
            const postElement = element as HTMLElement;
            const postIndex = parseInt(postElement.dataset.index || '-1');

            if (postIndex >= fromIndex) {
                let topPosition = 0;

                for (let i = 0; i < postIndex; i++) {
                    topPosition += this.postHeights.get(i) || this.getEstimatedHeight();
                }

                postElement.style.transition = 'top 0.3s';
                postElement.style.top = `${topPosition}px`;

                setTimeout(() => {
                    postElement.style.transition = '';
                }, 300);
            }
        });
    }

    public removePost(postId: string): void {
        const postIndex = this.allPosts.findIndex((post) => post.id && post.id.toString() === postId);

        if (postIndex === -1) return;

        const post = this.allPosts[postIndex];

        const postHeight = this.postHeights.get(postIndex) || 0;
        this.removedPosts.set(postId, {
            post: { ...post! },
            originalIndex: postIndex,
            height: postHeight,
        });

        this.allPosts.splice(postIndex, 1);

        const postElements = Array.from(this.postList.children) as HTMLElement[];
        const removedElement = postElements.find((el) => el.getAttribute('data-post-id') === postId);

        if (removedElement) {
            removedElement.style.transition = 'opacity 0.3s, transform 0.3s';
            removedElement.style.opacity = '0';
            removedElement.style.transform = 'scaleY(0.1)';

            setTimeout(() => {
                removedElement.remove();

                this.recalculateIndexesAfterRemoval(postIndex);

                this.lastStartIndex = -1;
                this.lastEndIndex = -1;
                const scrollPosition = window.scrollY || document.documentElement.scrollTop;
                this.updateVisibleItems(scrollPosition);
            }, 300);
        } else {
            this.recalculateIndexesAfterRemoval(postIndex);

            this.lastStartIndex = -1;
            this.lastEndIndex = -1;
            const scrollPosition = window.scrollY || document.documentElement.scrollTop;
            this.updateVisibleItems(scrollPosition);
        }
    }

    private recalculateIndexesAfterRemoval(removedIndex: number): void {
        const oldHeights = new Map(this.postHeights);
        this.postHeights.clear();

        for (let i = 0; i < this.allPosts.length; i++) {
            if (i < removedIndex) {
                this.postHeights.set(i, oldHeights.get(i) || 0);
            } else {
                this.postHeights.set(i, oldHeights.get(i + 1) || 0);
            }
        }

        const postElements = Array.from(this.postList.children) as HTMLElement[];
        for (const element of postElements) {
            const elementIndex = parseInt(element.dataset.index || '-1');

            if (elementIndex > removedIndex) {
                const newIndex = elementIndex - 1;
                element.dataset.index = newIndex.toString();

                let topPosition = 0;
                for (let i = 0; i < newIndex; i++) {
                    topPosition += this.postHeights.get(i) || 0;
                }

                element.style.transition = 'top 0.3s';
                element.style.top = `${topPosition}px`;
            }
        }

        this.updateTotalContainerHeight();

        setTimeout(() => {
            postElements.forEach((element) => {
                element.style.transition = '';
            });
        }, 300);
    }

    public restorePost(postId: string): void {
        const removedData = this.removedPosts.get(postId);
        if (!removedData) return;

        const { post, originalIndex } = removedData;

        const targetIndex = Math.min(originalIndex, this.allPosts.length);
        this.allPosts.splice(targetIndex, 0, post);

        const oldHeights = new Map(this.postHeights);
        this.postHeights.clear();

        for (let i = 0; i < this.allPosts.length; i++) {
            if (i < targetIndex) {
                this.postHeights.set(i, oldHeights.get(i) || 0);
            } else if (i === targetIndex) {
                this.postHeights.set(i, removedData.height);
            } else {
                this.postHeights.set(i, oldHeights.get(i - 1) || 0);
            }
        }

        const postElements = Array.from(this.postList.children) as HTMLElement[];
        for (const element of postElements) {
            const elementIndex = parseInt(element.dataset.index || '-1');

            if (elementIndex >= targetIndex) {
                const newIndex = elementIndex + 1;
                element.dataset.index = newIndex.toString();

                let topPosition = 0;
                for (let i = 0; i < newIndex; i++) {
                    topPosition += this.postHeights.get(i) || 0;
                }

                element.style.transition = 'top 0.3s';
                element.style.top = `${topPosition}px`;
            }
        }

        this.removedPosts.delete(postId);

        this.updateTotalContainerHeight();

        this.lastStartIndex = -1;
        this.lastEndIndex = -1;
        const scrollPosition = window.scrollY || document.documentElement.scrollTop;
        this.updateVisibleItems(scrollPosition);

        setTimeout(() => {
            postElements.forEach((element) => {
                element.style.transition = '';
            });
        }, 300);
    }

    public reset(nextLoadingLink: string): void {
        this.postList = document.getElementById('infinity-content') as HTMLUListElement;
        this.postList.style.position = 'relative';
        this.loader = document.getElementById('infinity-loader') as HTMLElement;

        this.nextLoadingLink = nextLoadingLink;
        this.isLoading = false;
        this.hasMoreData = true;
        this.lastStartIndex = -1;
        this.lastEndIndex = -1;
        this.allPosts = [];
        this.removedPosts.clear();

        if (this.boundScrollHandler) {
            window.removeEventListener('scroll', this.boundScrollHandler);
        }

        if (this.boundResizeHandler) {
            window.removeEventListener('resize', this.boundResizeHandler);
        }

        if (this.boundScrollHandler) {
            window.addEventListener('scroll', this.boundScrollHandler, { passive: true });
        }

        if (this.boundResizeHandler) {
            window.addEventListener('resize', this.boundResizeHandler);
        }

        this.initializeWithInitialContent();
    }

    public disconnect() {
        if (this.boundScrollHandler) {
            window.removeEventListener('scroll', this.boundScrollHandler);
        }

        if (this.boundResizeHandler) {
            window.removeEventListener('resize', this.boundResizeHandler);
        }

        this.allPosts = [];
        if (this.postList) {
            this.postList.style.height = 'auto';
        }
    }
}
