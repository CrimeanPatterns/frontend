import { bem } from '@Bem/ts/service/bem';

export class ConnectedPostsController {
    constructor() {}

    public handlePlusButtonClick(e: Event): void {
        const target = e.target as HTMLElement;
        const plusButton = target.closest<HTMLButtonElement>(`.${bem('connected-post', 'plus')}`);

        if (!plusButton) {
            return;
        }

        const itemElement = plusButton.closest<HTMLLIElement>(`.${bem('connected-post', 'item')}`);

        if (!itemElement) {
            return;
        }

        const contentElement = itemElement.querySelector<HTMLDivElement>(`.${bem('connected-post', 'content')}`);

        if (!contentElement) {
            return;
        }

        e.stopPropagation();
        e.preventDefault();

        const isVisible = contentElement.classList.contains('connected-post__content--visible');

        if (isVisible) {
            this.collapse(plusButton, contentElement);
        } else {
            this.expand(plusButton, contentElement);
        }
    }

    private expand(plusButton: HTMLButtonElement, contentElement: HTMLDivElement): void {
        const postElement = plusButton.closest('.post');
        if (postElement) {
            const allExpandedContents = postElement.querySelectorAll<HTMLDivElement>(
                '.connected-post__content--visible',
            );
            const allActivePluses = postElement.querySelectorAll<HTMLDivElement>('.connected-post__plus--active');
            allExpandedContents.forEach((element) => {
                element.classList.remove('connected-post__content--visible');
            });
            allActivePluses.forEach((element) => {
                element.classList.remove('connected-post__plus--active');
            });
        }

        contentElement.classList.add('connected-post__content--visible');
        plusButton.classList.add('connected-post__plus--active');
    }

    private collapse(plusButton: HTMLButtonElement, contentElement: HTMLDivElement): void {
        contentElement.classList.remove('connected-post__content--visible');
        plusButton.classList.remove('connected-post__plus--active');
    }
}
