define(['jquery-boot', 'jqueryui'], function ($) {
    $.ui.position.customFit = {
        left: function(position, data) {
            var within = data.within,
                withinOffset = within.isWindow ? within.scrollLeft : within.offset.left,
                outerWidth = within.width,
                collisionPosLeft = position.left - data.collisionPosition.marginLeft,
                overLeft = withinOffset - collisionPosLeft,
                overRight = collisionPosLeft + data.collisionWidth - outerWidth - withinOffset,
                newOverRight;

            // element is wider than within
            if ( data.collisionWidth > outerWidth ) {
                // element is initially over the left side of within
                if ( overLeft > 0 && overRight <= 0 ) {
                    newOverRight = position.left + overLeft + data.collisionWidth - outerWidth - withinOffset;
                    position.left += overLeft - newOverRight;
                    // element is initially over right side of within
                } else if ( overRight > 0 && overLeft <= 0 ) {
                    //position.left = withinOffset;
                    // element is initially over both left and right sides of within
                } else {
                    if ( overLeft > overRight ) {
                        position.left = withinOffset + outerWidth - data.collisionWidth;
                    } else {
                        position.left = withinOffset;
                    }
                }
                // too far left -> align with left edge
            } else if ( overLeft > 0 ) {
                position.left += overLeft;
                // too far right -> align with right edge
            } else if ( overRight > 0 ) {
                //position.left -= overRight;
                // adjust based on position and margin
            } else {
                position.left = Math.max( position.left - collisionPosLeft, position.left );
            }
        },
        top: function(position, data) {
            var within = data.within,
                withinOffset = within.isWindow ? within.scrollTop : within.offset.top,
                outerHeight = data.within.height,
                collisionPosTop = position.top - data.collisionPosition.marginTop,
                collisionPosMiddle = collisionPosTop + (data.elemHeight/2) + parseInt(data.elem.css('border-top-width')),
                overTop = withinOffset - collisionPosTop + (within.isWindow ? data.elem.parent().offset().top : 0),
                overBottom = collisionPosTop + data.collisionHeight - outerHeight - withinOffset + 25,
                centerIsVisible = (withinOffset - collisionPosMiddle) < 0 && (collisionPosMiddle - outerHeight - withinOffset) < 0,
                newOverBottom;
            var getAllowableOffset = function(border){
                return (data.elemHeight/2) - border - (data.targetHeight/2) - data.collisionPosition.marginTop;
            };
            var allowableOffsetTop = getAllowableOffset(parseInt(data.elem.css('border-top-width'))),
                allowableOffsetBottom = getAllowableOffset(parseInt(data.elem.css('border-top-width')));

            // element is taller than within
            if ( data.collisionHeight > outerHeight ) {
                // element is initially over the top of within
                if ( overTop > 0 && overBottom <= 0 ) {
                    if (centerIsVisible) {
                        position.top = withinOffset + data.collisionPosition.marginTop;
                    } else {
                        newOverBottom = position.top + overTop + data.collisionHeight - outerHeight - withinOffset;
                        if ((overTop - newOverBottom) < allowableOffsetTop)
                            position.top += overTop - newOverBottom;
                        else
                            position.top += allowableOffsetTop;
                    }
                    // element is initially over bottom of within
                } else if ( overBottom > 0 && overTop <= 0 ) {
                    if ((Math.abs((withinOffset + data.collisionPosition.marginTop) - position.top)) < allowableOffsetBottom)
                        position.top = withinOffset + data.collisionPosition.marginTop;
                    else
                        position.top -= allowableOffsetBottom;
                    // element is initially over both top and bottom of within
                } else {
                    if (centerIsVisible) {
                        position.top = withinOffset + data.collisionPosition.marginTop;
                    } else {
                        if ( overTop > overBottom ) {
                            if ((withinOffset + outerHeight - data.collisionHeight) < allowableOffsetTop)
                                position.top = withinOffset + outerHeight - data.collisionHeight;
                            else
                                position.top += allowableOffsetTop;
                        } else {
                            if ((Math.abs((withinOffset + data.collisionPosition.marginTop) - position.top)) < allowableOffsetBottom)
                                position.top = withinOffset + data.collisionPosition.marginTop;
                            else
                                position.top -= allowableOffsetBottom;
                        }
                    }
                }
                if (position.top < (withinOffset + data.collisionPosition.marginTop) && $(within).scrollTop() == 0)
                    position.top = withinOffset + data.collisionPosition.marginTop;
                // too far up -> align with top
            } else if ( overTop > 0 ) {
                if (overTop < allowableOffsetTop)
                    position.top += overTop;
                else
                    position.top += allowableOffsetTop;
                // too far down -> align with bottom edge
            } else if ( overBottom > 0 ) {
                if (overBottom < allowableOffsetBottom)
                    position.top -= overBottom;
                else
                    position.top -= allowableOffsetBottom;
                // adjust based on position and margin
            } else {
                position.top = Math.max( position.top - collisionPosTop, position.top );
            }
        }
    };
});