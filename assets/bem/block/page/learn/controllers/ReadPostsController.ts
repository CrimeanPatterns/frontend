import { learnedService } from '../services/LearnedService';

export class ReadPostsController {
    constructor(
        private latestNewsController: any,
        private blogPostInfinityScroll: any,
    ) {
        learnedService.setDependencies(this.latestNewsController, this.blogPostInfinityScroll);
    }

    public handleLearnedClick(e: Event): void {
        const target = e.target as HTMLElement;
        const learnedButton = target.closest<HTMLButtonElement>('.post__learned-button');

        if (!learnedButton) {
            return;
        }

        e.stopPropagation();
        e.preventDefault();

        if (!learnedService.checkUserAuth()) {
            return;
        }

        const isLearned = learnedButton.classList.contains('post__learned-button--undo');
        const postId = learnedButton.getAttribute('data-post-id');

        if (!postId) {
            console.log('Post id not found');
            return;
        }

        learnedService.toggleLearned(learnedButton, postId, isLearned);
    }
}
