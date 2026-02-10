import {bemClass} from '../../../ts/service/bem';
import throttle from 'lodash/throttle';

let container: HTMLDivElement | null;
let stickyHeaderElem: HTMLDivElement | null;
let stickyHeaderContentElem: HTMLDivElement | null;
let backgroundColor: string;

function computeBackgroundColor() {
    if (!stickyHeaderElem) {
        return;
    }

    stickyHeaderElem.style.transition = 'none';
    stickyHeaderElem.style.backgroundColor = '';
    backgroundColor = window.getComputedStyle(stickyHeaderElem).backgroundColor;
    stickyHeaderElem.style.transition = '';
}

function prepareStickyHeader() {
    container = document.querySelector('.' + bemClass('page-landing', 'container'));
    stickyHeaderElem = document.querySelector('.' + bemClass('page-landing', 'sticky-header'));

    if (stickyHeaderElem) {
        stickyHeaderContentElem = stickyHeaderElem.querySelector('.' + bemClass('page-landing', 'header'));
    }

    computeBackgroundColor();
}

function tickColor() {
    if (!stickyHeaderElem || !stickyHeaderContentElem) {
        return;
    }

    const alpha = Math.min(window.scrollY / stickyHeaderContentElem.clientHeight, 1);
    let newBackgroundColor: string;

    if (backgroundColor.startsWith('rgba(')) {
        newBackgroundColor = backgroundColor.replace(/[^,]+(?=\))/, alpha.toString());
    } else if (backgroundColor.startsWith('rgb(')) {
        newBackgroundColor = backgroundColor.replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
    } else {
        throw new Error('Unknown background color format');
    }

    stickyHeaderElem.style.backgroundColor = newBackgroundColor;
}

function tickClass() {
    if (!stickyHeaderContentElem) {
        return;
    }

    const smallClass = bemClass('page-landing', 'header', 'small');
    const smallHeader = stickyHeaderContentElem.classList.contains(smallClass);

    if (!smallHeader && window.scrollY > (stickyHeaderContentElem.clientHeight / 2)) {
        if (!stickyHeaderContentElem.classList.contains(smallClass)) {
            stickyHeaderContentElem.classList.add(smallClass);
        }
    } else if (smallHeader && window.scrollY <= (stickyHeaderContentElem.clientHeight / 2)) {
        if (stickyHeaderContentElem.classList.contains(smallClass)) {
            stickyHeaderContentElem.classList.remove(smallClass);
        }
    }
}

function tickContainerSize() {
    if (!container || !stickyHeaderElem) {
        return;
    }

    stickyHeaderElem.style.width = container.clientWidth.toString() + 'px';
}

function tick() {
    tickColor();
    tickClass();
}

export default function (): void {
    prepareStickyHeader();

    window.addEventListener('scroll', throttle(() => {
        tick();
    }, 50));
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        computeBackgroundColor();
        tick();
    });

    tickContainerSize();
    tick();

    setTimeout(() => {
        if (stickyHeaderElem) {
            stickyHeaderElem.style.display = 'block';
        }
    }, 50);

    window.addEventListener('resize', throttle(tickContainerSize, 50));
};