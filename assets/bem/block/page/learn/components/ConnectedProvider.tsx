import { bem } from '@Bem/ts/service/bem';
import classNames from 'classnames';
import React from 'dom-chef';

export async function createConnectedProvider({
    connectLink,
    displayName,
    logo,
}: {
    connectLink: string;
    displayName: string;
    logo: string;
}) {
    const logoPath = (await import(`@Bem/images/favicons/${logo.split('/')[1]}`)).default;

    const connectedProviderElement = (
        <div className={bem('connected-provider')}>
            <img src={logoPath} className={bem('connected-provider', 'logo')} />
            <h4 className={bem('connected-provider', 'name')}>{displayName}</h4>
            <a
                href={connectLink}
                className={classNames(bem('button', undefined, ['medium']), bem('connected-provider', 'button'))}
            >
                Connect Account
            </a>
        </div>
    );
    return connectedProviderElement as unknown as HTMLDivElement;
}
