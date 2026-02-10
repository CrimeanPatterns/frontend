import {
    AllHTMLAttributes,
    CSSProperties,
    ComponentClass,
    FunctionComponent,
    ReactNode,
    SyntheticEvent,
    createElement,
    useEffect,
    useImperativeHandle,
    useRef,
} from 'react';
import { MutableRef } from '../../type-deprecated';
import memoizeOne from 'memoize-one';
import useForceUpdate from '../../../bem/ts/hook/useForceUpdate';
import useStateWithCallback from '../../../bem/ts/hook/useStateWithCallback';

type ScrollEvent = SyntheticEvent<HTMLDivElement>;
type ItemKeyCallback<T> = (index: number, data: T) => string | number;
type TimeoutID = {
    id: number;
};
// type ScrollToAlign = 'auto' | 'smart' | 'center' | 'start' | 'end';
type ItemSize = (index: number) => number;
type ScrollDirection = 'forward' | 'backward';
export type RowProps<T> = {
    data: T;
    index: number;
    isScrolling?: boolean;
    style: CSSProperties;
};
type RenderComponent<T> = FunctionComponent<T> | ComponentClass<T> | string;
export type RowComponent<T> = RenderComponent<RowProps<T>>;
export type onItemsRenderedCallback = (
    overscanStartIndex: number,
    overscanStopIndex: number,
    visibleStartIndex: number,
    visibleStopIndex: number,
) => void;
type onScrollCallback = (
    scrollDirection: ScrollDirection,
    scrollOffset: number,
    scrollUpdateWasRequested: boolean,
) => void;
type InnerProps =
    | {
          onScroll: (event: ScrollEvent) => void;
          ref: MutableRef<HTMLElement>;
      }
    | AllHTMLAttributes<HTMLElement>;
export type Props<T> = {
    listRef: MutableRef<ListRef>;
    children: RowComponent<T>;
    className?: string;
    innerClassName?: string;
    height: number;
    estimatedItemSize?: number;
    initialScrollOffset?: number;
    innerRef?: MutableRef<HTMLElement>;
    innerElementType?: RenderComponent<InnerProps>;
    itemCount: number;
    itemData: T;
    itemKey?: ItemKeyCallback<T>;
    itemSize: ItemSize;
    onItemsRendered?: onItemsRenderedCallback;
    onScroll?: onScrollCallback;
    outerRef?: MutableRef<ReactNode>;
    outerElementType?: RenderComponent<InnerProps>;
    overscanCount: number;
    style?: CSSProperties;
    innerStyle?: CSSProperties;
    width: number | string;
};
type State = {
    isScrolling: boolean;
    scrollDirection: ScrollDirection;
    scrollOffset: number;
    scrollUpdateWasRequested: boolean;
};
type ItemMetadata = {
    offset: number;
    size: number;
};
type Context = {
    itemMetadataMap: { [index: number]: ItemMetadata };
    lastMeasuredIndex: number;
    estimatedItemSize: number;
};
export type ListRef = {
    resetAfterIndex(index: number, shouldForceUpdate: boolean): void;
    getItemStyleCache: ItemStyleCacheCallback;
    forceUpdate: () => void;
};
export type ItemStyleCacheCallback = (index: number) => { [index: string]: CSSProperties };

const IS_SCROLLING_DEBOUNCE_INTERVAL = 150;

const hasNativePerformanceNow: boolean = typeof performance === 'object' && typeof performance.now === 'function';

const now: () => number = hasNativePerformanceNow ? () => performance.now() : () => Date.now();

function cancelTimeout(timeoutID: TimeoutID): void {
    cancelAnimationFrame(timeoutID.id);
}

function requestTimeout(callback: () => void, delay: number): TimeoutID {
    const start = now();

    function tick() {
        if (now() - start >= delay) {
            callback.call(null);
        } else {
            timeoutID.id = requestAnimationFrame(tick);
        }
    }

    const timeoutID: TimeoutID = {
        id: requestAnimationFrame(tick),
    };

    return timeoutID;
}

function getItemMetadata<T>(props: Props<T>, index: number, context: Context): ItemMetadata {
    const { itemSize } = props;
    const { itemMetadataMap, lastMeasuredIndex } = context;

    if (index > lastMeasuredIndex) {
        let offset = 0;
        if (lastMeasuredIndex >= 0) {
            const itemMetadata = itemMetadataMap[lastMeasuredIndex];

            if (itemMetadata) {
                offset = itemMetadata.offset + itemMetadata.size;
            }
        }

        for (let i = lastMeasuredIndex + 1; i <= index; i++) {
            const size = itemSize(i);

            itemMetadataMap[i] = {
                offset,
                size,
            };

            offset += size;
        }

        context.lastMeasuredIndex = index;
    }

    const result = itemMetadataMap[index];

    if (!result) {
        throw new Error(`Item metadata for index ${index} is missing`);
    }

    return result;
}

function findNearestItem<T>(props: Props<T>, context: Context, offset: number): number {
    const { itemMetadataMap, lastMeasuredIndex } = context;
    const lastMeasuredItemOffset = lastMeasuredIndex > 0 ? itemMetadataMap[lastMeasuredIndex]?.offset ?? 0 : 0;

    if (lastMeasuredItemOffset >= offset) {
        // If we've already measured items within this range just use a binary search as it's faster.
        return findNearestItemBinarySearch(props, context, lastMeasuredIndex, 0, offset);
    } else {
        // If we haven't yet measured this high, fallback to an exponential search with an inner binary search.
        // The exponential search avoids pre-computing sizes for the full set of items as a binary search would.
        // The overall complexity for this approach is O(log n).
        return findNearestItemExponentialSearch(props, context, Math.max(0, lastMeasuredIndex), offset);
    }
}

function findNearestItemBinarySearch<T>(
    props: Props<T>,
    context: Context,
    high: number,
    low: number,
    offset: number,
): number {
    while (low <= high) {
        const middle = low + Math.floor((high - low) / 2);
        const currentOffset = getItemMetadata(props, middle, context).offset;

        if (currentOffset === offset) {
            return middle;
        } else if (currentOffset < offset) {
            low = middle + 1;
        } else if (currentOffset > offset) {
            high = middle - 1;
        }
    }

    if (low > 0) {
        return low - 1;
    } else {
        return 0;
    }
}

function findNearestItemExponentialSearch<T>(props: Props<T>, context: Context, index: number, offset: number): number {
    const { itemCount } = props;
    let interval = 1;

    while (index < itemCount && getItemMetadata(props, index, context).offset < offset) {
        index += interval;
        interval *= 2;
    }

    return findNearestItemBinarySearch(props, context, Math.min(index, itemCount - 1), Math.floor(index / 2), offset);
}

function getEstimatedTotalSize<T>(
    { itemCount }: Props<T>,
    { itemMetadataMap, estimatedItemSize, lastMeasuredIndex }: Context,
): number {
    let totalSizeOfMeasuredItems = 0;
    // Edge case check for when the number of items decreases while a scroll is in progress.
    // https://github.com/bvaughn/react-window/pull/138
    if (lastMeasuredIndex >= itemCount) {
        lastMeasuredIndex = itemCount - 1;
    }
    if (lastMeasuredIndex >= 0) {
        const itemMetadata = itemMetadataMap[lastMeasuredIndex];

        if (itemMetadata) {
            totalSizeOfMeasuredItems = itemMetadata.offset + itemMetadata.size;
        }
    }

    const numUnmeasuredItems = itemCount - lastMeasuredIndex - 1;
    const totalSizeOfUnmeasuredItems = numUnmeasuredItems * estimatedItemSize;

    return totalSizeOfMeasuredItems + totalSizeOfUnmeasuredItems;
}

export default function List<T>(props: Props<T>): JSX.Element {
    const {
        initialScrollOffset = 0,
        itemData = undefined,
        overscanCount = 2,
        estimatedItemSize = 50,
        listRef,
        itemCount,
        itemKey = (index) => index,
        children,
        outerElementType,
        innerElementType,
        innerClassName,
        className,
        innerRef,
        height,
        width,
        style,
        innerStyle,
    } = props;
    const [{ isScrolling, scrollUpdateWasRequested, scrollDirection, scrollOffset }, setState] =
        useStateWithCallback<State>({
            isScrolling: false,
            scrollUpdateWasRequested: false,
            scrollDirection: 'forward',
            scrollOffset: initialScrollOffset,
        });

    const context = useRef<Context>({
        itemMetadataMap: {},
        lastMeasuredIndex: -1,
        estimatedItemSize: estimatedItemSize,
    });
    const resetIsScrollingTimeoutId = useRef<TimeoutID | null>(null);
    const outerListRef = useRef<HTMLElement | null>(null);
    const getItemStyleCache = memoizeOne<ItemStyleCacheCallback>(() => ({}));
    const forceUpdate = useForceUpdate();

    // const scrollTo = (scrollOffset: number) => {
    //     scrollOffset = Math.max(0, scrollOffset);
    //     setState((prevState) => {
    //         if (prevState.scrollOffset === scrollOffset) {
    //             return {...prevState};
    //         }
    //         return {
    //             ...prevState,
    //             scrollDirection: prevState.scrollOffset < scrollOffset ? 'forward' : 'backward',
    //             scrollOffset: scrollOffset,
    //             scrollUpdateWasRequested: true,
    //         };
    //     }, resetIsScrollingDebounced);
    // };
    // const scrollToItem = (index: number, align: ScrollToAlign = 'auto') => {
    //     const { itemCount } = props;
    //     index = Math.max(0, Math.min(index, itemCount - 1));
    //     scrollTo(getOffsetForIndexAndAlignment(props, index, align, scrollOffset, context.current));
    // };
    const outerRefSetter = (ref: HTMLElement): void => {
        const { outerRef } = props;

        outerListRef.current = ref;

        if (typeof outerRef === 'function') {
            // @ts-expect-error 123
            outerRef(ref);
        } else if (
            outerRef != null &&
            typeof outerRef === 'object' &&
            Object.prototype.hasOwnProperty.call(outerRef, 'current')
        ) {
            // @ts-expect-error 123
            outerRef.current = ref;
        }
    };
    // const getOffsetForIndexAndAlignment = (
    //     props: Props<T>,
    //     index: number,
    //     align: ScrollToAlign,
    //     scrollOffset: number,
    //     context: Context,
    // ) => {
    //     const { height } = props;
    //     const size = height;
    //     const itemMetadata = getItemMetadata(props, index, context);
    //     // Get estimated total size after ItemMetadata is computed,
    //     // To ensure it reflects actual measurements instead of just estimates.
    //     const estimatedTotalSize = getEstimatedTotalSize(props, context);
    //     const maxOffset = Math.max(0, Math.min(estimatedTotalSize - size, itemMetadata.offset));
    //     const minOffset = Math.max(0, itemMetadata.offset - size + itemMetadata.size);
    //     if (align === 'smart') {
    //         if (scrollOffset >= minOffset - size && scrollOffset <= maxOffset + size) {
    //             align = 'auto';
    //         } else {
    //             align = 'center';
    //         }
    //     }
    //     switch (align) {
    //         case 'start':
    //             return maxOffset;
    //         case 'end':
    //             return minOffset;
    //         case 'center':
    //             return Math.round(minOffset + (maxOffset - minOffset) / 2);
    //         case 'auto':
    //         default:
    //             if (scrollOffset >= minOffset && scrollOffset <= maxOffset) {
    //                 return scrollOffset;
    //             } else if (scrollOffset < minOffset) {
    //                 return minOffset;
    //             } else {
    //                 return maxOffset;
    //             }
    //     }
    // };
    const getStopIndexForStartIndex = (props: Props<T>, startIndex: number, scrollOffset: number, context: Context) => {
        const { height, itemCount } = props;
        const size = height;
        const itemMetadata = getItemMetadata(props, startIndex, context);
        const maxOffset = scrollOffset + size;
        let offset = itemMetadata.offset + itemMetadata.size;
        let stopIndex = startIndex;

        while (stopIndex < itemCount - 1 && offset < maxOffset) {
            stopIndex++;
            offset += getItemMetadata(props, stopIndex, context).size;
        }

        return stopIndex;
    };
    const getRangeToRender = (): [number, number, number, number] => {
        const { itemCount } = props;

        if (itemCount === 0) {
            return [0, 0, 0, 0];
        }

        const startIndex = findNearestItem(props, context.current, scrollOffset);
        const stopIndex = getStopIndexForStartIndex(props, startIndex, scrollOffset, context.current);
        // Overscan by one item in each direction so that tab/focus works.
        // If there isn't at least one extra item, tab loops back around.
        const overscanBackward = !isScrolling || scrollDirection === 'backward' ? Math.max(1, overscanCount) : 1;
        const overscanForward = !isScrolling || scrollDirection === 'forward' ? Math.max(1, overscanCount) : 1;

        return [
            Math.max(0, startIndex - overscanBackward),
            Math.max(0, Math.min(itemCount - 1, stopIndex + overscanForward)),
            startIndex,
            stopIndex,
        ];
    };

    const { onItemsRendered, onScroll: onScrollCallable } = props;

    const callOnItemsRendered = memoizeOne(function (
        overscanStartIndex: number,
        overscanStopIndex: number,
        visibleStartIndex: number,
        visibleStopIndex: number,
    ) {
        if (onItemsRendered) {
            onItemsRendered(overscanStartIndex, overscanStopIndex, visibleStartIndex, visibleStopIndex);
            return;
        }
    });
    const callOnScroll = memoizeOne(function (
        scrollDirection: ScrollDirection,
        scrollOffset: number,
        scrollUpdateWasRequested: boolean,
    ) {
        if (onScrollCallable) {
            onScrollCallable(scrollDirection, scrollOffset, scrollUpdateWasRequested);
            return;
        }
    });
    const callPropsCallbacks = () => {
        const { onItemsRendered, onScroll, itemCount } = props;

        if (typeof onItemsRendered === 'function') {
            if (itemCount > 0) {
                const [overscanStartIndex, overscanStopIndex, visibleStartIndex, visibleStopIndex] = getRangeToRender();
                callOnItemsRendered(overscanStartIndex, overscanStopIndex, visibleStartIndex, visibleStopIndex);
            }
        }
        if (typeof onScroll === 'function') {
            callOnScroll(scrollDirection, scrollOffset, scrollUpdateWasRequested);
        }
    };
    const getItemStyle = (index: number): CSSProperties => {
        const itemStyleCache = getItemStyleCache(-1);

        let style: CSSProperties;
        const itemStyleCacheValue = itemStyleCache[index];

        if (itemStyleCacheValue) {
            style = itemStyleCacheValue;
        } else {
            const offset: number = getItemMetadata(props, index, context.current).offset;
            const size: number = context.current.itemMetadataMap[index]?.size ?? 0;
            itemStyleCache[index] = style = {
                position: 'absolute',
                left: 0,
                top: offset,
                height: size,
                width: '100%',
            };
        }

        return style;
    };
    const onScrollVertical = (event: ScrollEvent) => {
        const { clientHeight, scrollHeight, scrollTop } = event.currentTarget;

        setState((prevState) => {
            if (prevState.scrollOffset === scrollTop) {
                // Scroll position may have been updated by cDM/cDU,
                // In which case we don't need to trigger another render,
                // And we don't want to update state.isScrolling.
                return { ...prevState };
            }

            // Prevent Safari's elastic scrolling from causing visual shaking when scrolling past bounds.
            const scrollOffset = Math.max(0, Math.min(scrollTop, scrollHeight - clientHeight));

            return {
                ...prevState,
                isScrolling: true,
                scrollDirection: prevState.scrollOffset < scrollOffset ? 'forward' : 'backward',
                scrollOffset,
                scrollUpdateWasRequested: false,
            };
        }, resetIsScrollingDebounced);
    };
    const resetIsScrollingDebounced = (): void => {
        if (resetIsScrollingTimeoutId.current !== null) {
            cancelTimeout(resetIsScrollingTimeoutId.current);
        }

        resetIsScrollingTimeoutId.current = requestTimeout(resetIsScrolling, IS_SCROLLING_DEBOUNCE_INTERVAL);
    };
    const resetIsScrolling = (): void => {
        resetIsScrollingTimeoutId.current = null;

        setState(
            (prevState) => {
                return {
                    ...prevState,
                    isScrolling: false,
                };
            },
            () => {
                getItemStyleCache(-1);
            },
        );
    };

    useImperativeHandle(listRef, () => ({
        resetAfterIndex(index, shouldForceUpdate = true) {
            context.current.lastMeasuredIndex = Math.min(context.current.lastMeasuredIndex, index - 1);
            // We could potentially optimize further by only evicting styles after this index,
            // But since styles are only cached while scrolling is in progress-
            // It seems an unnecessary optimization.
            // It's unlikely that resetAfterIndex() will be called while a user is scrolling.
            getItemStyleCache(-1);
            if (shouldForceUpdate) {
                forceUpdate();
            }
        },
        getItemStyleCache,
        forceUpdate,
    }));

    useEffect(() => {
        if (outerListRef.current != null) {
            outerListRef.current.scrollTop = initialScrollOffset;
        }
        callPropsCallbacks();
    }, []);

    useEffect(() => {
        if (scrollUpdateWasRequested && outerListRef.current != null) {
            outerListRef.current.scrollTop = scrollOffset;
        }
        callPropsCallbacks();

        return () => {
            if (resetIsScrollingTimeoutId.current !== null) {
                cancelTimeout(resetIsScrollingTimeoutId.current);
            }
        };
    });

    const onScroll = onScrollVertical;
    const [startIndex, stopIndex] = getRangeToRender();
    const items = [];
    if (itemCount > 0) {
        for (let index = startIndex; index <= stopIndex; index++) {
            items.push(
                createElement(children, {
                    data: itemData as NonNullable<T>,
                    key: itemKey(index, itemData as NonNullable<T>),
                    index,
                    isScrolling,
                    style: getItemStyle(index),
                }),
            );
        }
    }

    // Read this value AFTER items have been created,
    // So their actual sizes (if variable) are taken into consideration.
    const estimatedTotalSize = getEstimatedTotalSize(props, context.current);

    return createElement(
        outerElementType || 'div',
        {
            className,
            onScroll,
            ref: outerRefSetter,
            style: {
                position: 'relative',
                height,
                width,
                WebkitOverflowScrolling: 'touch',
                willChange: 'transform',
                ...style,
            },
        },
        createElement(
            innerElementType || 'div',
            {
                className: innerClassName,
                ref: innerRef,
                style: {
                    height: estimatedTotalSize,
                    pointerEvents: isScrolling ? 'none' : undefined,
                    width: '100%',
                    ...innerStyle,
                },
            },
            items,
        ),
    ) as JSX.Element;
}
