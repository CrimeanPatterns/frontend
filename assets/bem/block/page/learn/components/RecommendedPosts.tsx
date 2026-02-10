import { bem } from '@Bem/ts/service/bem';
import { pageName } from '../consts';
import { Post } from '../types/post';
import React from 'dom-chef';
import { createPostElement } from './PostElement';
import classNames from 'classnames';

export async function createRecommendedPosts(posts: Post[], title: string) {
    const firstPost = posts[0];
    let postElement: null | React.JSX.Element = null;
    if (firstPost) {
        postElement = await createPostElement(firstPost);
    }
    return (
        <>
            <div className={bem(pageName, 'category-title-block', ['recommended-offer'])}>
                <h3 className={bem(pageName, 'section-title')}>{title}</h3>
            </div>
            <div
                className={classNames(bem(pageName, 'category-posts'), bem(pageName, 'recommended-posts'))}
                data-recommended-posts={JSON.stringify(posts)}
            >
                {postElement}
            </div>
        </>
    );
}
