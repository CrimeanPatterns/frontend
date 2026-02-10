import { BlogMenuController } from '../../controllers/blog-menu/BlogMenuController';
import { pageName } from '../../consts';
import { HeaderResizeController } from './HeaderResizeController';
import { HeaderPopoverController } from './HeaderPopoverController';
import { HeaderClassManager } from './HeaderClassManager';
import throttle from 'lodash/throttle';
import { MetaThemeColorManager } from './MetaThemeColorManager';
import { initStickyHeader } from '@Bem/block/simple-header';
import { HeaderSearchController } from './HeaderSearchController';

export class LearnHeaderController {
    private header: HTMLElement | null = document.querySelector<HTMLDivElement>(`.${pageName}__header`);
    private headerMenuItemElements: NodeListOf<HTMLElement> =
        document.querySelectorAll<HTMLElement>(`.blog-menu__item-title`);
    private isScrolling: boolean = false;

    private resizeController: HeaderResizeController;
    private popoverController: HeaderPopoverController;
    private classManager: HeaderClassManager;
    private themeColorManager: MetaThemeColorManager;

    constructor() {
        this.resizeController = new HeaderResizeController(this.header, this.headerMenuItemElements);
        this.popoverController = new HeaderPopoverController();
        this.classManager = new HeaderClassManager();
        this.themeColorManager = new MetaThemeColorManager();

        initStickyHeader((newColor: string) => {
            this.themeColorManager.setMetaColor(newColor);
        });

        new BlogMenuController(
            {},
            {
                onOpen: this.classManager.addMenuClass.bind(this.classManager),
                onClose: this.classManager.removeMenuClass.bind(this.classManager),
            },
        );

        //@ts-expect-error The value set in learn.twig
        if (!window.isSearchPage) {
            new HeaderSearchController();
        }

        this.initialize();
    }

    private initialize(): void {
        if (window.scrollY > 0) {
            this.updateHeaderUI();
        }

        window.addEventListener('scroll', this.handleScroll.bind(this));

        window.addEventListener('resize', throttle(this.popoverController.adjustAllPopoversPosition, 100));

        this.popoverController.adjustAllPopoversPosition();
    }
    private updateHeaderUI(): void {
        this.resizeController.calculateMenuItemsHeight();
        this.resizeController.calculateHeaderHeight();
        this.popoverController.adjustAllPopoversPosition();
    }

    private handleScroll(): void {
        if (!this.isScrolling) {
            requestAnimationFrame(() => {
                this.updateHeaderUI();
                this.isScrolling = false;
            });
            this.isScrolling = true;
        }
    }
}
