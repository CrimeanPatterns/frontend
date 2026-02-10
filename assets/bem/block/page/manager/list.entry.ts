import './list.scss';
import onReady from '../../../ts/service/on-ready';

interface ListConfigInterface {
    page: number;
    pageSize: number;
    sort1: string;
    sort1Direction: string;
}

function getPageParams(): ListConfigInterface {
    const list = document.getElementById('entity-list');
    const params = list?.dataset;

    if (!params) {
        throw new Error('No params');
    }

    if (!params.configPage) {
        throw new Error('No page param');
    }

    if (!params.configPageSize) {
        throw new Error('No pageSize param');
    }

    if (!params.configSort1Field) {
        throw new Error('No sort1 param');
    }

    if (!params.configSort1Direction) {
        throw new Error('No sort1Direction param');
    }

    return {
        page: parseInt(params.configPage),
        pageSize: parseInt(params.configPageSize),
        sort1: params.configSort1Field,
        sort1Direction: params.configSort1Direction,
    };
}

function reloadPage(config: ListConfigInterface): void {
    window.location.href =
        `?page=${config.page}&` +
        `pageSize=${config.pageSize}&` +
        `sort1=${config.sort1}&` +
        `sort1Direction=${config.sort1Direction}`;
}

onReady(() => {
    document.querySelectorAll('[data-set-page-size]').forEach((element) => {
        element.addEventListener('change', (event) => {
            const target = event.target as HTMLSelectElement;
            const pageSize = parseInt(target.value);
            const params = getPageParams();

            reloadPage({
                ...params,
                page: 1,
                pageSize,
            });
        });
    });

    document.querySelectorAll('[data-set-page]').forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();

            const target = event.target as HTMLAnchorElement;
            const setPage = target.dataset.setPage;

            if (!setPage) {
                throw new Error('No page param');
            }

            const page = parseInt(setPage);
            const params = getPageParams();

            reloadPage({
                ...params,
                page,
            });
        });
    });

    document.querySelectorAll('[data-sort-field]').forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();

            const target = event.target as HTMLAnchorElement;
            const sortField = target.dataset.sortField;
            const sortDirection = target.dataset.sortDirection;

            if (!sortField) {
                throw new Error('No sortField param');
            }

            if (!sortDirection) {
                throw new Error('No sortDirection param');
            }

            const params = getPageParams();

            reloadPage({
                ...params,
                page: 1,
                sort1: sortField,
                sort1Direction: sortDirection,
            });
        });
    });
});