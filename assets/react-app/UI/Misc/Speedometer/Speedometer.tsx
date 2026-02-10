import React, { ReactNode, useEffect, useMemo, useRef, useState } from 'react';

import classes from './Speedometer.module.scss';

type SpeedometerProps = {
    segments: Segment[];
    segmentGap?: number;
    segmentThickness?: number;
    height?: number;
    width?: number;
    defaultLabelDistance?: number;
    currentValue?: number;
    arrowLength?: number;
};

export type Segment = {
    min: number;
    max: number;
    color?: string;
    label: ReactNode;
    description?: ReactNode;
    labelDistance?: number;
    labelOffset?: { x?: number; y?: number };
    descriptionOffset?: { x?: number; y?: number };
    className?: string;
};

type SegmentPosition = {
    startAngle: number;
    endAngle: number;
    min: number;
    max: number;
};

const easeInOutCubic = (t: number): number => {
    return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
};

export function Speedometer({
    segments,
    segmentGap = 3,
    segmentThickness = 15,
    height = 200,
    width,
    defaultLabelDistance = 30,
    currentValue,
}: SpeedometerProps) {
    const [animatedValue, setAnimatedValue] = useState(0);
    const animationRef = useRef<number | null>(null);
    const startTimeRef = useRef<number | null>(null);
    const prevValueRef = useRef<number>(0);
    const isFirstRenderRef = useRef<boolean>(true);

    const svgHeight = height;
    const svgWidth = width || height * 2;

    const radius = Math.min(svgWidth / 2, svgHeight) * 0.8;

    const centerX = Math.round(svgWidth / 2);
    const centerY = Math.round(svgHeight);

    const normalizedGaps: number[] = Array(segments.length - 1).fill(segmentGap) as number[];

    const totalGapDegrees = normalizedGaps.reduce((sum, gap) => sum + gap, 0);
    const availableArcDegrees = 180 - totalGapDegrees;

    useEffect(() => {
        if (currentValue === undefined) return;

        let startValue = 0;
        if (!isFirstRenderRef.current) {
            startValue = prevValueRef.current;
        } else {
            isFirstRenderRef.current = false;
        }

        const targetValue = currentValue;
        prevValueRef.current = targetValue;

        const animate = (timestamp: number) => {
            if (startTimeRef.current === null) {
                startTimeRef.current = timestamp;
            }

            const animationDuration = 1000;
            const elapsed = timestamp - startTimeRef.current;

            const linearProgress = Math.min(elapsed / animationDuration, 1);

            const easedProgress = easeInOutCubic(linearProgress);

            const currentAnimatedValue = startValue + (targetValue - startValue) * easedProgress;

            setAnimatedValue(currentAnimatedValue);

            if (linearProgress < 1) {
                animationRef.current = requestAnimationFrame(animate);
            }
        };

        animationRef.current = requestAnimationFrame(animate);

        return () => {
            if (animationRef.current !== null) {
                cancelAnimationFrame(animationRef.current);
                animationRef.current = null;
            }
            startTimeRef.current = null;
        };
    }, [currentValue]);

    const segmentPositions = useMemo(() => {
        let currentAngle = -180;
        const positions: SegmentPosition[] = [];

        const segmentSize = availableArcDegrees / segments.length;

        for (let i = 0; i < segments.length; i++) {
            const segment = segments[i];
            if (segment) {
                const startAngle = currentAngle;
                const endAngle = startAngle + segmentSize;

                positions.push({
                    startAngle,
                    endAngle,
                    min: segment.min,
                    max: segment.max,
                });

                if (i < segments.length - 1) {
                    const segmentGap = normalizedGaps[i];
                    if (segmentGap) {
                        currentAngle = endAngle + segmentGap;
                    }
                }
            }
        }

        return positions;
    }, [segments, availableArcDegrees, normalizedGaps]);

    const calculateExactArrowAngle = (value: number | undefined) => {
        if (value === undefined) return null;

        const minValue = Math.min(...segments.map((s) => s.min));
        const maxValue = Math.max(...segments.map((s) => s.max));

        if (value <= minValue) return -180;
        if (value >= maxValue) return 0;

        let segmentIndex = -1;
        for (let i = 0; i < segments.length; i++) {
            const segment = segments[i];
            if (segment && value >= segment.min && value <= segment.max) {
                segmentIndex = i;
                break;
            }
        }

        if (segmentIndex !== -1) {
            const segment = segments[segmentIndex];
            const position = segmentPositions[segmentIndex];

            if (segment && position) {
                const segmentRange = segment.max - segment.min;
                const relativePosition = segmentRange > 0 ? (value - segment.min) / segmentRange : 0.5;

                return position.startAngle + (position.endAngle - position.startAngle) * relativePosition;
            }
        }

        for (let i = 0; i < segments.length - 1; i++) {
            const segment = segments[i];
            const nextSegment = segments[i + 1];

            if (segment && nextSegment && value > segment.max && value < nextSegment.min) {
                const segmentPosition = segmentPositions[i];
                const nextSegmentPosition = segmentPositions[i + 1];

                if (segmentPosition && nextSegmentPosition) {
                    const gapSize = nextSegment.min - segment.max;
                    const gapPosition = (value - segment.max) / gapSize;

                    return (
                        segmentPosition.endAngle +
                        (nextSegmentPosition.startAngle - segmentPosition.endAngle) * gapPosition
                    );
                }
            }
        }

        return -180;
    };

    const arrowAngle = useMemo(() => {
        return calculateExactArrowAngle(animatedValue);
    }, [animatedValue]);

    return (
        <div style={{ width: svgWidth, height: svgHeight, position: 'relative' }} className={classes.speedometer}>
            <svg
                width={svgWidth}
                height={svgHeight}
                viewBox={`0 0 ${Math.round(svgWidth)} ${Math.round(svgHeight)}`}
                style={{ overflow: 'visible' }}
            >
                <path
                    d={`M ${centerX} ${centerY} 
                        L ${centerX - radius * 0.7} ${centerY} 
                        A ${radius * 0.7} ${radius * 0.7} 0 0 1 ${centerX + radius * 0.7} ${centerY} 
                        Z`}
                    className={classes.backgroundCircle}
                />

                {segments.map((segment, index) => {
                    const segmentPosition = segmentPositions[index];
                    if (!segmentPosition) return null;
                    const { startAngle, endAngle } = segmentPosition;

                    const startRad = (startAngle * Math.PI) / 180;
                    const endRad = (endAngle * Math.PI) / 180;
                    const outerRadius = radius;

                    const startOuterX = centerX + Math.cos(startRad) * outerRadius;
                    const startOuterY = centerY + Math.sin(startRad) * outerRadius;
                    const endOuterX = centerX + Math.cos(endRad) * outerRadius;
                    const endOuterY = centerY + Math.sin(endRad) * outerRadius;

                    const largeArcFlag = 0;

                    const middleAngle = (startAngle + endAngle) / 2;
                    const middleRad = (middleAngle * Math.PI) / 180;

                    const labelDistance =
                        segment.labelDistance !== undefined ? segment.labelDistance : defaultLabelDistance;

                    const labelRadius = outerRadius + labelDistance;
                    const baseLabelX = centerX + Math.cos(middleRad) * labelRadius;
                    const baseLabelY = centerY + Math.sin(middleRad) * labelRadius;

                    const labelX = baseLabelX + (segment.labelOffset?.x || 0);
                    const labelY = baseLabelY + (segment.labelOffset?.y || 0);

                    const descriptionX = baseLabelX + (segment.descriptionOffset?.x || 0);
                    const descriptionY = baseLabelY + (segment.descriptionOffset?.y || 0);

                    return (
                        <React.Fragment key={index}>
                            <path
                                d={`
                                M ${startOuterX} ${startOuterY}
                                A ${outerRadius} ${outerRadius} 0 ${largeArcFlag} 1 ${endOuterX} ${endOuterY}
                            `}
                                stroke={segment.color || 'currentColor'}
                                className={segment.className}
                                strokeWidth={segmentThickness}
                                fill="none"
                            />

                            <text
                                x={labelX}
                                y={labelY - 20}
                                textAnchor="middle"
                                fill={segment.color || 'currentColor'}
                                className={segment.className}
                                fontWeight="bold"
                            >
                                {segment.label}
                            </text>

                            {segment.description && (
                                <text
                                    x={descriptionX}
                                    y={descriptionY}
                                    textAnchor="middle"
                                    fill={segment.color || 'currentColor'}
                                    className={segment.className}
                                >
                                    {segment.description}
                                </text>
                            )}
                        </React.Fragment>
                    );
                })}

                {arrowAngle !== null && (
                    <>
                        <g
                            style={{
                                transform: `rotate(${arrowAngle + 90}deg)`,
                                transformOrigin: `${centerX}px ${centerY}px`,
                            }}
                        >
                            <path
                                d={`M ${centerX - 3} ${centerY} L ${centerX} ${centerY - 96} L ${centerX + 3} ${centerY} Z`}
                                fill="#646C8B"
                                stroke="none"
                            />

                            <path
                                d={`M ${centerX - 3} ${centerY} L ${centerX} ${centerY - 96} L ${centerX} ${centerY} Z`}
                                fill="#A4ABC2"
                                stroke="none"
                            />
                        </g>
                        <circle cx={centerX} cy={centerY} r="7" fill="#646C8B" />
                        <circle cx={centerX} cy={centerY} r="2.8" fill="#A4ABC2" />
                    </>
                )}
            </svg>
        </div>
    );
}
