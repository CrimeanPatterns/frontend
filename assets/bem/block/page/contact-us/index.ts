import 'jquery-boot';
import 'translator-boot';
import 'pages/contactUs/main';
import 'lib/design';
import onReady from '@Bem/ts/service/on-ready';
import { DesktopExtensionInterface } from '@awardwallet/extension-client/dist/DesktopExtensionInterface';

const handleExtensionInfoUpdate = async () => {
    const bridge = new DesktopExtensionInterface();
    const response = await bridge.getExtensionInfo();

    const extensionV3infoInput = document.getElementById('contact_us_auth_v3') as HTMLInputElement | null;

    if (extensionV3infoInput) {
        extensionV3infoInput.value = response.version || '';
    }
};

const handleScrollToMessages = (): void => {
    const message = document.querySelector<HTMLElement>('.success-message');
    const errors = document.querySelectorAll<HTMLElement>('#contactUsForm .error');
    const header = document.querySelector<HTMLElement>('header');
    const headerHeight = header ? header.offsetHeight : 0;
    let top = 0;

    if (errors.length > 0) {
        const firstError = errors[0];
        top = firstError ? firstError.getBoundingClientRect().top + window.scrollY - headerHeight : 0;
    } else if (message) {
        top = message.getBoundingClientRect().top + window.scrollY - headerHeight;
    }

    if (top > 0) {
        window.scrollTo({
            top: top - 5,
            behavior: 'smooth',
        });
    }
};

const initContactUsPage = async () => {
    handleScrollToMessages();
    await handleExtensionInfoUpdate();
};

onReady(initContactUsPage);
