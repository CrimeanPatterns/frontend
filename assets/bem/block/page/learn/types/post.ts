import { CreditCardAccount, ExpirationAccount } from './account';

export type PostAuthorOrReviewer = {
    avatar: string;
    link: string;
    name: string;
};

export type ConnectedPost = {
    description: string;
    link: string;
    title: string;
};

export type Post = {
    authors: {
        count: number;
        list: PostAuthorOrReviewer[];
    };
    commentsCount: number;
    id: number;
    link: string;
    dateAgo: string;
    supporting?: ConnectedPost[];
    account?: {
        balance: string;
        expirationDate: string;
        expirationDateShort: string;
        expirationState: null | 'far' | 'soon' | 'expired';
        link: string;
        logo: string;
        owner: string;
    };
    provider?: {
        connectLink: string;
        displayName: string;
        logo: string;
    };
    meta?: {
        expiring?: ExpirationAccount[];
        subAccount?: CreditCardAccount[];
    };
    pubDate: string;
    reviewed: PostAuthorOrReviewer | [];
    thumbnail: string;
    title: string;
    isFavorite: boolean;
};

export type LabelPostsData = {
    title: string;
    more?: {
        link: string;
        text: string;
    };
    posts: Post[];
    nextPage?: string;
};

export type InitialData = {
    recommendedOffer: {
        title: string;
        posts: Post[];
    };
    groups: {
        title: string;
        more: {
            link: string;
            text: string;
        };
        posts: Post[];
        nextPage?: string;
    }[];
};
