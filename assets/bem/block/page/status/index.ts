import onReady from '@Bem/ts/service/on-ready';
import { DesktopExtensionInterface } from '@awardwallet/extension-client/dist/DesktopExtensionInterface';

async function handleExtensionInfoUpdate(): Promise<void> {
    const bridge = new DesktopExtensionInterface();

    window.extensionV3info = await bridge.getExtensionInfo();
}

onReady(handleExtensionInfoUpdate);