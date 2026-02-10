import '../../../scss/_reset.scss';
import '../../spinner/index';
import 'swiper/css';
import '../../button';
import './learn.scss';
import './offered-cards/offered-cards';
import { BlogPostInfiniteScroll } from './controllers/BlogPostInfiniteScroll';
import { LearnHeaderController } from './controllers/header/LearnHeaderController';
import { LatestNewsController } from './controllers/LatestNewsController';
import { AuthorsController } from './controllers/AuthorsController';
import { FavoritePostsController } from './controllers/FavoritePostsController';
import { LabelController } from './controllers/LabelController';
import onReady from '@Bem/ts/service/on-ready';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';
import { ReadPostsController } from './controllers/ReadPostsController';
import { SearchController } from './controllers/SearchController';
import { ConnectedPostsController } from './controllers/ConnectedPostsController';
import { PostMobileMenuController } from './controllers/PostMobileMenuController';
import { RecommendedPostsController } from './controllers/RecommendedPostsController';

new LearnHeaderController();

const authorsController = new AuthorsController();
authorsController.initAuthorsPopover();

//@ts-expect-error The value is set in learn.twig
if (!window.isSearchPage) {
    const latestNewsController = new LatestNewsController();
    const blogPostInfinityScroll = new BlogPostInfiniteScroll();
    const favoritePostsController = new FavoritePostsController();
    const readPostsController = new ReadPostsController(latestNewsController, blogPostInfinityScroll);
    const connectedPostController = new ConnectedPostsController();
    const postMobileMenuController = new PostMobileMenuController();

    const labelController = new LabelController(authorsController, blogPostInfinityScroll);
    labelController.init();

    setupGlobalEventListeners(
        labelController,
        favoritePostsController,
        readPostsController,
        connectedPostController,
        postMobileMenuController,
    );
} else {
    const searchController = new SearchController();
    searchController.init();
}

function setupGlobalEventListeners(
    labelController: LabelController,
    favoritePostsController: FavoritePostsController,
    readPostsController: ReadPostsController,
    connectedPostsController: ConnectedPostsController,
    postMobileMenuController: PostMobileMenuController,
): void {
    const pageName = 'page-learn';
    const latestNewsSection = document.querySelector(`.${pageName}__latest-news`);
    const contentSection = document.querySelector(`.${pageName}__content-grid`);
    const recommendedOfferSection = document.querySelector<HTMLDivElement>(`.${pageName}__recommended-offer`);

    if (latestNewsSection) {
        latestNewsSection.addEventListener(
            'click',
            (e) => {
                labelController.handleReadMoreClickEvents(e);
                favoritePostsController.handleBookmarkClick(e);
                readPostsController.handleLearnedClick(e);
                postMobileMenuController.handleMobileMenuButtonClick(e);
            },
            { capture: true },
        );
    }

    if (recommendedOfferSection) {
        const recommendedController = new RecommendedPostsController(recommendedOfferSection);
        const recommendedPostMobileMenuController = new PostMobileMenuController(
            recommendedController.handleLearnedButtonClick,
        );

        recommendedOfferSection.addEventListener(
            'click',
            (e) => {
                favoritePostsController.handleBookmarkClick(e);
                connectedPostsController.handlePlusButtonClick(e);
                recommendedPostMobileMenuController.handleMobileMenuButtonClick(e);
                recommendedController.handleLearnedButtonClick(e);
            },
            { capture: true },
        );
    }

    if (contentSection) {
        contentSection.addEventListener(
            'click',
            (e) => {
                labelController.handleReadMoreClickEvents(e);
                favoritePostsController.handleBookmarkClick(e);
                readPostsController.handleLearnedClick(e);
                connectedPostsController.handlePlusButtonClick(e);
                postMobileMenuController.handleMobileMenuButtonClick(e);
            },
            { capture: true },
        );
    }
}

onReady(() => {
    hideGlobalLoader();
});
