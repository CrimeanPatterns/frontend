import { bem } from '@Bem/ts/service/bem';
import { ExpirationAccount } from '../types/account';
import React from 'dom-chef';

export async function createExpiringAccount(account: ExpirationAccount): Promise<HTMLAnchorElement> {
    const logoPath = (await import(`@Bem/images/favicons/${account.logo.split('/')[1]}`)).default;

    const expiringAccountElement = (
        <a href={account.link} className={bem('expiring-account')}>
            <img src={logoPath} className={bem('expiring-account', 'logo')} />
            <span className={bem('expiring-account', 'owner')}>{account.owner}</span>
            <span className={bem('expiring-account', 'balance')}>{account.balance}</span>
            <div className={bem('expiring-account', 'status-block', [account.expirationState ?? ''])}>
                {account.expirationDate && account.expirationDateShort && (
                    <>
                        {' '}
                        <svg
                            className={bem('expiring-account', 'status-icon')}
                            viewBox="0 0 18 18"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M7.8 1.19995C7.64087 1.19995 7.48826 1.26317 7.37573 1.37569C7.26321 1.48821 7.2 1.64082 7.2 1.79995C7.2 4.83415 3 7.19995 3 11.4C3 14.5907 6.4581 16.6535 7.67578 16.7871C7.71663 16.7957 7.75826 16.8 7.8 16.8C7.95913 16.8 8.11174 16.7367 8.22426 16.6242C8.33678 16.5117 8.4 16.3591 8.4 16.2C8.39995 16.1108 8.38005 16.0228 8.34174 15.9423C8.30343 15.8619 8.24767 15.7909 8.17851 15.7347V15.7335C7.63251 15.2907 6.6 14.0062 6.6 12.8332C6.6 10.9078 8.4 10.2 8.4 10.2C7.3734 13.104 10.7373 13.4548 11.4141 16.33H11.4152C11.4448 16.4631 11.5188 16.5822 11.6251 16.6676C11.7314 16.7531 11.8636 16.7997 12 16.8C12.1264 16.7996 12.2494 16.7595 12.3516 16.6851C12.3624 16.6773 12.3729 16.6691 12.3832 16.6605C12.4808 16.5971 15 14.931 15 11.4C15 9.95838 14.279 7.48225 13.7602 6.3855L13.759 6.38198L13.7578 6.37964C13.7136 6.26771 13.6368 6.17166 13.5373 6.10395C13.4379 6.03624 13.3203 6.00001 13.2 5.99995C13.0599 6.00008 12.9242 6.04926 12.8165 6.13894C12.7089 6.22863 12.636 6.35317 12.6105 6.49097V6.49331C12.6091 6.49987 12.3335 7.72376 11.4 8.39995C11.4 5.57297 9.35902 2.65292 8.28281 1.44487C8.26666 1.423 8.24904 1.40225 8.23008 1.38276C8.17426 1.32507 8.10744 1.27917 8.03357 1.24777C7.95969 1.21636 7.88027 1.2001 7.8 1.19995Z" />
                        </svg>
                        <span className={bem('expiring-account', 'expiration-date', ['desktop'])}>
                            {account.expirationDate}
                        </span>
                        <span className={bem('expiring-account', 'expiration-date', ['mobile'])}>
                            {account.expirationDateShort}
                        </span>
                    </>
                )}
            </div>
            <svg
                width="12"
                height="12"
                viewBox="0 0 12 12"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                className={bem('expiring-account', 'arrow-icon')}
            >
                <path
                    d="M6.74997 6L3.25 9.49997L4.25003 10.5L8.75003 6L4.25003 1.5L3.25 2.50003L6.74997 6Z"
                    fill="currentColor"
                />
            </svg>
        </a>
    );
    return expiringAccountElement as unknown as HTMLAnchorElement;
}
