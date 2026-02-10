import { SwiperSlide, SwiperSlideProps } from 'swiper/react';
import React from 'react';

export function CarouselItem(props: SwiperSlideProps) {
    return <SwiperSlide {...props} />;
}

CarouselItem.displayName = 'SwiperSlide';
