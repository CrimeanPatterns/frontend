import {extractOptions} from './env';

// returns a string of classes for a BEM component
export function bem(block: string, element?: string, modifiers: string[] = []): string {
    const opts = extractOptions();
    const classes = [];
    let component: string;

    if (element) {
        classes.push(component = `${block}__${element}`);
    } else {
        classes.push(component = block);
    }

    // add the theme modifier
    if (opts.theme) {
        modifiers.push(opts.theme);
    }

    // add the modifiers
    modifiers.forEach(modifier => {
        classes.push(`${component}--${modifier}`);
    });

    return classes.join(' ');
}

export function bemClass(block: string, element?: string, modifier?: string): string {
    if (modifier) {
        return `${block}__${element}--${modifier}`;
    } else if (element) {
        return `${block}__${element}`;
    } else {
        return block;
    }
}
