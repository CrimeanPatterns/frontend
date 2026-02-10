import './simple-header.scss';
import throttle from 'lodash/throttle';

let stickyHeaderElem: HTMLDivElement | null;
let backgroundColor: string;
let onColorChangeCallback: ((backgroundColor: string) => void) | null = null;

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
    stickyHeaderElem = document.querySelector('.simple-header--sticky');

    computeBackgroundColor();
}

function tickColor() {
    if (!stickyHeaderElem) {
        return;
    }

    const alpha = Math.min(window.scrollY / stickyHeaderElem.clientHeight, 1);
    let newBackgroundColor: string;

    if (backgroundColor.startsWith('rgba(')) {
        newBackgroundColor = backgroundColor.replace(/[^,]+(?=\))/, alpha.toString());
    } else if (backgroundColor.startsWith('rgb(')) {
        newBackgroundColor = backgroundColor.replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
    } else {
        throw new Error('Unknown background color format');
    }

    onColorChangeCallback?.(newBackgroundColor);
    stickyHeaderElem.style.backgroundColor = newBackgroundColor;
}

function tick() {
    tickColor();
}

export function initStickyHeader(onColorChange?: (newColor: string) => void) {
    if (onColorChange) {
        onColorChangeCallback = onColorChange;
    }

    prepareStickyHeader();

    window.addEventListener(
        'scroll',
        throttle(() => {
            tick();
        }, 50),
    );
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        computeBackgroundColor();
        tick();
    });

    tick();
}
