import { bem } from '@Bem/ts/service/bem';
import React from 'dom-chef';
import { ConnectedPost } from '../types/post';

export function createConnectedPost(connectedPost: ConnectedPost) {
    const connectedPostElement = (
        <li className={bem('connected-post', 'item')}>
            <div className={bem('connected-post', 'header')}>
                <button className={bem('connected-post', 'plus')}></button>
                <h3 className={bem('connected-post', 'title')}>
                    <a href={connectedPost.link} className={bem('connected-post', 'link')}>
                        {connectedPost.title}
                    </a>
                </h3>
            </div>
            <div className={bem('connected-post', 'content')}>
                <a href={connectedPost.link} className={bem('connected-post', 'link')}>
                    {connectedPost.description}
                </a>
            </div>
        </li>
    );
    return connectedPostElement as unknown as HTMLLIElement;
}
