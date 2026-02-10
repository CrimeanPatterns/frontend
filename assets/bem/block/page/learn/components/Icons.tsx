import React from 'dom-chef';

export function createBookmarkIcon(isChecked: boolean) {
    return (
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            {isChecked ? (
                <path
                    d="M5 21V5C5 4.45 5.19583 3.97917 5.5875 3.5875C5.97917 3.19583 6.45 3 7 3H17C17.55 3 18.0208 3.19583 18.4125 3.5875C18.8042 3.97917 19 4.45 19 5V21L12 18L5 21Z"
                    fill="currentColor"
                />
            ) : (
                <path
                    d="M5 21V5C5 4.45 5.19583 3.97917 5.5875 3.5875C5.97917 3.19583 6.45 3 7 3H17C17.55 3 18.0208 3.19583 18.4125 3.5875C18.8042 3.97917 19 4.45 19 5V21L12 18L5 21ZM7 17.95L12 15.8L17 17.95V5H7V17.95Z"
                    fill="currentColor"
                />
            )}
        </svg>
    );
}

export function createDoubleCheckIcon() {
    return (
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M17.0686 5.22772C17.4526 5.56299 17.4921 6.14612 17.1569 6.53015L7.48658 17.6071C7.31128 17.8078 7.05774 17.923 6.79121 17.923C6.52466 17.923 6.27114 17.8078 6.09584 17.6071L2.22772 13.1763C1.89245 12.7922 1.93198 12.2091 2.31603 11.8738C2.70006 11.5386 3.28319 11.5781 3.61846 11.9622L6.79121 15.5964L15.7661 5.31603C16.1014 4.93198 16.6845 4.89245 17.0686 5.22772Z"
                fill="currentColor"
            />
            <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M23.2519 5.3318C23.621 5.68339 23.6353 6.26767 23.2836 6.63684L12.7339 17.7137C12.5474 17.9095 12.2845 18.0136 12.0146 17.9988C11.7447 17.9839 11.4949 17.8514 11.3311 17.6363L10.8039 16.944C10.4951 16.5384 10.5735 15.9593 10.9791 15.6505C11.3275 15.3851 11.8041 15.4055 12.1273 15.6737L21.9468 5.36361C22.2984 4.99446 22.8827 4.9802 23.2519 5.3318Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function createCalendarIcon() {
    return (
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <mask id="mask0_20584_2" maskUnits="userSpaceOnUse" x="0" y="0" width="16" height="16">
                <rect width="16" height="16" fill="currentColor" />
            </mask>
            <g mask="url(#mask0_20584_2)">
                <g opacity="0.4">
                    <path d="M1.375 1.375H14.625V4H1.375V1.375Z" fill="currentColor" />
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M1.375 5.375H14.625V14.625H1.375V5.375ZM13.375 10.75V9.25H10.625V10.75H13.375ZM2.625 9.25V10.75H5.375V9.25H2.625ZM13.375 13.375V11.875H10.625V13.375H13.375ZM2.625 11.875V13.375H5.375V11.875H2.625ZM13.375 8.125V6.625H10.625V8.125H13.375ZM2.625 6.625V8.125H5.375V6.625H2.625ZM6.625 6.625V8.125H9.375V6.625H6.625ZM9.375 10.75V9.25H6.625V10.75H9.375ZM6.625 11.875V13.375H9.375V11.875H6.625Z"
                        fill="currentColor"
                    />
                </g>
            </g>
        </svg>
    );
}

export function createCommentsIcon() {
    return (
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g opacity="0.4">
                <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M1.63086 1.63086V11.5726H3.68911V14.4464L7.72795 11.5726H14.3687V1.63086H1.63086ZM3.18426 10.0192V3.18426H12.8153V10.0192H7.51173L5.16484 11.5726V10.0192H3.18426Z"
                    fill="currentColor"
                />
            </g>
        </svg>
    );
}

export function createFacebookIcon() {
    return (
        <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M11.6832 6.0002H9.33317V4.66687C9.33317 3.97887 9.38917 3.54554 10.3752 3.54554H11.6205V1.42554C11.0145 1.36287 10.4052 1.3322 9.79517 1.33354C7.9865 1.33354 6.6665 2.4382 6.6665 4.4662V6.0002H4.6665V8.66687L6.6665 8.6662V14.6669H9.33317V8.66487L11.3772 8.66421L11.6832 6.0002Z" />
        </svg>
    );
}

export function createTwitterIcon() {
    return (
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M11.5237 8.77566L17.4811 2H16.0699L10.8949 7.88201L6.7648 2H2L8.24693 10.8955L2 17.9999H3.4112L8.87253 11.787L13.2352 17.9999H18M3.92053 3.04126H6.08853L16.0688 17.0098H13.9003"
                fill="#272F3F"
            />
        </svg>
    );
}
