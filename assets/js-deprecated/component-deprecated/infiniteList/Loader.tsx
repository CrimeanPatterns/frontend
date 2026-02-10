import { FunctionComponent, RefCallback, useRef } from 'react';
import type { ListRef, onItemsRenderedCallback } from './List';

type Range = [number, number];
type Ranges = Range[];
type ItemLoadedCallback = (index: number) => boolean;
export type LoadMoreCallback = (startIndex: number, stopIndex: number) => Promise<unknown> | null;
type ItemRendererProp = {
    onItemsRendered: onItemsRenderedCallback;
    ref: RefCallback<ListRef> | null;
};

function scanForUnloadedRanges(
    isItemLoaded: ItemLoadedCallback,
    itemCount: number,
    minimumBatchSize: number,
    startIndex: number,
    stopIndex: number,
): Ranges {
    const unloadedRanges: Ranges = [];

    let rangeStartIndex: number | null = null;
    let rangeStopIndex: number | null = null;

    for (let index = startIndex; index <= stopIndex; index++) {
        const loaded = isItemLoaded(index);

        if (!loaded) {
            rangeStopIndex = index;
            if (rangeStartIndex === null) {
                rangeStartIndex = index;
            }
        } else if (rangeStartIndex !== null && rangeStopIndex !== null) {
            unloadedRanges.push([rangeStartIndex, rangeStopIndex]);
            rangeStartIndex = rangeStopIndex = null;
        }
    }

    // If :rangeStopIndex is not null it means we haven't ran out of unloaded rows.
    // Scan forward to try filling our :minimumBatchSize.
    if (rangeStartIndex !== null && rangeStopIndex !== null) {
        const potentialStopIndex = Math.min(
            Math.max(rangeStopIndex, rangeStartIndex + minimumBatchSize - 1),
            itemCount - 1,
        );
        for (let index = rangeStopIndex + 1; index <= potentialStopIndex; index++) {
            if (!isItemLoaded(index)) {
                rangeStopIndex = index;
            } else {
                break;
            }
        }

        unloadedRanges.push([rangeStartIndex, rangeStopIndex]);
    }

    // Check to see if our first range ended prematurely.
    // In this case we should scan backwards to try filling our :minimumBatchSize.
    if (unloadedRanges.length) {
        const firstRange = unloadedRanges[0];
        while (firstRange && firstRange[1] - firstRange[0] + 1 < minimumBatchSize && firstRange[0] > 0) {
            const index = firstRange[0] - 1;
            if (!isItemLoaded(index)) {
                firstRange[0] = index;
            } else {
                break;
            }
        }
    }

    return unloadedRanges;
}

function isRangeVisible(
    lastRenderedStartIndex: number,
    lastRenderedStopIndex: number,
    startIndex: number,
    stopIndex: number,
): boolean {
    return !(startIndex > lastRenderedStopIndex || stopIndex < lastRenderedStartIndex);
}

type Props = {
    children: FunctionComponent<ItemRendererProp>;
    isItemLoaded: ItemLoadedCallback;
    itemCount: number;
    loadMoreItems: LoadMoreCallback;
    minimumBatchSize?: number;
    threshold?: number;
};

type Context = {
    lastRenderedStartIndex: number;
    lastRenderedStopIndex: number;
    memoizedUnloadedRanges: Ranges;
};

const Loader: React.FunctionComponent<Props> = function (props: Props) {
    const context = useRef<Context>({
        lastRenderedStartIndex: -1,
        lastRenderedStopIndex: -1,
        memoizedUnloadedRanges: [],
    });
    const ref = useRef<ListRef | null>(null);

    function onItemsRendered(visibleStartIndex: number, visibleStopIndex: number) {
        context.current.lastRenderedStartIndex = visibleStartIndex;
        context.current.lastRenderedStopIndex = visibleStopIndex;
        ensureRowsLoaded(visibleStartIndex, visibleStopIndex);
    }

    // function resetloadMoreItemsCache(autoReload = false) {
    //     context.current.memoizedUnloadedRanges = [];
    //
    //     if (autoReload) {
    //         ensureRowsLoaded(context.current.lastRenderedStartIndex, context.current.lastRenderedStopIndex);
    //     }
    // }

    function loadUnloadedRanges(unloadedRanges: Ranges) {
        const { loadMoreItems } = props;

        unloadedRanges.forEach(([startIndex, stopIndex]) => {
            const promise = loadMoreItems(startIndex, stopIndex);
            if (promise != null) {
                promise
                    .then(() => {
                        // Refresh the visible rows if any of them have just been loaded.
                        // Otherwise they will remain in their unloaded visual state.
                        if (
                            isRangeVisible(
                                context.current.lastRenderedStartIndex,
                                context.current.lastRenderedStopIndex,
                                startIndex,
                                stopIndex,
                            )
                        ) {
                            // Handle an unmount while promises are still in flight.
                            if (ref.current === null) {
                                return;
                            }

                            // Resize cached row sizes for VariableSizeList,
                            // otherwise just re-render the list.
                            if (typeof ref.current.resetAfterIndex === 'function') {
                                ref.current.resetAfterIndex(startIndex, true);
                            } else {
                                // HACK reset temporarily cached item styles to force PureComponent to re-render.
                                // This is pretty gross, but I'm okay with it for now.
                                // Don't judge me.
                                if (typeof ref.current.getItemStyleCache === 'function') {
                                    ref.current.getItemStyleCache(-1);
                                }
                                ref.current.forceUpdate();
                            }
                        }
                    })
                    .catch((e) => {
                        console.log(e);
                    });
            }
        });
    }

    function ensureRowsLoaded(startIndex: number, stopIndex: number) {
        const { isItemLoaded, itemCount, minimumBatchSize = 10, threshold = 15 } = props;
        const unloadedRanges = scanForUnloadedRanges(
            isItemLoaded,
            itemCount,
            minimumBatchSize,
            Math.max(0, startIndex - threshold),
            Math.min(itemCount - 1, stopIndex + threshold),
        );

        // Avoid calling load-rows unless range has changed.
        // This shouldn't be strictly necsesary, but is maybe nice to do.
        if (
            context.current.memoizedUnloadedRanges.length !== unloadedRanges.length ||
            context.current.memoizedUnloadedRanges.some(
                function ([startIndex, stopIndex], index) {
                    const range = unloadedRanges[index];

                    return range instanceof Range && (range[0] !== startIndex || range[1] !== stopIndex);
                },
            )
        ) {
            context.current.memoizedUnloadedRanges = unloadedRanges;
            loadUnloadedRanges(unloadedRanges);
        }
    }

    return props.children({
        onItemsRendered,
        ref: (listRef: ListRef) => {
            ref.current = listRef;
        },
    });
}

export default Loader;
