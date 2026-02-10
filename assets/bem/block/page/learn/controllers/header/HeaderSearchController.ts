import { bem } from '@Bem/ts/service/bem';
import { autocomplete, getAlgoliaResults } from '@algolia/autocomplete-js';
import { LiteClient, liteClient as algoliasearch } from 'algoliasearch/lite';
import { pageName } from '../../consts';
import { Router } from '@Services/Router';
import { InsightsService } from '../../services/InsightsService';

interface PostItem {
    title: string;
    thumbnail: string;
    permalink: string;
    [key: string]: unknown;
}

export class HeaderSearchController {
    private indexName: string | null = null;
    private searchClient: LiteClient | null = null;
    private searchContainerElement: HTMLDivElement | null = null;
    private insightsService?: InsightsService;

    constructor() {
        //@ts-expect-error Provided in learn.twig file
        if (!window.apiSearch || !window.apiSearch.apiKey || !window.apiSearch.appId || !window.apiSearch.indexName) {
            console.warn("Search hasn't initialized correctly");
            return;
        }

        this.searchContainerElement = document.querySelector(`.${bem(pageName, 'header-search-container')}`);

        //@ts-expect-error Checked that these values exist before
        this.searchClient = algoliasearch(window.apiSearch.appId, window.apiSearch.apiKey);
        //@ts-expect-error Checked that these values exist before
        this.indexName = window.apiSearch.indexName as string;

        this.insightsService = InsightsService.getInstance();
        this.insightsService.initialize();

        this.setUpFixedPanelPosition();
        this.setupCustomEnterKeyHandler();
        this.initAlgoliaSearch();
    }

    private initAlgoliaSearch() {
        if (this.searchContainerElement && this.indexName) {
            const insightsClient = this.insightsService?.getClient();

            autocomplete<PostItem>({
                container: this.searchContainerElement,
                detachedMediaQuery: 'none',
                autoFocus: false,
                defaultActiveItemId: 0,
                panelPlacement: 'start',
                classNames: {
                    root: bem(pageName, 'search-container'),
                    form: bem(pageName, 'search-form'),
                    input: bem(pageName, 'search-input'),
                    submitButton: bem(pageName, 'search-submit-button'),
                    panel: bem(pageName, 'search-panel'),
                    list: bem(pageName, 'search-list'),
                    item: bem(pageName, 'search-item'),
                    inputWrapperPrefix: bem(pageName, 'search-prefix'),
                    inputWrapperSuffix: bem(pageName, 'search-suffix'),
                    clearButton: bem(pageName, 'search-clear-button'),
                    loadingIndicator: bem(pageName, 'search-loading-indicator'),
                },
                placeholder: 'Search',
                onSubmit: ({ state }) => {
                    const searchQuery = state.query.trim();
                    if (searchQuery) {
                        window.location.href = Router.generate('aw_blog_learn_search', {
                            s: encodeURIComponent(searchQuery),
                        });
                    }
                },
                getSources: ({ query }) => {
                    const searchClient = this.searchClient;
                    const indexName = this.indexName;

                    return [
                        {
                            sourceId: 'posts',
                            getItemInputValue: ({ item }) => item.title,
                            getItems() {
                                if (!searchClient) {
                                    console.warn("SearchClient isn't found");
                                    return Promise.resolve([]);
                                }
                                return getAlgoliaResults<PostItem>({
                                    searchClient,
                                    queries: [
                                        {
                                            indexName: indexName!,
                                            params: {
                                                query,
                                                hitsPerPage: 50,
                                            },
                                        },
                                    ],
                                });
                            },
                            templates: {
                                noResults({ html }) {
                                    return html`
                                        <div class="${bem(pageName, 'search-no-results')}">No results found</div>
                                    `;
                                },
                                item({ item, html, components }) {
                                    return html`
                                        <a href="${item.permalink || '#'}" class="${bem(pageName, 'search-item-link')}">
                                            ${html`<img
                                                class="${bem(pageName, 'search-item-image')}"
                                                src="${item.thumbnail}"
                                                alt="${item.title}"
                                            />`}

                                            <h4 class="${bem(pageName, 'search-item-title')}">
                                                ${components.Highlight({
                                                    hit: item,
                                                    attribute: 'title',
                                                    tagName: 'mark',
                                                })}
                                            </h4>
                                        </a>
                                    `;
                                },
                            },
                        },
                    ];
                },
                ...(insightsClient && {
                    insights: {
                        insightsClient: insightsClient,
                    },
                }),
            });
        }
    }

    private setUpFixedPanelPosition() {
        const fixPosition = () => {
            const rect = document.querySelector('.page-learn__header-search-container')!.getBoundingClientRect();

            let topPosition = rect.bottom;
            document.documentElement.style.setProperty('--position-autocomplete-panel-top', `${topPosition}px`);
        };

        setTimeout(() => {
            document.querySelector('.page-learn__search-input')!.addEventListener('focus', fixPosition);
            document.querySelector('.page-learn__search-input')!.addEventListener('blur', fixPosition);
        }, 0);
    }

    private setupCustomEnterKeyHandler() {
        setTimeout(() => {
            const form = document.querySelector(`.${bem(pageName, 'search-form')}`) as HTMLFormElement;
            const input = document.querySelector(`.${bem(pageName, 'search-input')}`) as HTMLInputElement;

            if (form && input) {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    const searchQuery = input.value.trim();
                    if (searchQuery) {
                        window.location.href = Router.generate('aw_blog_learn_search', {
                            s: encodeURIComponent(searchQuery),
                        });
                    }
                });

                input.addEventListener(
                    'keydown',
                    (event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            event.stopPropagation();

                            const searchQuery = input.value.trim();
                            if (searchQuery) {
                                window.location.href = Router.generate('aw_blog_learn_search', {
                                    s: encodeURIComponent(searchQuery),
                                });
                            }
                        }
                    },
                    true,
                );
            }
        }, 0);
    }
}
