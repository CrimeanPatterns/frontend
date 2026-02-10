export enum ScrollMode {
    CONTAINER = 'container',
    WINDOW = 'window',
}

export interface VirtualListOptions<T> {
    container: HTMLElement;
    items?: T[];
    renderItem: (item: T, index: number) => string;
    loadMore?: () => Promise<void>;
    buffer?: number;
    onItemClick?: (item: T, index: number) => void;
    scrollMode?: ScrollMode;
    estimatedItemHeight?: number;
    renderNoResults?: () => string;
    showNoResults?: boolean;
    renderLoading?: () => string;
    showLoading?: boolean;
}

export class VirtualList<T = any> {
    private container: HTMLElement;
    private itemsData: T[];
    private renderItem: (item: T, index: number) => string;
    private loadMoreCallback?: () => Promise<void>;
    private options: VirtualListOptions<T>;
    private buffer: number;
    private estimatedItemHeight: number;
    private isLoading: boolean = false;
    private scrollMode: ScrollMode;

    private showNoResults: boolean;
    private showLoading: boolean;
    private renderNoResults?: () => string;
    private renderLoading?: () => string;
    private noResultsElement: HTMLElement | null = null;
    private loadingElement: HTMLElement | null = null;
    private previousContainerHeight: number = 0;
    private heightChangedAfterLoad: boolean = false;
    private scheduledUpdate: boolean = false;

    private runway!: HTMLElement;
    private sentinel!: HTMLElement;
    private bottomUIContainer!: HTMLElement;
    private uiElementsContainer!: HTMLElement;

    private resizeObserver!: ResizeObserver;
    private infiniteObserver!: IntersectionObserver;

    private visibleItems: Map<number, HTMLElement> = new Map();
    private itemHeights: Map<number, number> = new Map();
    private itemOffsets: number[] = [];
    private totalHeight: number = 0;
    private viewportHeight: number = 0;
    private scrollTop: number = 0;
    private containerTop: number = 0;

    private _eventHandlers: {
        scrollHandler: (e: Event) => void;
        resizeHandler: (e: Event) => void;
    } | null = null;

    constructor(options: VirtualListOptions<T>) {
        this.container = options.container;
        this.itemsData = options.items || [];
        this.renderItem = options.renderItem;
        this.loadMoreCallback = options.loadMore;
        this.buffer = options.buffer || 5;
        this.estimatedItemHeight = options.estimatedItemHeight || 200;
        this.options = options;

        this.showNoResults = options.showNoResults !== undefined ? options.showNoResults : true;
        this.showLoading = options.showLoading !== undefined ? options.showLoading : true;
        this.renderNoResults = options.renderNoResults;
        this.renderLoading = options.renderLoading;

        this.scrollMode = options.scrollMode || ScrollMode.CONTAINER;

        this.setupDOM();

        if (this.scrollMode === ScrollMode.WINDOW) {
            this.updateContainerPosition();
        }

        this.setupObservers();

        this.updateViewportDimensions();
        this.calculateLayout();
        this.render();

        this.setupEventListeners();
    }

    private setupEventListeners(): void {
        const scrollHandler = this.handleScroll.bind(this);
        const resizeHandler = this.handleResize.bind(this);

        if (this.scrollMode === ScrollMode.WINDOW) {
            window.addEventListener('scroll', scrollHandler);
            window.addEventListener('resize', resizeHandler);
        } else {
            this.container.addEventListener('scroll', scrollHandler);
            window.addEventListener('resize', resizeHandler);
        }

        this._eventHandlers = {
            scrollHandler,
            resizeHandler,
        };
    }

    private updateContainerPosition(): void {
        const rect = this.container.getBoundingClientRect();
        this.containerTop = rect.top + window.scrollY;
    }

    private setupDOM(): void {
        this.container.innerHTML = '';

        if (this.scrollMode === ScrollMode.CONTAINER) {
            this.container.style.overflow = 'auto';
            this.container.style.position = 'relative';
        } else {
            this.container.style.position = 'relative';
        }

        this.uiElementsContainer = document.createElement('div');
        this.uiElementsContainer.style.width = '100%';
        this.container.appendChild(this.uiElementsContainer);

        if (this.renderNoResults) {
            this.noResultsElement = document.createElement('div');
            this.noResultsElement.style.width = '100%';
            this.uiElementsContainer.appendChild(this.noResultsElement);
        }

        this.runway = document.createElement('div');
        this.runway.style.position = 'relative';
        this.runway.style.width = '100%';
        this.runway.style.height = '0px';
        this.container.appendChild(this.runway);

        this.bottomUIContainer = document.createElement('div');
        this.bottomUIContainer.style.width = '100%';
        this.container.appendChild(this.bottomUIContainer);

        if (this.renderLoading) {
            this.loadingElement = document.createElement('div');
            this.loadingElement.style.width = '100%';
            this.bottomUIContainer.appendChild(this.loadingElement);
        }

        this.sentinel = document.createElement('div');
        this.sentinel.style.width = '100%';
        this.sentinel.style.height = '10px';
        this.sentinel.setAttribute('id', 'sentinel');
        this.bottomUIContainer.appendChild(this.sentinel);

        this.setupItemClickHandling();

        this.updateUI();
    }

    private updateUI(): void {
        if (this.noResultsElement && this.renderNoResults) {
            const showNoResults = this.showNoResults && this.itemsData.length === 0 && !this.isLoading;
            this.noResultsElement.style.display = showNoResults ? 'block' : 'none';
            if (showNoResults) {
                this.noResultsElement.innerHTML = this.renderNoResults();
            }
        }

        if (this.loadingElement && this.renderLoading) {
            this.loadingElement.style.display = this.showLoading && this.isLoading ? 'block' : 'none';
            if (this.isLoading && this.showLoading) {
                this.loadingElement.innerHTML = this.renderLoading();
            }
        }

        this.sentinel.style.display = this.isLoading ? 'none' : 'block';
    }

    private setupObservers(): void {
        this.resizeObserver = new ResizeObserver(this.handleItemResize.bind(this));

        const infiniteObserverOptions =
            this.scrollMode === ScrollMode.CONTAINER
                ? {
                      root: this.container,
                      threshold: 0.1,
                  }
                : {
                      threshold: 0.1,
                  };

        this.infiniteObserver = new IntersectionObserver(this.handleInfiniteScroll.bind(this), infiniteObserverOptions);

        this.observeSentinel();
    }

    private observeSentinel(): void {
        if (!this.loadMoreCallback || this.isLoading || !this.heightChangedAfterLoad) {
            return;
        }

        this.infiniteObserver.observe(this.sentinel);
    }

    private unobserveSentinel(): void {
        this.infiniteObserver.unobserve(this.sentinel);
    }

    private updateViewportDimensions(): void {
        if (this.scrollMode === ScrollMode.CONTAINER) {
            this.viewportHeight = this.container.clientHeight;
            this.scrollTop = this.container.scrollTop;
        } else {
            this.viewportHeight = window.innerHeight;
            this.scrollTop = window.scrollY - this.containerTop;

            this.updateContainerPosition();
        }
    }

    private calculateLayout(): void {
        this.previousContainerHeight = this.totalHeight;

        let offset = 0;
        this.itemOffsets = [];

        if (this.itemsData.length === 0) {
            this.totalHeight = 0;
            this.runway.style.height = '0px';
            return;
        }

        for (let i = 0; i < this.itemsData.length; i++) {
            const height = this.itemHeights.get(i) || this.estimatedItemHeight;
            this.itemOffsets[i] = offset;
            offset += height;
        }

        this.totalHeight = offset;
        this.runway.style.height = `${this.totalHeight}px`;

        this.heightChangedAfterLoad = this.totalHeight !== this.previousContainerHeight;
    }

    private getVisibleRange(): { start: number; end: number } {
        if (this.itemsData.length === 0) {
            return { start: 0, end: -1 };
        }

        if (this.itemOffsets.length === 0) {
            return { start: 0, end: Math.min(20, this.itemsData.length - 1) };
        }

        let start = this.binarySearchForIndex(this.scrollTop - this.viewportHeight * 0.5);
        start = Math.max(0, start - this.buffer);

        let end = this.binarySearchForIndex(this.scrollTop + this.viewportHeight * 1.5);
        end = Math.min(this.itemsData.length - 1, end + this.buffer);

        return { start, end };
    }

    private binarySearchForIndex(offset: number): number {
        let low = 0;
        let high = this.itemOffsets.length - 1;
        let mid = 0;

        if (offset <= this.itemOffsets[0]! || this.itemOffsets.length === 0) {
            return 0;
        }

        if (offset >= this.itemOffsets[high]!) {
            return high;
        }

        while (low <= high) {
            mid = Math.floor((low + high) / 2);

            if (
                this.itemOffsets[mid]! <= offset &&
                (mid === this.itemOffsets.length - 1 || this.itemOffsets[mid + 1]! > offset)
            ) {
                return mid;
            } else if (this.itemOffsets[mid]! < offset) {
                low = mid + 1;
            } else {
                high = mid - 1;
            }
        }

        return mid;
    }

    private render(): void {
        const { start, end } = this.getVisibleRange();
        const currentlyVisible = new Set<number>();

        for (let i = start; i <= end; i++) {
            currentlyVisible.add(i);

            if (!this.visibleItems.has(i)) {
                const item = this.createItem(i);
                this.runway.appendChild(item);
                this.visibleItems.set(i, item);

                this.resizeObserver.observe(item);
            }
        }

        for (const [index, element] of this.visibleItems.entries()) {
            if (!currentlyVisible.has(index)) {
                this.resizeObserver.unobserve(element);
                this.runway.removeChild(element);
                this.visibleItems.delete(index);
            }
        }

        if (this.visibleItems.size > 0 && !this.isLoading && this.heightChangedAfterLoad) {
            setTimeout(() => {
                if (!this.isLoading) {
                    this.observeSentinel();
                }
            }, 100);
        } else {
            this.unobserveSentinel();
        }
        this.updateUI();
    }

    private createItem(index: number): HTMLElement {
        const data = this.itemsData[index];
        const element = document.createElement('div');
        element.setAttribute('data-index', index.toString());
        element.style.position = 'absolute';
        element.style.top = `${this.itemOffsets[index]}px`;
        element.style.width = '100%';
        element.style.left = '0';

        element.innerHTML = this.renderItem(data!, index);

        return element;
    }

    private setupItemClickHandling(): void {
        this.runway.addEventListener('click', (e: MouseEvent) => {
            const target = e.target as HTMLElement;
            const itemElement = target.closest('[data-index]') as HTMLElement;
            if (!itemElement) return;

            if (
                target.tagName === 'A' ||
                target.tagName === 'BUTTON' ||
                target.closest('a') ||
                target.closest('button')
            ) {
                return;
            }

            const index = parseInt(itemElement.getAttribute('data-index') || '0', 10);
            if (this.options?.onItemClick && index >= 0 && index < this.itemsData.length && this.itemsData[index]) {
                this.options.onItemClick(this.itemsData[index]!, index);
            }
        });
    }

    private handleScroll(): void {
        if (!this.scheduledUpdate) {
            this.scheduledUpdate = true;
            requestAnimationFrame(() => {
                this.updateViewportDimensions();
                this.render();
                this.scheduledUpdate = false;
            });
        }
    }

    private handleResize(): void {
        this.updateViewportDimensions();
        this.render();
    }

    private handleInfiniteScroll(entries: IntersectionObserverEntry[]): void {
        if (this.isLoading) {
            return;
        }

        let shouldLoad = false;
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                shouldLoad = true;
            }
        });

        if (shouldLoad && this.loadMoreCallback) {
            this.isLoading = true;

            this.unobserveSentinel();

            this.updateUI();

            this.loadMoreCallback();
        }
    }

    private handleItemResize(entries: ResizeObserverEntry[]): void {
        let needsLayout = false;

        entries.forEach((entry) => {
            const element = entry.target as HTMLElement;
            const index = parseInt(element.getAttribute('data-index') || '0', 10);
            const newHeight = entry.contentRect.height;
            const oldHeight = this.itemHeights.get(index) || 0;

            if (Math.abs(newHeight - oldHeight) > 1) {
                this.itemHeights.set(index, newHeight);
                needsLayout = true;
            }
        });

        if (needsLayout) {
            this.calculateLayout();

            for (const [index, element] of this.visibleItems.entries()) {
                element.style.top = `${this.itemOffsets[index]}px`;
            }
        }
    }

    public updateItems(newItems: T[]): void {
        for (const [index, element] of this.visibleItems.entries()) {
            this.resizeObserver.unobserve(element);
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }
        this.visibleItems.clear();

        this.itemsData = newItems;
        this.calculateLayout();
        this.updateUI();
        this.render();
    }

    public appendItems(moreItems: T[]): void {
        if (!moreItems || moreItems.length === 0) {
            this.heightChangedAfterLoad = false;
            return;
        }

        this.itemsData = [...this.itemsData, ...moreItems];
        this.calculateLayout();

        this.updateUI();
        this.render();
    }

    public refresh(): void {
        this.calculateLayout();
        this.updateUI();
        this.render();
    }

    public scrollToItem(index: number, options: ScrollToOptions = { behavior: 'auto' }): void {
        if (index >= 0 && index < this.itemsData.length) {
            if (this.scrollMode === ScrollMode.CONTAINER) {
                this.container.scrollTo({
                    top: this.itemOffsets[index],
                    behavior: options.behavior,
                });
            } else {
                const scrollTargetY = this.itemOffsets[index]! + this.containerTop;
                window.scrollTo({
                    top: scrollTargetY,
                    behavior: options.behavior,
                });
            }
        }
    }

    public destroy(): void {
        if (this._eventHandlers) {
            if (this.scrollMode === ScrollMode.WINDOW) {
                window.removeEventListener('scroll', this._eventHandlers.scrollHandler);
            } else {
                this.container.removeEventListener('scroll', this._eventHandlers.scrollHandler);
            }
            window.removeEventListener('resize', this._eventHandlers.resizeHandler);
        }

        this.resizeObserver.disconnect();
        this.infiniteObserver.disconnect();

        this.visibleItems.clear();
        this.itemHeights.clear();
        this.container.innerHTML = '';

        this._eventHandlers = null;
    }

    public setLoading(loading: boolean): void {
        this.isLoading = loading;
        this.updateUI();
        if (loading) {
            this.unobserveSentinel();
        } else {
            this.observeSentinel();
        }
    }

    public updateUISettings(settings: {
        showNoResults?: boolean;
        showLoading?: boolean;
        renderNoResults?: () => string;
        renderLoading?: () => string;
    }): void {
        if (settings.showNoResults !== undefined) {
            this.showNoResults = settings.showNoResults;
        }

        if (settings.showLoading !== undefined) {
            this.showLoading = settings.showLoading;
        }

        if (settings.renderNoResults) {
            this.renderNoResults = settings.renderNoResults;
            if (!this.noResultsElement) {
                this.noResultsElement = document.createElement('div');
                this.noResultsElement.style.width = '100%';
                this.container.appendChild(this.noResultsElement);
            }
        }

        if (settings.renderLoading) {
            this.renderLoading = settings.renderLoading;
            if (!this.loadingElement) {
                this.loadingElement = document.createElement('div');
                this.loadingElement.style.width = '100%';
                this.container.appendChild(this.loadingElement);
            }
        }

        this.updateUI();
    }

    public getItemsCount(): number {
        return this.itemsData.length;
    }
}
