export const useInitialData = () => {
    const contentElement = document.getElementById('content') as HTMLElement;

    const showWarningPopup = JSON.parse(contentElement.dataset.showWarningPopup || 'false') as boolean;

    const businessText1 = contentElement.dataset.businessText1;

    const businessText3 = contentElement.dataset.businessText3;

    const isBusinessArea = contentElement.dataset.isBusinessArea === 'true' ? true : false;

    return { showWarningPopup, businessText3, businessText1, isBusinessArea };
};
