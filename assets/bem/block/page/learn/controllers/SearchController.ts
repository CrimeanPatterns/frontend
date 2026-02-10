import { liteClient as algoliasearch } from 'algoliasearch/lite';
import instantsearch, { Hit } from 'instantsearch.js';
import searchBox from 'instantsearch.js/es/widgets/search-box/search-box';
import stats from 'instantsearch.js/es/widgets/stats/stats';
import { connectInfiniteHits } from 'instantsearch.js/es/connectors';
import { ScrollMode, VirtualList } from '@Bem/ts/service/virtual-list';
import { bem } from '@Bem/ts/service/bem';
import { InsightsService } from '../services/InsightsService';

interface PostAuthor {
    avatar: string;
    link: string;
    name: string;
}

interface AlgoliaHit {
    id: string;
    title: string;
    authors: {
        list: PostAuthor[];
    };
    date: string;
    permalink: string;
    comments: number;
    content: string;
    thumbnail: string;
    [key: string]: any;
}

interface ContainerWithVirtualList extends HTMLElement {
    _virtualList?: VirtualList<AlgoliaHit>;
}

export class SearchController {
    private search: ReturnType<typeof instantsearch>;
    private isSearchQueryChanged = false;
    private indexName;
    private insightsService?: InsightsService;

    constructor() {
        // @ts-expect-error The value is set in learn.twig
        const searchClient = algoliasearch(window.apiSearch.appId, window.apiSearch.apiKey);
        // @ts-expect-error The value is set in learn.twig
        const indexName = window.apiSearch.indexName;
        this.indexName = indexName;
        const that = this;

        this.insightsService = InsightsService.getInstance();
        this.insightsService.initialize();
        const insightsClient = this.insightsService?.getClient();

        this.search = instantsearch({
            indexName,
            searchClient,
            future: {
                preserveSharedStateOnUnmount: true,
            },
            routing: {
                stateMapping: {
                    stateToRoute(uiState) {
                        const indexUiState = uiState[indexName];
                        return {
                            s: indexUiState!.query,
                        };
                    },
                    routeToState(routeState) {
                        return {
                            [indexName]: {
                                query: routeState.s,
                            },
                        };
                    },
                },
            },
            onStateChange(params) {
                if (params.uiState[that.indexName]?.page === undefined) {
                    that.isSearchQueryChanged = true;
                }
                params.setUiState(params.uiState);
            },
            insights: {
                insightsClient,
            },
        });
    }

    private renderHitItem(hit: AlgoliaHit): string {
        const dateObj = new Date(hit.date);

        const formatter = new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });

        const formattedDate = formatter.format(dateObj);
        return `<div class="${bem('search-section', 'result-item')}" data-post-id="${hit.id}">
            <a href="${hit.permalink}" class="${bem('search-section', 'result-link')}"></a>
            <img src="${hit.thumbnail}" alt="${hit.title}" class="${bem('search-section', 'result-img')}" loading="lazy" />
        
            <h3 class="${bem('search-section', 'result-title')}">
                ${instantsearch.highlight({ attribute: 'title', hit })}
            </h3>
        
            <div class="${bem('search-section', 'result-authors')}">
                <div class="${bem('search-section', 'result-authors-img-block')}">
                    ${hit.authors.list.map((author, index) => {
                        return `<span style="z-index: ${hit.authors.list.length - index};" class="${bem('search-section', 'result-authors-img-wrapper')}"> 
                                <img
                                    class="${bem('search-section', 'result-authors-img')}"
                                    src="${author.avatar}"
                                    alt="${author.name}"
                                    loading="lazy"
                                />
                            </span>`;
                    })}
                </div>
                <div class="${bem('search-section', 'result-author-names-wrapper')}">
                    <div class="${bem('search-section', 'result-author-names')}" data-authors="${JSON.stringify(hit.authors.list)}">
                        ${hit.authors.list
                            .map((author) => {
                                return `<a href="${author.link}" class="${bem('search-section', 'result-author-link')}">
                                ${author.name}
                             </a>`;
                            })
                            .join(' ')}
                    </div>
                </div>
            </div>
        
            <div class="${bem('search-section', 'result-meta-info')}">
                <div class="${bem('search-section', 'result-release-date')}">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <mask id="mask0_20584_2"  style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="16" height="16">
                            <rect width="16" height="16" fill="#D9D9D9"/>
                        </mask>
                        <g mask="url(#mask0_20584_2)">
                            <g opacity="0.4">
                                <path d="M1.375 1.375H14.625V4H1.375V1.375Z" fill="#272F3F"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.375 5.375H14.625V14.625H1.375V5.375ZM13.375 10.75V9.25H10.625V10.75H13.375ZM2.625 9.25V10.75H5.375V9.25H2.625ZM13.375 13.375V11.875H10.625V13.375H13.375ZM2.625 11.875V13.375H5.375V11.875H2.625ZM13.375 8.125V6.625H10.625V8.125H13.375ZM2.625 6.625V8.125H5.375V6.625H2.625ZM6.625 6.625V8.125H9.375V6.625H6.625ZM9.375 10.75V9.25H6.625V10.75H9.375ZM6.625 11.875V13.375H9.375V11.875H6.625Z" fill="#272F3F"/>
                            </g>
                        </g>
                    </svg>
                    ${formattedDate}
                </div>
                <div class="${bem('search-section', 'result-comments')}">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g opacity="0.4">
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M1.63086 1.63086V11.5726H3.68911V14.4464L7.72795 11.5726H14.3687V1.63086H1.63086ZM3.18426 10.0192V3.18426H12.8153V10.0192H7.51173L5.16484 11.5726V10.0192H3.18426Z"
                                fill="#272F3F"
                            />
                        </g>
                    </svg>
                    ${hit.comments}
                </div>
                <div class="${bem('search-section', 'result-social-links')}">
                    <a
                        href="https://facebook.com/sharer/sharer.php?u=${encodeURIComponent(hit.permalink)}"
                        class="${bem('search-section', 'result-social-link')}"
                        target="_blank"
                        rel="noopener"
                    >
                        <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.6832 6.0002H9.33317V4.66687C9.33317 3.97887 9.38917 3.54554 10.3752 3.54554H11.6205V1.42554C11.0145 1.36287 10.4052 1.3322 9.79517 1.33354C7.9865 1.33354 6.6665 2.4382 6.6665 4.4662V6.0002H4.6665V8.66687L6.6665 8.6662V14.6669H9.33317V8.66487L11.3772 8.66421L11.6832 6.0002Z" />
                        </svg>
                    </a>
                    
                    <a    href="https://twitter.com/intent/tweet?url=${encodeURIComponent(hit.permalink)}"
                        class="${bem('search-section', 'result-social-link')}"
                        target="_blank"
                        rel="noopener"
                    >
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M11.5237 8.77566L17.4811 2H16.0699L10.8949 7.88201L6.7648 2H2L8.24693 10.8955L2 17.9999H3.4112L8.87253 11.787L13.2352 17.9999H18M3.92053 3.04126H6.08853L16.0688 17.0098H13.9003"
                                fill="#272F3F"
                            />
                        </svg>
                    </a>
                </div>
            </div>
            <div class="${bem('search-section', 'result-content-wrapper')}">
                <p class="${bem('search-section', 'result-content')}">
                    ${instantsearch.highlight({ attribute: 'content', hit })}
                </p>
            </div>
        </div>`;
    }

    private createInfiniteHitsWidget() {
        const that = this;

        return connectInfiniteHits<{ container: HTMLElement }>((renderArgs, isFirstRender) => {
            const { hits, showMore, widgetParams, isLastPage, instantSearchInstance, sendEvent } = renderArgs;
            const { container } = widgetParams;

            if (isFirstRender) {
                return;
            }

            container.setAttribute('data-is-last-page', String(isLastPage));

            const renderNoResults = (): string => {
                return `
                    <div class="${bem('search-section', 'no-results')}">
                        <p class="${bem('search-section', 'no-results-text')}">
                            No Results Found
                        </p>
                    </div>
                `;
            };

            const containerWithVL = container as ContainerWithVirtualList;
            if (containerWithVL._virtualList) {
                if (instantSearchInstance.status === 'idle') {
                    containerWithVL._virtualList.setLoading(false);

                    if (that.isSearchQueryChanged) {
                        that.isSearchQueryChanged = false;
                        containerWithVL._virtualList.updateItems(hits as unknown as AlgoliaHit[]);
                    } else if (hits.length > containerWithVL._virtualList.getItemsCount()) {
                        const existingCount = containerWithVL._virtualList.getItemsCount();
                        const newItems = hits.slice(existingCount) as unknown as AlgoliaHit[];
                        containerWithVL._virtualList.appendItems(newItems);
                    }
                }
            } else if (hits.length > 0) {
                const virtualList = new VirtualList<AlgoliaHit>({
                    container,
                    items: hits as unknown as AlgoliaHit[],
                    renderItem: that.renderHitItem,
                    loadMore: async (): Promise<void> => {
                        if (container.getAttribute('data-is-last-page') !== 'true') {
                            showMore();
                        }
                        return Promise.resolve();
                    },
                    onItemClick: (item: AlgoliaHit): void => {
                        sendEvent('click', item as unknown as Hit, 'Hit Clicker');
                        window.location.href = item.permalink;
                    },
                    scrollMode: ScrollMode.WINDOW,
                    estimatedItemHeight: 300,
                    buffer: 3,
                    renderNoResults,
                    showNoResults: true,
                    showLoading: false,
                });

                (container as ContainerWithVirtualList)._virtualList = virtualList;
            }
        });
    }

    public init() {
        const infiniteHits = this.createInfiniteHitsWidget();

        this.search.addWidgets([
            searchBox({
                container: '#searchbox',
                placeholder: 'Search',
                cssClasses: {
                    form: bem('search-section', 'form'),
                    input: bem('search-section', 'input'),
                    reset: bem('search-section', 'reset-button'),
                    resetIcon: bem('search-section', 'reset-icon'),
                    submit: bem('search-section', 'submit-button'),
                    submitIcon: bem('search-section', 'submit-icon'),
                },
                showLoadingIndicator: false,
            }),
            stats({
                container: '#stats',
                cssClasses: {
                    text: bem('search-section', 'stats-text'),
                },
                templates: {
                    text(data) {
                        let count = '';

                        if (data.hasManyResults) {
                            count += `${data.nbHits} results`;
                        } else if (data.hasOneResult) {
                            count += `1 result`;
                        }

                        if (count.length === 0) return '';

                        return `${count} found`;
                    },
                },
            }),
            infiniteHits({
                container: document.querySelector('#hits')!,
            }),
        ]);

        this.search.start();
        this.setupStickyBackground();
        this.setupLoaderHandling();
        this.setupScrollToTop();
        this.setupLinkClickHandling();
    }

    private setupLoaderHandling() {
        this.search.on('render', () => {
            const loaderElement = document.querySelector<HTMLDivElement>(`.${bem('page-learn', 'content-loader')}`);

            if (this.search.status === 'stalled' || this.search.status === 'loading') {
                if (loaderElement) {
                    loaderElement.style.display = 'flex';
                }
            } else {
                if (loaderElement) {
                    loaderElement.style.display = 'none';
                }
            }
        });
    }

    private setupStickyBackground() {
        const searchWrapper = document.querySelector(`.${bem('search-section', 'search-wrapper')}`);

        if (!searchWrapper) {
            return;
        }

        window.addEventListener('scroll', () => {
            const scrollPosition = window.scrollY;

            if (scrollPosition >= 45) {
                searchWrapper.classList.add('search-section__search-wrapper--bg');
            } else {
                searchWrapper.classList.remove('search-section__search-wrapper--bg');
            }
        });
    }

    private setupScrollToTop() {
        let previousQuery = '';

        this.search.on('render', () => {
            const currentQuery = this.search.helper?.state.query || '';

            if (currentQuery !== previousQuery) {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth',
                });

                previousQuery = currentQuery;
            }
        });
    }

    private setupLinkClickHandling(): void {
        document.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;
            const link = target.closest(`.${bem('search-section', 'result-link')}`) as HTMLAnchorElement;

            if (link) {
                e.preventDefault();

                const resultItem = link.closest(`.${bem('search-section', 'result-item')}`) as HTMLElement;
                if (resultItem) {
                    const syntheticEvent = new MouseEvent('click', {
                        bubbles: true,
                        cancelable: true,
                        view: window,
                    });
                    resultItem.dispatchEvent(syntheticEvent);
                }
            }
        });
    }
}
