import React, { ReactNode, forwardRef } from 'react';
import classNames from 'classnames';
import classes from './Breadcrumb.module.scss';

interface BreadcrumbProps {
    items: ReactNode[];
    separator: ReactNode;
    onClick?: () => void;
    className?: string;
}
export const Breadcrumb = forwardRef<HTMLDivElement, BreadcrumbProps>(
    ({ items, separator, className, onClick }, ref) => {
        return (
            <div ref={ref} onClick={onClick} className={classNames(classes.container, className)}>
                {items.map((item, index) => {
                    return (
                        <React.Fragment key={index}>
                            {item}
                            {index + 1 !== items.length && separator}
                        </React.Fragment>
                    );
                })}
            </div>
        );
    },
);

Breadcrumb.displayName = 'Breadcrumb';
