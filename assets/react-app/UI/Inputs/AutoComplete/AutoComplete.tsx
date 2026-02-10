import { FixedSizeList } from 'react-window';
import { Hint, Hint_Height } from './Components/Hint';
import { Popover } from '@UI/Popovers';
import { TextInput, TextInputProps } from '..';
import { createUseStyles } from 'react-jss';
import AutoSizer from 'react-virtualized-auto-sizer';
import React, { ChangeEvent, ForwardedRef, forwardRef, memo, useCallback, useEffect, useRef, useState } from 'react';
import classNames from 'classnames';
import classes from './AutoComplete.module.scss';

const Inner_Popover_Container_Padding = 4 * 2;
const Max_Hints_List_Items = 8;
const Max_Hints_List_Height = Max_Hints_List_Items * Hint_Height + Inner_Popover_Container_Padding;

export interface AutoCompleteHint<T> {
    label: string;
    value: T;
}

type AutoCompleteProps<T> = Omit<TextInputProps, 'onChange'> & {
    onChange: (newValue: T | string, isHint: boolean) => void;
    hints?: AutoCompleteHint<T>[];
};

interface CreateAutoCompleteClassesProps {
    anchorWidth: number | undefined;
    hintsCount: number;
}
const createClasses = createUseStyles(() => ({
    popoverContainer: ({ anchorWidth, hintsCount }: CreateAutoCompleteClassesProps) => {
        if (!anchorWidth) return;

        return {
            width: anchorWidth,
            height: Math.min(Max_Hints_List_Height, hintsCount * Hint_Height) + Inner_Popover_Container_Padding,
        };
    },
}));

function AutoCompleteBase<T>({ onChange, hints, ...props }: AutoCompleteProps<T>, ref: ForwardedRef<HTMLInputElement>) {
    const [isPopoverOpen, setIsPopoverOpen] = useState(false);
    const [selectedHint, setSelectedHint] = useState<AutoCompleteHint<T> | null>(null);
    const [shownHints, setShownHints] = useState<AutoCompleteHint<T>[]>(hints ?? []);

    const anchorRef = useRef<HTMLInputElement>(null);

    const jssClasses = createClasses({
        anchorWidth: anchorRef.current?.getBoundingClientRect().width,
        hintsCount: shownHints.length,
    });

    const openPopover = () => {
        setIsPopoverOpen(true);
    };
    const closePopover = () => {
        setIsPopoverOpen(false);
    };
    const onFocus = () => {
        openPopover();
        props.onFocus?.();
    };

    const onChangeHandler = useCallback(
        (event: ChangeEvent<HTMLInputElement>) => {
            const hint = hints?.find((hint) => hint.label === event.target.value) ?? null;

            if (hint) {
                onChange(hint.value, true);
                return;
            }

            onChange(event.target.value, false);
        },
        [hints, onChange],
    );
    useEffect(() => {
        if (hints && hints.length > 0) {
            setShownHints(hints);
            return;
        }
    }, [hints]);

    useEffect(() => {
        const isThereHint = hints?.find((hint) => hint.label === props.value) ?? null;

        setSelectedHint(isThereHint);
    }, [props.value, hints]);

    return (
        <div>
            <div ref={anchorRef}>
                <TextInput {...props} ref={ref} onFocus={onFocus} onChange={onChangeHandler} />
            </div>
            <Popover
                open={isPopoverOpen && (hints?.length ?? 0) > 0}
                anchor={anchorRef}
                onClose={closePopover}
                closeTrigger="click"
            >
                <div className={classNames(jssClasses.popoverContainer, classes.container)}>
                    <AutoSizer>
                        {({ height, width }) => (
                            <FixedSizeList
                                height={height}
                                itemCount={shownHints.length}
                                itemData={shownHints}
                                itemSize={Hint_Height}
                                width={width}
                            >
                                {({ style, data, index }) => {
                                    const hint = data[index];

                                    if (!hint) return null;

                                    const isSelected = selectedHint?.label === hint.label;
                                    const onClick = () => {
                                        onChange(hint.value, true);
                                        closePopover();
                                    };

                                    return (
                                        <div style={style}>
                                            <Hint
                                                onClick={onClick}
                                                key={hint.label}
                                                hint={hint}
                                                selected={isSelected}
                                            />
                                        </div>
                                    );
                                }}
                            </FixedSizeList>
                        )}
                    </AutoSizer>
                </div>
            </Popover>
        </div>
    );
}
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const AutoCompleteForwardRef = forwardRef<HTMLInputElement, any>(AutoCompleteBase);

export const AutoComplete = memo(AutoCompleteForwardRef);
