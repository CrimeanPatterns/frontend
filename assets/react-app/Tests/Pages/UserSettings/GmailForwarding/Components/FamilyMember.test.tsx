import { fireEvent, render, screen } from '../../../../TestUtils';

import * as familyMember from '@Root/Pages/UserSettings/GmailForwarding/Context/FamilyMemberContext';
import * as filterMeta from '@Root/Pages/UserSettings/GmailForwarding/Context/FiltersMetaContext';
import { FamilyMember } from '@Root/Pages/UserSettings/GmailForwarding/Components/FamilyMember';
import { FamilyMembers } from '@Root/Pages/UserSettings/GmailForwarding/Context/FamilyMemberContext';
import { Translator } from '@Services/Translator';
import { act } from 'react-dom/test-utils';
import React from 'react';

describe('FamilyMember', () => {
    beforeAll(() => {
        jest.spyOn(console, 'warn').mockImplementation(() => {});
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

        render(<FamilyMember />);

        const familyMemberBlock = screen.getByText(Translator.trans('gmail.filter.awardwallet.user'));

        expect(familyMemberBlock).toBeInTheDocument();
    });

    test("shouldn't render when amount of family member equals or less then 1", () => {
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

        render(<FamilyMember />);

        const familyMemberBlock = screen.queryByText(Translator.trans('gmail.filter.awardwallet.user'));

        expect(familyMemberBlock).not.toBeInTheDocument();
    });

    test('should change selected family member', () => {
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
        let selectedFamilyMember: FamilyMembers | null = null;

        jest.spyOn(familyMember, 'useFamilyMember').mockImplementation(() => ({
            selectedFamilyMember,
            setSelectedFamilyMember: jest.fn((familyMember: FamilyMembers | null) => {
                selectedFamilyMember = familyMember;
            }),
            alternativeAddress: '',
            setAlternativeAddress: jest.fn(),
        }));

        render(<FamilyMember />);

        const dropdownAnchor = screen.getByRole('button');

        act(() => {
            fireEvent.click(dropdownAnchor);
        });

        const secondOption = screen.getByText(/Label2/i);

        act(() => {
            fireEvent.click(secondOption);
        });

        expect(selectedFamilyMember).toEqual({
            key: '2',
            label: 'Label2',
            value: 'Alias2',
        });
    });

    test('should show alternative input, after checking switcher', () => {
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

        render(<FamilyMember />);

        const switcher = screen.getByText(Translator.trans('gmail.filter.want.specify.alternate.address'));

        act(() => {
            fireEvent.click(switcher);
        });

        const alternativeAddress = document.querySelector('.alternativeAddressOpen');

        expect(alternativeAddress).toBeInTheDocument();
    });
});
