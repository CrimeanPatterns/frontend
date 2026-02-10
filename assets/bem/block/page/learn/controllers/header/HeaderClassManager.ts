import { pageName } from '../../consts';

export class HeaderClassManager {
    constructor(private headerSelector: string = 'header') {}

    public addMenuClass(): void {
        const header = document.querySelector(this.headerSelector);
        if (header) {
            header.classList.add(`${pageName}__header--in-menu`);
        }
    }

    public removeMenuClass(): void {
        const header = document.querySelector(this.headerSelector);
        if (header) {
            header.classList.remove(`${pageName}__header--in-menu`);
        }
    }
}
