import { favoriteService } from '../services/FavoriteService';

export class FavoritePostsController {
    public handleBookmarkClick(e: Event): void {
        const target = e.target as HTMLElement;
        const bookmarkButton = target.closest('.post__bookmark');

        if (!bookmarkButton) {
            return;
        }

        e.stopPropagation();
        e.preventDefault();

        if (!favoriteService.checkUserAuth()) {
            return;
        }

        const isFavorite = bookmarkButton.classList.contains('post__bookmark--checked');
        const postId = bookmarkButton.getAttribute('data-post-id');

        if (!postId) {
            console.log('Post id not found');
            return;
        }

        favoriteService.toggleFavorite(bookmarkButton, postId, isFavorite);
    }
}
