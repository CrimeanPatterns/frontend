import axios from '@Bem/ts/service/axios';
import { AxiosError, isCancel } from 'axios';
import { showErrorToast } from '@Bem/ts/service/toast/toast';
import { InitialData, LabelPostsData } from '../types/post';
import RouterService from '@Bem/ts/service/router';

let controller: AbortController | null = null;

export async function loadPosts(): Promise<LabelPostsData | null> {
    if (controller) {
        controller.abort();
    }

    controller = new AbortController();

    try {
        const path = document.location.pathname;
        const response = (await axios.post<LabelPostsData>(path, {}, { signal: controller.signal })).data;
        return response;
    } catch (error) {
        if (isCancel(error)) return null;

        if (error instanceof AxiosError) {
            showErrorToast(error.message);
        }
        return null;
    }
}

export async function loadInitialData(): Promise<InitialData | null> {
    if (controller) {
        controller.abort();
    }

    controller = new AbortController();

    try {
        const path = document.location.pathname;
        const response = (await axios.post<InitialData>(path, {}, { signal: controller.signal })).data;
        return response;
    } catch (error) {
        if (isCancel(error)) return null;

        if (error instanceof AxiosError) {
            showErrorToast(error.message);
        }
        return null;
    }
}

export interface ApiResponse {
    success: boolean;
    message?: string;
}

export async function addPostToFavorite(postId: string): Promise<ApiResponse> {
    const response = await axios.put<ApiResponse>(
        RouterService.generate('aw_blog_learn_favorite', {
            id: postId,
        }),
    );

    return response.data;
}

export async function removePostFromFavorite(postId: string): Promise<ApiResponse> {
    const response = await axios.delete<ApiResponse>(
        RouterService.generate('aw_blog_learn_favorite', {
            id: postId,
        }),
    );

    return response.data;
}

export async function addPostToLearned(postId: string): Promise<ApiResponse> {
    const response = await axios.put<ApiResponse>(
        RouterService.generate('aw_blog_learn_read', {
            id: postId,
        }),
    );

    return response.data;
}

export async function removePostFromLearned(postId: string): Promise<ApiResponse> {
    const response = await axios.delete<ApiResponse>(
        RouterService.generate('aw_blog_learn_read', {
            id: postId,
        }),
    );

    return response.data;
}
