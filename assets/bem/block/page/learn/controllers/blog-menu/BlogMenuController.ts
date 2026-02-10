import './blog-menu.scss';

import { BackgroundLayoutController } from '@Bem/block/bg-layout';

export const CSS_CLASSES = {
    MENU: {
        CONTAINER: 'blog-menu__mobile-menu',
        CONTAINER_SHOWN: 'blog-menu__mobile-menu--shown',
        MAIN: 'blog-menu__mobile-menu-main',
        MAIN_HIDDEN: 'blog-menu__mobile-menu-main--hidden',
        CATEGORY_MENU: 'blog-menu__mobile-category-menu',
        CATEGORY_MENU_SHOWN: 'blog-menu__mobile-category-menu--shown',
        BACK_BUTTON: 'blog-menu__mobile-category-back-button',
        MENU_ITEM: 'blog-menu__item',
        MENU_ITEM_ACTIVE: 'blog-menu__item--active',
        DROPDOWN: 'blog-menu__dropdown',
    },
    HEADER: {
        MENU_BUTTON: 'page-learn__header-menu-button',
        MENU_BUTTON_ACTIVE: 'page-learn__header-menu-button--active',
        BG_LAYOUT: 'page-learn__header-bg-layout',
    },
};

interface BlogMenuSelectors {
    menuButtonSelector?: string;
    menuContainerSelector?: string;
    menuItemSelector?: string;
    mainMenuSelector?: string;
    backButtonSelector?: string;
    bgLayoutSelector?: string;
    categoryMenuSelector?: string;
    menuButtonActiveClass?: string;
    menuContainerShownClass?: string;
    mainMenuHiddenClass?: string;
    categoryMenuShownClass?: string;
}

interface BlogMenuOptions {
    onOpen?: () => void;
    onClose?: () => void;
}

interface BlogMenuDependencies {
    backgroundLayoutController?: BackgroundLayoutController;
}

type BlogMenuView = 'main' | 'subcategory';

export class BlogMenuController {
    private menuButton: HTMLButtonElement | null = null;
    private menuContainer: HTMLElement | null = null;
    private mainMenuElement: HTMLElement | null = null;
    private backButtons: HTMLButtonElement[] = [];

    private isMenuOpen: boolean = false;
    private currentView: BlogMenuView = 'main';
    private options: BlogMenuOptions | null = null;

    private backgroundLayoutController: BackgroundLayoutController;

    private boundToggleMenu: () => void;
    private boundHideMenu: () => void;
    private boundNavigateBack: () => void;

    private menuItemHandlers: Map<HTMLElement, (event: Event) => void> = new Map();

    private menuButtonSelector: string = `.${CSS_CLASSES.HEADER.MENU_BUTTON}`;
    private menuContainerSelector: string = `.${CSS_CLASSES.MENU.CONTAINER}`;
    private menuItemSelector: string = '[data-category-id]';
    private mainMenuSelector: string = `.${CSS_CLASSES.MENU.MAIN}`;
    private backButtonSelector: string = `.${CSS_CLASSES.MENU.BACK_BUTTON}`;
    private bgLayoutSelector: string = `.${CSS_CLASSES.HEADER.BG_LAYOUT}`;
    private categoryMenuSelector: string = `.${CSS_CLASSES.MENU.CATEGORY_MENU}`;

    private menuButtonActiveClass: string = CSS_CLASSES.HEADER.MENU_BUTTON_ACTIVE;
    private menuContainerShownClass: string = CSS_CLASSES.MENU.CONTAINER_SHOWN;
    private mainMenuHiddenClass: string = CSS_CLASSES.MENU.MAIN_HIDDEN;
    private categoryMenuShownClass: string = CSS_CLASSES.MENU.CATEGORY_MENU_SHOWN;

    constructor(selectors?: BlogMenuSelectors, options?: BlogMenuOptions, dependencies?: BlogMenuDependencies) {
        this.menuButtonSelector = selectors?.menuButtonSelector ?? this.menuButtonSelector;
        this.menuContainerSelector = selectors?.menuContainerSelector ?? this.menuContainerSelector;
        this.menuItemSelector = selectors?.menuItemSelector ?? this.menuItemSelector;
        this.mainMenuSelector = selectors?.mainMenuSelector ?? this.mainMenuSelector;
        this.backButtonSelector = selectors?.backButtonSelector ?? this.backButtonSelector;
        this.bgLayoutSelector = selectors?.bgLayoutSelector ?? this.bgLayoutSelector;
        this.categoryMenuSelector = selectors?.categoryMenuSelector ?? this.categoryMenuSelector;

        this.menuButtonActiveClass = selectors?.menuButtonActiveClass ?? this.menuButtonActiveClass;
        this.menuContainerShownClass = selectors?.menuContainerShownClass ?? this.menuContainerShownClass;
        this.mainMenuHiddenClass = selectors?.mainMenuHiddenClass ?? this.mainMenuHiddenClass;
        this.categoryMenuShownClass = selectors?.categoryMenuShownClass ?? this.categoryMenuShownClass;

        this.boundToggleMenu = this.toggleMenu.bind(this);
        this.boundHideMenu = this.hideMenu.bind(this);
        this.boundNavigateBack = this.navigateBack.bind(this);

        if (options) {
            this.options = options;
        }

        this.backgroundLayoutController =
            dependencies?.backgroundLayoutController || new BackgroundLayoutController(this.bgLayoutSelector);

        this.init();
    }

    private handleError(context: string, error: unknown, additionalData?: Record<string, unknown>): void {
        console.error(`[BlogMenuController] ${context}:`, error, additionalData || {});
    }

    private init(): void {
        try {
            this.menuButton = document.querySelector<HTMLButtonElement>(this.menuButtonSelector);
            this.menuContainer = document.querySelector<HTMLElement>(this.menuContainerSelector);
            this.mainMenuElement = this.menuContainer?.querySelector<HTMLElement>(this.mainMenuSelector) || null;
            this.backButtons = Array.from(document.querySelectorAll<HTMLButtonElement>(this.backButtonSelector));

            if (!this.menuButton) {
                console.warn(`Menu button not found: ${this.menuButtonSelector}`);
            } else {
                this.menuButton.addEventListener('click', this.boundToggleMenu);
            }

            this.initMenuItems();
            this.initBackButtons();
            this.initDropdownHoverHandlers();
        } catch (error) {
            this.handleError('Initialization', error, {
                menuButtonSelector: this.menuButtonSelector,
                menuContainerSelector: this.menuContainerSelector,
            });
        }
    }

    private initMenuItems(): void {
        try {
            const menuItems = document.querySelectorAll<HTMLElement>(this.menuItemSelector);

            menuItems.forEach((item) => {
                const handler = (e: Event) => this.handleCategoryClick(e, item);
                this.menuItemHandlers.set(item, handler);
                item.addEventListener('click', handler);
            });
        } catch (error) {
            this.handleError('Menu items initialization', error, {
                menuItemSelector: this.menuItemSelector,
            });
        }
    }

    private initDropdownHoverHandlers(): void {
        const dropdowns = document.querySelectorAll(`.${CSS_CLASSES.MENU.DROPDOWN}`);

        dropdowns.forEach((dropdown) => {
            dropdown.addEventListener('mouseenter', (event: Event) => this.onDropdownMouseEnter(event as MouseEvent));
            dropdown.addEventListener('mouseleave', (event: Event) => this.onDropdownMouseLeave(event as MouseEvent));
        });
    }

    private onDropdownMouseEnter(event: MouseEvent): void {
        const dropdown = event.currentTarget as HTMLElement;
        const parentItem = dropdown.closest(`.${CSS_CLASSES.MENU.MENU_ITEM}`);

        if (parentItem) {
            parentItem.classList.add(CSS_CLASSES.MENU.MENU_ITEM_ACTIVE);
        }
    }

    private onDropdownMouseLeave(event: MouseEvent): void {
        const dropdown = event.currentTarget as HTMLElement;
        const parentItem = dropdown.closest(`.${CSS_CLASSES.MENU.MENU_ITEM}`);

        if (parentItem) {
            parentItem.classList.remove(CSS_CLASSES.MENU.MENU_ITEM_ACTIVE);
        }
    }

    private initBackButtons(): void {
        try {
            this.backButtons.forEach((buttonElement) => {
                buttonElement.addEventListener('click', this.boundNavigateBack);
            });
        } catch (error) {
            this.handleError('Back buttons initialization', error, {
                backButtonSelector: this.backButtonSelector,
            });
        }
    }

    public showMenu(): void {
        if (!this.menuButton || !this.menuContainer || this.isMenuOpen) return;

        try {
            this.backgroundLayoutController.show({ onClick: this.boundHideMenu });
            this.isMenuOpen = true;
            this.menuButton.classList.add(this.menuButtonActiveClass);
            this.menuContainer.classList.add(this.menuContainerShownClass);

            this.options?.onOpen?.();
        } catch (error) {
            this.handleError('Show menu', error);
        }
    }

    public hideMenu(): void {
        if (!this.menuButton || !this.menuContainer || !this.isMenuOpen) return;

        try {
            this.backgroundLayoutController.hide();
            this.isMenuOpen = false;

            const handleTransitionEnd = () => {
                if (this.currentView === 'subcategory') {
                    this.showMainCategories();
                }
                this.menuContainer?.removeEventListener('transitionend', handleTransitionEnd);
            };

            this.menuContainer.addEventListener('transitionend', handleTransitionEnd);
            this.menuButton.classList.remove(this.menuButtonActiveClass);
            this.menuContainer.classList.remove(this.menuContainerShownClass);

            this.options?.onClose?.();
        } catch (error) {
            this.handleError('Hide menu', error);
        }
    }

    private toggleMenu(): void {
        this.isMenuOpen ? this.hideMenu() : this.showMenu();
    }

    private handleCategoryClick(event: Event, item: HTMLElement): void {
        event.preventDefault();

        const categoryId = item.getAttribute('data-category-id');

        if (!categoryId) {
            this.handleError('Category click', new Error('Category ID not found'), { categoryId });
            return;
        }

        this.showSubcategories(categoryId);
    }

    private showSubcategories(categoryId: string): void {
        if (!this.menuContainer) return;

        try {
            this.currentView = 'subcategory';

            if (this.mainMenuElement) {
                this.mainMenuElement.classList.add(this.mainMenuHiddenClass);
            } else {
                const mainCategories = this.menuContainer.querySelector<HTMLElement>(this.mainMenuSelector);
                mainCategories?.classList.add(this.mainMenuHiddenClass);
            }

            const selector = `${this.categoryMenuSelector}[data-parent="${categoryId}"]`;
            const subcategoriesContainer = this.menuContainer.querySelector<HTMLElement>(selector);
            subcategoriesContainer?.classList.add(this.categoryMenuShownClass);
        } catch (error) {
            this.handleError('Show subcategories', error, { categoryId });
        }
    }
    private showMainCategories(): void {
        if (!this.menuContainer) return;

        try {
            this.currentView = 'main';

            if (this.mainMenuElement) {
                this.mainMenuElement.classList.remove(this.mainMenuHiddenClass);
            } else {
                const mainCategories = this.menuContainer.querySelector<HTMLElement>(this.mainMenuSelector);
                mainCategories?.classList.remove(this.mainMenuHiddenClass);
            }

            const allSubcategories = this.menuContainer.querySelectorAll<HTMLElement>(this.categoryMenuSelector);
            allSubcategories.forEach((container) => {
                container.classList.remove(this.categoryMenuShownClass);
            });
        } catch (error) {
            this.handleError('Show main categories', error);
        }
    }

    private navigateBack(): void {
        if (this.currentView === 'subcategory') {
            this.showMainCategories();
        }
    }

    public destroy(): void {
        try {
            this.menuButton?.removeEventListener('click', this.boundToggleMenu);

            this.menuItemHandlers.forEach((handler, element) => {
                element.removeEventListener('click', handler);
            });
            this.menuItemHandlers.clear();

            this.backButtons.forEach((button) => {
                button.removeEventListener('click', this.boundNavigateBack);
            });

            this.backgroundLayoutController = null as any;

            this.isMenuOpen = false;
            this.currentView = 'main';
        } catch (error) {
            this.handleError('Destroy', error);
        }
    }
}
