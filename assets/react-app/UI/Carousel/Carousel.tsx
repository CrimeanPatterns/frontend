import 'swiper/css';
import { Autoplay, FreeMode } from 'swiper/modules';
import { Swiper } from 'swiper/react';
import React, { PropsWithChildren } from 'react';

type CarouselProps = {
    spaceBetween?: number;
    slidesPerView?: number | 'auto';
    freeMode?: boolean;
    loop?: boolean;
    autoplay?: boolean;
} & PropsWithChildren;

export function Carousel({ spaceBetween, slidesPerView, freeMode, loop, autoplay, children }: CarouselProps) {
    return (
        <Swiper
            style={{ width: '100%' }}
            spaceBetween={spaceBetween}
            slidesPerView={slidesPerView}
            modules={[FreeMode, Autoplay]}
            freeMode={freeMode}
            loop={loop}
            autoplay={autoplay}
        >
            {children}
        </Swiper>
    );
}
