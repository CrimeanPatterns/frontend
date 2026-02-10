import { MutableRef } from '../../type-deprecated';
import List, { Props as ListProps, ListRef, RowProps } from './List';
import Loader, { LoadMoreCallback } from './Loader';
import Measure, { ContentRect } from 'react-measure';
import React, { Ref, useRef } from 'react';

const mergeRefs = (...refs: MutableRef<ListRef>[]) => (incomingRef: ListRef) =>
    { refs.forEach((ref) => {
        if (typeof ref === 'function') {
            ref(incomingRef);
        } else if (ref) {
            ref.current = incomingRef;
        }
    }); };

type Props<T> = {
    children: (index: number, measureRef: Ref<Element>) => JSX.Element;
    height: number;
    itemCount: number;
    loadMore: LoadMoreCallback;
    listProps: ListProps<T>;
};

export default function LazyList<T>(props: Props<T>): JSX.Element {
    const { children, itemCount, loadMore, listProps, height } = props;
    const itemSizes = useRef<{ [index: number]: number }>({});
    const listRef = useRef<ListRef>();
    const getItemSize = (index: number): number => itemSizes.current[index] || 50;
    const handleItemResize = (index: number, { bounds, margin }: ContentRect) => {
        itemSizes.current[index] = (bounds?.height ?? 0) + (margin?.top ?? 0) + (margin?.bottom ?? 0);
        if (listRef.current) {
            listRef.current.resetAfterIndex(index, false);
        }
    };

    const Row = ({ index, style }: RowProps<T>) => {
        return (
            <div style={style}>
                <Measure bounds margin onResize={(resizeData) => { handleItemResize(index, resizeData); }}>
                    {({ measureRef }) => children(index, measureRef)}
                </Measure>
            </div>
        );
    };

    return (
        <Loader
            isItemLoaded={(index) => {
                return index < itemCount;
            }}
            itemCount={itemCount + 1}
            loadMoreItems={loadMore}
        >
            {({ onItemsRendered, ref }) => {
                const refs = [ref, listRef as NonNullable<MutableRef<ListRef>>];

                return (
                    <List
                        {...listProps}
                        listRef={mergeRefs(...refs)}
                        onItemsRendered={onItemsRendered}
                        itemCount={itemCount}
                        itemSize={getItemSize}
                        height={height}
                        width={'auto'}
                    >
                        {Row}
                    </List>
                );
            }}
        </Loader>
    );
}
