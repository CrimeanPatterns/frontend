import * as familyMember from '@Root/Pages/UserSettings/GmailForwarding/Context/FamilyMemberContext';
import * as filterMeta from '@Root/Pages/UserSettings/GmailForwarding/Context/FiltersMetaContext';
import { FamilyMembers } from '@Root/Pages/UserSettings/GmailForwarding/Context/FamilyMemberContext';
import { PageWrapper } from '@Root/Pages/UserSettings/GmailForwarding';
import { fireEvent, render, screen, waitFor } from '../../../TestUtils';
import React from 'react';

describe('GmailForwardingPage', () => {
    beforeAll(() => {
        jest.spyOn(console, 'warn').mockImplementation(() => {});

        const div = document.createElement('dev');
        div.setAttribute('id', 'content');
        div.setAttribute('data-userlogin', 'userlogin');

        document.body.append(div);
    });

    test('should render', () => {
        jest.spyOn(filterMeta, 'useFiltersMeta').mockImplementation(() => ({
            loading: false,
            filtersMeta: {
                familyMembers: [
                    {
                        key: '1',
                        label: 'Label1',
                        description: 'Account Owner',
                        value: 'Alias1',
                    },
                    {
                        key: '2',
                        label: 'Label2',
                        value: 'Alias2',
                    },
                ],
                listCount: 2,
            },
            error: null,
        }));
        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember: null,
            setSelectedFamilyMember: jest.fn(),
            alternativeAddress: '',
            setAlternativeAddress: jest.fn(),
        }));

        render(<PageWrapper />);

        const familyMemberBlock = screen.getByText('Gmail Travel Confirmation Email Forwarding Instructions.');

        expect(familyMemberBlock).toBeInTheDocument();
    });

    test('should render skeleton while loading', () => {
        jest.spyOn(filterMeta, 'useFiltersMeta').mockImplementation(() => ({
            loading: true,
            filtersMeta: {
                familyMembers: [],
                listCount: 2,
            },
            error: null,
        }));

        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember: null,
            setSelectedFamilyMember: jest.fn(),
            alternativeAddress: '',
            setAlternativeAddress: jest.fn(),
        }));

        render(<PageWrapper />);

        const skeletonPage = screen.queryByRole('page-loader');
        const familyMemberBlock = screen.queryByText('gmail.filter.title');

        expect(skeletonPage).toBeInTheDocument();
        expect(familyMemberBlock).not.toBeInTheDocument();
    });

    test('should render error page', () => {
        jest.spyOn(filterMeta, 'useFiltersMeta').mockImplementation(() => ({
            loading: false,
            filtersMeta: {
                familyMembers: [],
                listCount: 2,
            },
            error: new Error('Error!'),
        }));

        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember: null,
            setSelectedFamilyMember: jest.fn(),
            alternativeAddress: '',
            setAlternativeAddress: jest.fn(),
        }));

        render(<PageWrapper />);

        const errorPage = screen.getByText(/This page didn/i);

        expect(errorPage).toBeInTheDocument();
    });

    test('should display family members block', () => {
        jest.spyOn(filterMeta, 'useFiltersMeta').mockImplementation(() => ({
            loading: false,
            filtersMeta: {
                familyMembers: [
                    {
                        key: '1',
                        label: 'Label1',
                        description: 'Account Owner',
                        value: 'Alias1',
                    },
                    {
                        key: '2',
                        label: 'Label2',
                        value: 'Alias2',
                    },
                ],
                listCount: 2,
            },
            error: null,
        }));

        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember: null,
            setSelectedFamilyMember: jest.fn(),
            alternativeAddress: '',
            setAlternativeAddress: jest.fn(),
        }));

        render(<PageWrapper />);

        fireEvent.scroll(window, { target: { scrollY: 2200 } });

        const familyMemberBlock = document.body.querySelector('.familyMembers');
        expect(familyMemberBlock).toBeInTheDocument();
    });

    test("shouldn't display family members block", () => {
        jest.spyOn(filterMeta, 'useFiltersMeta').mockImplementation(() => ({
            loading: false,
            filtersMeta: {
                familyMembers: [
                    {
                        key: '1',
                        label: 'Label1',
                        description: 'Account Owner',
                        value: 'Alias1',
                    },
                ],
                listCount: 2,
            },
            error: null,
        }));

        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember: null,
            setSelectedFamilyMember: jest.fn(),
            alternativeAddress: '',
            setAlternativeAddress: jest.fn(),
        }));

        render(<PageWrapper />);

        fireEvent.scroll(window, { target: { scrollY: 2200 } });

        const familyMemberBlock = document.body.querySelector('.familyMembers');
        expect(familyMemberBlock).not.toBeInTheDocument();
    });

    test('should show email address for owner', async () => {
        jest.spyOn(filterMeta, 'useFiltersMeta').mockImplementation(() => ({
            loading: false,
            filtersMeta: {
                familyMembers: [
                    {
                        key: '1',
                        label: 'Label1',
                        description: 'Account Owner',
                        value: '',
                    },
                    {
                        key: '2',
                        label: 'Label2',
                        value: 'Alias2',
                    },
                ],
                listCount: 2,
            },
            error: null,
        }));

        let selectedFamilyMember: FamilyMembers | null = {
            key: '1',
            label: 'Label1',
            description: 'Account Owner',
            value: '',
        };

        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember,
            setSelectedFamilyMember: jest.fn((familyMember: FamilyMembers | null) => {
                selectedFamilyMember = familyMember;
            }),
            alternativeAddress: '',
            setAlternativeAddress: jest.fn(),
        }));

        render(<PageWrapper />);

        await waitFor(() => {
            const link = screen.getAllByText(/userlogin/i)[0];

            expect(link).toHaveTextContent('userlogin+f@email.AwardWallet.com');
        });
    });

    test('should show email address for family member', async () => {
        jest.spyOn(filterMeta, 'useFiltersMeta').mockImplementation(() => ({
            loading: false,
            filtersMeta: {
                familyMembers: [
                    {
                        key: '1',
                        label: 'Label1',
                        description: 'Account Owner',
                        value: '',
                    },
                    {
                        key: '2',
                        label: 'Label2',
                        value: 'Alias2',
                    },
                ],
                listCount: 2,
            },
            error: null,
        }));

        let selectedFamilyMember: FamilyMembers | null = {
            key: '2',
            label: 'Label2',
            value: 'Alias2',
        };

        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember,
            setSelectedFamilyMember: jest.fn((familyMember: FamilyMembers | null) => {
                selectedFamilyMember = familyMember;
            }),
            alternativeAddress: '',
            setAlternativeAddress: jest.fn(),
        }));

        render(<PageWrapper />);

        await waitFor(() => {
            const link = screen.getAllByText(/userlogin/i)[0];

            expect(link).toHaveTextContent('userlogin.Alias2+f@email.AwardWallet.com');
        });
    });

    test('should show file link with alternative address', () => {
        jest.spyOn(filterMeta, 'useFiltersMeta').mockImplementation(() => ({
            loading: false,
            filtersMeta: {
                familyMembers: [
                    {
                        key: '1',
                        label: 'Label1',
                        description: 'Account Owner',
                        value: '',
                    },
                    {
                        key: '2',
                        label: 'Label2',
                        value: 'Alias2',
                    },
                ],
                listCount: 2,
            },
            error: null,
        }));

        let selectedFamilyMember: FamilyMembers | null = {
            key: '2',
            label: 'Label2',
            value: 'Alias2',
        };

        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember,
            setSelectedFamilyMember: jest.fn((familyMember: FamilyMembers | null) => {
                selectedFamilyMember = familyMember;
            }),
            alternativeAddress: '123',
            setAlternativeAddress: jest.fn(),
        }));

        render(<PageWrapper />);

        const anchorElement1 = screen.getByRole('link', { name: 'gmailFilter1.xml' });
        const anchorElement2 = screen.getByRole('link', { name: 'gmailFilter2.xml' });

        expect(anchorElement1).toBeInTheDocument();
        expect(anchorElement2).toBeInTheDocument();

        expect(anchorElement1).toHaveAttribute('download', 'gmailFilter1.xml');
        expect(anchorElement2).toHaveAttribute('download', 'gmailFilter2.xml');

        expect(anchorElement1).toHaveAttribute('href', `/user/get-filter/0/Alias2?to=${encodeURIComponent(123)}`);
        expect(anchorElement2).toHaveAttribute('href', `/user/get-filter/1/Alias2?to=${encodeURIComponent(123)}`);
    });

    test('should show file link with alias', () => {
        jest.spyOn(filterMeta, 'useFiltersMeta').mockImplementation(() => ({
            loading: false,
            filtersMeta: {
                familyMembers: [
                    {
                        key: '1',
                        label: 'Label1',
                        description: 'Account Owner',
                        value: '',
                    },
                    {
                        key: '2',
                        label: 'Label2',
                        value: 'Alias2',
                    },
                ],
                listCount: 2,
            },
            error: null,
        }));

        let selectedFamilyMember: FamilyMembers | null = {
            key: '2',
            label: 'Label2',
            value: 'Alias2',
        };

        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember,
            setSelectedFamilyMember: jest.fn((familyMember: FamilyMembers | null) => {
                selectedFamilyMember = familyMember;
            }),
            alternativeAddress: '',
            setAlternativeAddress: jest.fn(),
        }));

        render(<PageWrapper />);

        const anchorElement1 = screen.getByRole('link', { name: 'gmailFilter1.xml' });
        const anchorElement2 = screen.getByRole('link', { name: 'gmailFilter2.xml' });

        expect(anchorElement1).toBeInTheDocument();
        expect(anchorElement2).toBeInTheDocument();

        expect(anchorElement1).toHaveAttribute('download', 'gmailFilter1.xml');
        expect(anchorElement2).toHaveAttribute('download', 'gmailFilter2.xml');

        expect(anchorElement1).toHaveAttribute('href', `/user/get-filter/0/Alias2`);
        expect(anchorElement2).toHaveAttribute('href', `/user/get-filter/1/Alias2`);
    });

    test('should show file link for account owner', () => {
        jest.spyOn(filterMeta, 'useFiltersMeta').mockImplementation(() => ({
            loading: false,
            filtersMeta: {
                familyMembers: [
                    {
                        key: '1',
                        label: 'Label1',
                        description: 'Account Owner',
                        value: '',
                    },
                    {
                        key: '2',
                        label: 'Label2',
                        value: 'Alias2',
                    },
                ],
                listCount: 2,
            },
            error: null,
        }));

        let selectedFamilyMember: FamilyMembers | null = {
            key: '1',
            label: 'Label1',
            description: 'Account Owner',
            value: '',
        };

        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember,
            setSelectedFamilyMember: jest.fn((familyMember: FamilyMembers | null) => {
                selectedFamilyMember = familyMember;
            }),
            alternativeAddress: '',
            setAlternativeAddress: jest.fn(),
        }));

        render(<PageWrapper />);

        const anchorElement1 = screen.getByRole('link', { name: 'gmailFilter1.xml' });
        const anchorElement2 = screen.getByRole('link', { name: 'gmailFilter2.xml' });

        expect(anchorElement1).toBeInTheDocument();
        expect(anchorElement2).toBeInTheDocument();

        expect(anchorElement1).toHaveAttribute('download', 'gmailFilter1.xml');
        expect(anchorElement2).toHaveAttribute('download', 'gmailFilter2.xml');

        expect(anchorElement1).toHaveAttribute('href', `/user/get-filter/0`);
        expect(anchorElement2).toHaveAttribute('href', `/user/get-filter/1`);
    });
});
