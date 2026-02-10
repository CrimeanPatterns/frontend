import { animated, useTransition } from '@react-spring/web';
import React, { PropsWithChildren } from 'react';
import classes from './Shadow.module.scss';

type ShadowProps = {
    show?: boolean;
    onClick?: () => void;
} & PropsWithChildren;

export function Shadow({ show = true, children, onClick }: ShadowProps) {
    const transition = useTransition(show, {
        from: {
            opacity: 0,
        },
        enter: {
            opacity: 1,
        },
        leave: {
            opacity: 0,
        },
        config: {
            duration: 200,
        },
    });
    return (
        <>
            {transition((style, show) => (
                <>
                    {show && (
                        <animated.div className={classes.shadow} style={{ opacity: style.opacity }} onClick={onClick}>
                            {children}
                        </animated.div>
                    )}
                </>
            ))}
        </>
    );
}
