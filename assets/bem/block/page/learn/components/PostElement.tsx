import { bem } from '@Bem/ts/service/bem';
import { ConnectedPost, Post } from '../types/post';
import React from 'dom-chef';
import { Translator } from '@Services/Translator';
import { CreditCardAccount, ExpirationAccount } from '../types/account';
import { createExpiringAccount } from './ExpiringAccount';
import { createCreditCardAccount } from './CreditCardAccount';
import { createConnectedProvider } from './ConnectedProvider';
import { createConnectedPost } from './ConnectedPost';
import {
    createBookmarkIcon,
    createCalendarIcon,
    createCommentsIcon,
    createDoubleCheckIcon,
    createFacebookIcon,
    createTwitterIcon,
} from './Icons';

export async function createPostElement(post: Post) {
    const isAdditionalBlocks = typeof post.meta !== 'undefined' || typeof post.supporting !== 'undefined';
    const bookmarkModifiers = post.isFavorite ? ['checked'] : [];

    const postElement = (
        <div className={bem('post', undefined, [isAdditionalBlocks ? 'addition' : 'large'])} data-post-id={post.id}>
            <a href={post.link} className={bem('post', 'link')}></a>
            <button
                type="button"
                aria-label="Bookmark"
                className={bem('post', 'bookmark', bookmarkModifiers)}
                data-post-id={post.id}
            >
                <div className={bem('post', 'action-tooltip', ['favorite'])}>
                    {bookmarkModifiers.length === 0 ? 'Add to "Favorites"' : 'Remove from "Favorites"'}
                </div>
                {createBookmarkIcon(post.isFavorite)}
            </button>

            <button
                type="button"
                aria-label="Add to learned"
                className={bem('post', 'learned-button')}
                data-post-id={post.id}
            >
                <div className={bem('post', 'action-tooltip', ['learn'])}>Mark as learned</div>
                <div>{createDoubleCheckIcon()}</div>
            </button>

            <img src={post.thumbnail} alt={post.title} className={bem('post', 'preview')} loading="lazy" />

            <h3 className={bem('post', 'title')}>{post.title}</h3>

            <div className={bem('post', 'meta-info')}>
                <div className={bem('post', 'release-date', ['desktop'])}>
                    {createCalendarIcon()}
                    {post.pubDate}
                </div>
                <span className={bem('post', 'release-date', ['mobile'])}>{post.dateAgo}</span>
                <div className={bem('post', 'author-names-wrapper', ['large'])}>
                    <div className={bem('post', 'author-names')} data-authors={JSON.stringify(post.authors.list)}>
                        <span className={bem('post', 'author-description')}>by&nbsp;</span>
                        {post.authors.list.map((author) => (
                            <a href={author.link} key={author.name} className={bem('post', 'author-link')}>
                                {author.name}
                            </a>
                        ))}
                    </div>
                </div>
                <div className={bem('post', 'comments')}>
                    {createCommentsIcon()}
                    {post.commentsCount}
                </div>
                <div className={bem('post', 'social-links')}>
                    <a
                        href={`https://facebook.com/sharer/sharer.php?u=${encodeURIComponent(post.link)}`}
                        className={bem('post', 'social-link')}
                    >
                        {createFacebookIcon()}
                    </a>
                    <a
                        href={`https://twitter.com/sharer/sharer.php?u=${encodeURIComponent(post.link)}`}
                        className={bem('post', 'social-link')}
                    >
                        {createTwitterIcon()}
                    </a>
                </div>
            </div>
            <div className={bem('post', 'authors')}>
                <div className={bem('post', 'authors-img-block')}>
                    {post.authors.list.map((author, index) => (
                        <span
                            style={{ zIndex: post.authors.list.length - index }}
                            className={bem('post', 'authors-img-wrapper')}
                        >
                            <img
                                className={bem('post', 'author-img')}
                                src={author.avatar}
                                alt={author.name}
                                loading="lazy"
                            />
                        </span>
                    ))}
                </div>
                <div className={bem('post', 'author-names-wrapper')}>
                    <div className={bem('post', 'author-names')} data-authors={JSON.stringify(post.authors.list)}>
                        <span className={bem('post', 'author-description')}>by&nbsp;</span>
                        {post.authors.list.map((author) => (
                            <a href={author.link} key={author.name} className={bem('post', 'author-link')}>
                                {author.name}
                            </a>
                        ))}
                    </div>
                </div>
            </div>
            <div className={bem('post', 'reviewer-wrapper', [isAdditionalBlocks ? 'addition' : ''])}>
                <div className={bem('post', 'reviewer')}>
                    {!Array.isArray(post.reviewed) && (
                        <>
                            <div className={bem('post', 'img-block')}>
                                <img
                                    src={post.reviewed.avatar}
                                    className={bem('post', 'reviewer-img')}
                                    alt={post.reviewed.name}
                                    loading="lazy"
                                />
                                <svg
                                    className={bem('post', 'reviewer-img-icon')}
                                    width="19"
                                    height="20"
                                    viewBox="0 0 19 20"
                                >
                                    <path
                                        d="M9.88217 1.81469L9.57746 1.67921L9.27276 1.81469L2.52538 4.81468L2.08008 5.01267V5.5V8.13475C2.08008 12.7065 5.00453 16.7657 9.34021 18.2115L9.57746 18.2906L9.81472 18.2115C14.1504 16.7657 17.0749 12.7065 17.0749 8.13475V5.5V5.01267L16.6296 4.81468L9.88217 1.81469Z"
                                        fill="#0168CA"
                                        stroke="white"
                                        stroke-width="1.5"
                                    ></path>
                                    <path
                                        fill-rule="evenodd"
                                        clip-rule="evenodd"
                                        d="M8.74138 10.5475L11.7932 7L13.175 8.08943L8.95069 13L6.17773 10.6772L7.35656 9.38753L8.74138 10.5475Z"
                                        fill="white"
                                    ></path>
                                </svg>
                            </div>
                            <div className={bem('post', 'reviewer-text-block')}>
                                <div className={bem('post', 'reviewer-description')}>
                                    {Translator.trans('reviewed-by')}
                                </div>

                                <a href={post.reviewed.link} className={bem('post', 'reviewer-name')}>
                                    {post.reviewed.name}
                                </a>
                            </div>
                        </>
                    )}
                </div>
            </div>

            <div className={bem('post', 'mobile-menu')}>
                <button className={bem('post', 'mobile-menu-button')}>
                    <div></div>
                    <div></div>
                    <div></div>
                </button>
                <ul className={bem('post', 'mobile-menu-list')} data-post-id={post.id} data-post-link={post.link}>
                    <li className={bem('post', 'mobile-menu-item')}>
                        <button
                            className={bem('post', 'mobile-menu-item-button', ['share'])}
                            data-post-title={post.title}
                        >
                            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.9987 19.1666C4.54036 19.1666 4.148 19.0034 3.82161 18.677C3.49523 18.3506 3.33203 17.9583 3.33203 17.4999V8.33325C3.33203 7.87492 3.49523 7.48256 3.82161 7.15617C4.148 6.82978 4.54036 6.66658 4.9987 6.66658H7.4987V8.33325H4.9987V17.4999H14.9987V8.33325H12.4987V6.66658H14.9987C15.457 6.66658 15.8494 6.82978 16.1758 7.15617C16.5022 7.48256 16.6654 7.87492 16.6654 8.33325V17.4999C16.6654 17.9583 16.5022 18.3506 16.1758 18.677C15.8494 19.0034 15.457 19.1666 14.9987 19.1666H4.9987ZM9.16537 13.3333V4.02075L7.83203 5.35408L6.66536 4.16658L9.9987 0.833252L13.332 4.16658L12.1654 5.35408L10.832 4.02075V13.3333H9.16537Z"
                                    fill="currentColor"
                                />
                            </svg>
                            Share Post
                        </button>
                    </li>
                    <li className={bem('post', 'mobile-menu-item')}>
                        <button
                            className={bem('post', 'mobile-menu-item-button', [
                                'favorite',
                                post.isFavorite ? 'active' : '',
                            ])}
                        >
                            {createBookmarkIcon(post.isFavorite)}
                            <span>{post.isFavorite ? 'Remove from "Favorites"' : 'Save to "Favorites"'}</span>
                        </button>
                    </li>
                    <li className={bem('post', 'mobile-menu-item')}>
                        <button className={bem('post', 'mobile-menu-item-button', ['learned'])}>
                            {createDoubleCheckIcon()}
                            <span>Mark as "Learned"</span>
                        </button>
                    </li>
                    <li className={bem('post', 'mobile-menu-item')}>
                        <button className={bem('post', 'mobile-menu-item-button', ['copy-link'])}>
                            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M9.16797 14.1667H5.83464C4.68186 14.1667 3.69922 13.7605 2.88672 12.948C2.07422 12.1355 1.66797 11.1528 1.66797 10C1.66797 8.84726 2.07422 7.86462 2.88672 7.05212C3.69922 6.23962 4.68186 5.83337 5.83464 5.83337H9.16797V7.50004H5.83464C5.14019 7.50004 4.54991 7.7431 4.0638 8.22921C3.57769 8.71532 3.33464 9.3056 3.33464 10C3.33464 10.6945 3.57769 11.2848 4.0638 11.7709C4.54991 12.257 5.14019 12.5 5.83464 12.5H9.16797V14.1667ZM6.66797 10.8334V9.16671H13.3346V10.8334H6.66797ZM10.8346 14.1667V12.5H14.168C14.8624 12.5 15.4527 12.257 15.9388 11.7709C16.4249 11.2848 16.668 10.6945 16.668 10C16.668 9.3056 16.4249 8.71532 15.9388 8.22921C15.4527 7.7431 14.8624 7.50004 14.168 7.50004H10.8346V5.83337H14.168C15.3207 5.83337 16.3034 6.23962 17.1159 7.05212C17.9284 7.86462 18.3346 8.84726 18.3346 10C18.3346 11.1528 17.9284 12.1355 17.1159 12.948C16.3034 13.7605 15.3207 14.1667 14.168 14.1667H10.8346Z"
                                    fill="currentColor"
                                />
                            </svg>
                            Copy Link
                        </button>
                    </li>
                    <li className={bem('post', 'mobile-menu-item')}>
                        <a
                            href={`https://facebook.com/sharer/sharer.php?u=${encodeURIComponent(post.link)}`}
                            rel="noopener noreferrer"
                            className={bem('post', 'mobile-menu-item-link')}
                        >
                            {createFacebookIcon()}
                            Share on Facebook
                        </a>
                    </li>
                    <li className={bem('post', 'mobile-menu-item')}>
                        <a
                            href={`https://twitter.com/sharer/sharer.php?u=${encodeURIComponent(post.link)} `}
                            rel="noopener noreferrer"
                            className={bem('post', 'mobile-menu-item-link')}
                        >
                            {createTwitterIcon()}
                            Share on X
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    );

    if (isAdditionalBlocks) {
        const additionalBlocksWrapper = (
            <div className={bem('post', 'additional-block')}></div>
        ) as unknown as HTMLDivElement;

        if (post.meta?.expiring) {
            const expiringAccounts = await createExpiringElements(post.meta.expiring);

            for (let element of expiringAccounts) {
                additionalBlocksWrapper.append(element);
            }
        }

        if (post.meta?.subAccount) {
            const creditCardElements = await createCreditCardsElements(post.meta.subAccount);

            for (let element of creditCardElements) {
                additionalBlocksWrapper.append(element);
            }
        }

        if (post.supporting) {
            const ulElement = document.createElement('ul');
            ulElement.className = bem('post', 'connected-posts');
            const connectedPostElements = createConnectedPosts(post.supporting);
            for (const connectedPostElement of connectedPostElements) {
                ulElement.append(connectedPostElement);
            }

            if (post.account) {
                const expiringAccount = await createExpiringAccount({
                    ...post.account,
                    displayName: post.account.owner,
                    providerCode: '',
                    providerId: '',
                });

                additionalBlocksWrapper.append(expiringAccount);
                additionalBlocksWrapper.append(ulElement);
            }

            if (post.provider) {
                const connectProvider = await createConnectedProvider({
                    connectLink: post.provider.connectLink,
                    displayName: post.provider.displayName,
                    logo: post.provider.logo,
                });

                additionalBlocksWrapper.append(connectProvider);
                additionalBlocksWrapper.append(ulElement);
            }

            if (!post.provider && !post.account) {
                additionalBlocksWrapper.append(ulElement);
            }
        }

        postElement.append(additionalBlocksWrapper);
    }

    return postElement;
}

async function createExpiringElements(expiring: ExpirationAccount[]): Promise<HTMLAnchorElement[]> {
    const accountElements = [];

    for (const account of expiring) {
        const accountElement = await createExpiringAccount(account);
        accountElements.push(accountElement);
    }
    return accountElements;
}

async function createCreditCardsElements(creditCardAccounts: CreditCardAccount[]): Promise<HTMLAnchorElement[]> {
    const accountElements = [];

    for (const account of creditCardAccounts) {
        const accountElement = await createCreditCardAccount(account);
        accountElements.push(accountElement);
    }
    return accountElements;
}

function createConnectedPosts(connectedPosts: ConnectedPost[]): HTMLLIElement[] {
    const accountElements = [];

    for (const connectedPost of connectedPosts) {
        const accountElement = createConnectedPost(connectedPost);
        accountElements.push(accountElement);
    }

    return accountElements;
}
