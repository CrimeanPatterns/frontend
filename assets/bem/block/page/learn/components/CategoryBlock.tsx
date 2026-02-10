import { bem } from '@Bem/ts/service/bem';
import { pageName } from '../consts';
import { Post } from '../types/post';
import React from 'dom-chef';
import { createPostElement } from './PostElement';

export async function createCategoryBlock(
    posts: Post[],
    title: string,
    more?: {
        link: string;
        text: string;
    },
    nextPage?: string,
) {
    const postsElements = await Promise.all(
        posts.map(async (post) => {
            return await createPostElement(post);
        }),
    );
    return (
        <>
            <div className={bem(pageName, 'category-title-block')}>
                <h3 className={bem(pageName, 'category-title')}>{title}</h3>
                {more && (
                    <a
                        href={more.link}
                        className={`${bem(pageName, 'category-link', ['desktop'])} ${bem(pageName, 'read-more-link')}`}
                    >
                        {more.text}
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                fillRule="evenodd"
                                clipRule="evenodd"
                                d="M0.909091 0H9.09091C9.59299 0 10 0.407014 10 0.909091V9.09091H8.18182V3.10383L1.85914 9.42651L0.573487 8.14087L6.89617 1.81818H0.909091V0Z"
                                fill="#0168CA"
                            />
                        </svg>
                    </a>
                )}
            </div>
            <div className={bem(pageName, 'category-posts')} id={nextPage ? 'infinity-content' : undefined}>
                {postsElements}
            </div>
            {more && (
                <a
                    href={more.link}
                    className={`${bem(pageName, 'category-link', ['mobile'])} ${bem(pageName, 'read-more-link')}`}
                >
                    {more.text}
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M0.909091 0H9.09091C9.59299 0 10 0.407014 10 0.909091V9.09091H8.18182V3.10383L1.85914 9.42651L0.573487 8.14087L6.89617 1.81818H0.909091V0Z"
                            fill="#0168CA"
                        />
                    </svg>
                </a>
            )}
            {nextPage && (
                <>
                    <p id="infinity-loader" className={bem(pageName, 'infinity-loader')} style={{ display: 'none' }}>
                        <svg className="spinner" viewBox="0 0 50 50">
                            <circle className="path" cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle>
                        </svg>
                    </p>
                    <div
                        id="infinity-observer-target"
                        className={bem(pageName, 'observer-target')}
                        style={{ height: '10px', background: 'transparent' }}
                    ></div>
                </>
            )}
        </>
    );
}
