import { pageName } from '../consts';
import { createCategoryBlock } from '../components/CategoryBlock';
import { LabelData } from '../types/label';
import { AuthorsController } from './AuthorsController';
import { BlogPostInfiniteScroll } from './BlogPostInfiniteScroll';
import { loadInitialData, loadPosts } from '../services/api';
import { Post } from '../types/post';
import { createRecommendedPosts } from '../components/RecommendedPosts';

export class LabelController {
    private labels: LabelData[];
    private activeLabel: LabelData | null = null;
    private defaultActiveLabel: LabelData | null = null;
    private labelsLinkElements: NodeListOf<Element>;

    constructor(
        private authorsController: AuthorsController,
        private blogPostInfinityScroll: BlogPostInfiniteScroll,
    ) {
        //@ts-expect-error TS doesn't know that window.labels was added in learn.html.twig
        this.labels = window.labels as LabelData[];

        const firstLabel = this.labels[0];
        if (firstLabel) {
            this.activeLabel = firstLabel;
            this.defaultActiveLabel = firstLabel;
        }
        this.labelsLinkElements = document.querySelectorAll(`.${pageName}__hero-label-link`);
    }

    public init(): void {
        this.setLabelsClickListener();
        this.setActiveLabelFromUrl();
        this.enableNavigateBackListener();
    }

    private onClickReadMoreLink(e: Event, href: string | null) {
        e.preventDefault();

        if (href) {
            this.activeLabel = null;
            this.updateActiveLabel();
            this.updateUrlPath(href);
            this.setDisplayedBlocks();
            this.fetchAndRenderPosts();
        }
    }

    public handleReadMoreClickEvents(e: Event): void {
        const target = e.target as HTMLElement;

        if (target.matches(`.${pageName}__read-more-link`)) {
            e.preventDefault();
            const href = target.getAttribute('href');
            this.onClickReadMoreLink(e, href);
        }
    }

    private updateUrlPath(newPath: string) {
        history.pushState(null, '', newPath);
    }

    private setLabelsClickListener() {
        this.labelsLinkElements.forEach((labelElement) => {
            labelElement.addEventListener('click', (e) => {
                e.preventDefault();

                const labelText = labelElement.textContent?.trim();
                if (labelText) {
                    this.activeLabel =
                        this.labels.find((label) => label.name === labelText) || (this.labels[0] as LabelData);
                    this.updateActiveLabel();
                    this.updateUrlPath(this.activeLabel.link);
                    this.setDisplayedBlocks();

                    if (this.activeLabel.name !== this.defaultActiveLabel?.name) {
                        this.fetchAndRenderPosts();
                    } else {
                        this.fetchAndRenderDefaultContent();
                    }
                }
            });
        });
    }

    private enableNavigateBackListener(): void {
        window.addEventListener('popstate', () => {
            this.setActiveLabelFromUrl();
        });
    }

    private updateActiveLabel(): void {
        this.labelsLinkElements.forEach((labelElement) => {
            const labelText = labelElement.textContent?.trim();
            labelElement.classList.remove(`${pageName}__hero-label-link--active`);
            if (this.activeLabel && labelText === this.activeLabel.name) {
                labelElement.classList.add(`${pageName}__hero-label-link--active`);
            }
        });
    }

    private setActiveLabelFromUrl(): void {
        const path = document.location.pathname;

        const matchingLabel = this.labels.find((label) => label.link === path);

        if (matchingLabel) {
            this.activeLabel = matchingLabel;
        } else {
            this.activeLabel = null;
        }

        this.updateActiveLabel();
        this.setDisplayedBlocks();
    }

    private setDisplayedBlocks() {
        const latestNewsSection = document.querySelector<HTMLElement>(`.${pageName}__latest-news`);
        const recommendedOfferSection = document.querySelector<HTMLElement>(`.${pageName}__recommended-offer`);

        if (this.activeLabel && this.activeLabel.name === this.defaultActiveLabel?.name) {
            if (latestNewsSection) {
                latestNewsSection.style.display = 'flex';
            }
            if (recommendedOfferSection) {
                recommendedOfferSection.style.display = 'flex';
            }
        } else {
            if (latestNewsSection) {
                latestNewsSection.style.display = 'none';
            }
            if (recommendedOfferSection) {
                recommendedOfferSection.style.display = 'none';
            }
        }
    }

    async fetchAndRenderDefaultContent(): Promise<void> {
        this.prepareForContentUpdate();

        const initialData = await this.fetchInitialData();
        if (!initialData) {
            this.hideContentLoader();
            return;
        }

        this.renderGroupsData(initialData.groups);
        this.renderRecommendedPosts(initialData.recommendedOffer);
        this.finishContentUpdate();
    }

    private prepareForContentUpdate(): void {
        this.blogPostInfinityScroll.disconnect();
        this.showContentLoader();
        this.clearContentElements();
    }

    private clearContentElements(): void {
        const contentCategoryTitleElements = document.querySelectorAll<HTMLElement>(
            `.${pageName}__category-title-block`,
        );
        const contentCategoryPostsElements = document.querySelectorAll<HTMLElement>(`.${pageName}__category-posts`);

        contentCategoryTitleElements.forEach((titleElement) => {
            if (!titleElement.classList.contains(`${pageName}__category-title-block--recommended-offer`)) {
                titleElement.remove();
            }
        });
        contentCategoryPostsElements.forEach((postsElement) => {
            if (!postsElement.classList.contains(`${pageName}__recommended-posts`)) {
                postsElement.remove();
            }
        });
    }

    private async fetchInitialData() {
        try {
            return await loadInitialData();
        } catch (error) {
            console.error('Failed to load initial data:', error);
            return null;
        }
    }

    private async renderGroupsData(groupsData: any[]) {
        const contentGridElement = document.querySelector(`.${pageName}__content-grid`);

        for (const group of groupsData) {
            const categoryBlock = await createCategoryBlock(group.posts, group.title, group.more, group.nextPage);
            contentGridElement?.append(categoryBlock);

            if (group.nextPage) {
                this.blogPostInfinityScroll.reset(group.nextPage);
            }
        }
    }

    private async renderRecommendedPosts(recommendedOffer: { title: string; posts: Post[] }) {
        const recommendedSection = document.querySelector('.page-learn__category-title-block--recommended-offer');

        if (recommendedSection !== null || recommendedOffer.posts.length === 0) {
            return;
        }

        const recommendedOfferContent = await createRecommendedPosts(recommendedOffer.posts, recommendedOffer.title);

        const recommendedPostsBlock = document.querySelector(`.${pageName}__recommended-offer`);

        recommendedPostsBlock?.append(recommendedOfferContent);
    }

    private finishContentUpdate(): void {
        this.authorsController.initAuthorsPopover();
        this.hideContentLoader();
    }

    async fetchAndRenderPosts(): Promise<void> {
        this.prepareForPostsUpdate();

        const postData = await this.fetchPosts();
        if (!postData) {
            this.hideContentLoader();
            return;
        }

        await this.renderPostsData(postData);
        this.setupInfiniteScroll(postData.nextPage);
    }

    private prepareForPostsUpdate(): void {
        this.blogPostInfinityScroll.disconnect();
        this.clearReadMoreLinks();
        this.clearContentElements();
        this.showContentLoader();
    }

    private clearReadMoreLinks(): void {
        const readMoreLinks = document.querySelectorAll<HTMLElement>(`.${pageName}__read-more-link`);
        readMoreLinks.forEach((readMoreElement) => {
            if (readMoreElement.classList.contains(`${pageName}__latest-news-link`)) {
                return;
            }
            readMoreElement.remove();
        });
    }

    private async fetchPosts() {
        try {
            return await loadPosts();
        } catch (error) {
            console.error('Failed to load posts:', error);
            return null;
        }
    }

    private async renderPostsData(postData: any) {
        const contentGridElement = document.querySelector(`.${pageName}__content-grid`);
        const categoryBlockElement = await createCategoryBlock(
            postData.posts,
            postData.title,
            undefined,
            postData.nextPage,
        );

        contentGridElement?.append(categoryBlockElement);
        this.authorsController.initAuthorsPopover();
    }

    private setupInfiniteScroll(nextPage: string | undefined): void {
        if (nextPage) {
            requestAnimationFrame(() => {
                this.blogPostInfinityScroll.reset(nextPage);
            });
        } else {
            this.blogPostInfinityScroll.disconnect();
        }

        this.hideContentLoader();
    }

    showContentLoader() {
        const loaderElement = document.querySelector<HTMLElement>(`.${pageName}__content-loader`);
        const observerTrigger = document.getElementById('infinity-observer-target');
        const infinityLoader = document.getElementById('infinity-loader');

        if (observerTrigger) {
            observerTrigger.remove();
        }
        if (infinityLoader) {
            infinityLoader.remove();
        }
        const contentWrapper = document.querySelector<HTMLElement>(`.${pageName}__content-wrapper`);
        if (contentWrapper) {
            contentWrapper.classList.add(`${pageName}__content-wrapper--hidden`);
            contentWrapper.classList.remove(`${pageName}__content-wrapper--shown`);
        }
        if (loaderElement) {
            loaderElement.style.display = 'flex';
        }
    }

    hideContentLoader() {
        const loaderElement = document.querySelector<HTMLElement>(`.${pageName}__content-loader`);
        const observerTrigger = document.getElementById('infinity-observer-target');

        if (observerTrigger) {
            observerTrigger.style.display = 'block';
        }

        if (loaderElement) {
            loaderElement.style.display = 'none';
        }
        const contentWrapper = document.querySelector<HTMLElement>(`.${pageName}__content-wrapper`);
        contentWrapper?.classList.toggle(`${pageName}__content-wrapper--shown`);
        contentWrapper?.classList.toggle(`${pageName}__content-wrapper--hidden`);
    }
}
