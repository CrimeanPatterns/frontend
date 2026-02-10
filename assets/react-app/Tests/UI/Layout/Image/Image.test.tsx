import { fireEvent, render, screen } from '../../../TestUtils';
import React from 'react';
// eslint-disable-next-line sort-imports-es6-autofix/sort-imports-es6
import { Image, ImageProps } from '@UI/Layout';
import { ModalItem, ModalPriority } from '@Root/Contexts/ModalManagerContext';
import { Translator } from '@Services/Translator';
import { act } from 'react-dom/test-utils';

describe('Image', () => {
    beforeAll(() => {
        jest.spyOn(console, 'warn').mockImplementation(() => {});
    });

    test('should render', () => {
        render(
            <Image
                src="src"
                alt="Alt"
                srcSet="srcSet"
                classes={{
                    container: 'ExternalClassContainer',
                    img: 'ExternalClassImg',
                    loadingContainer: 'ExternalClassLoadingContainer',
                }}
            />,
        );

        const image = screen.getByAltText('Alt');

        expect(image).toBeInTheDocument();

        expect(image).toHaveAttribute('src', 'src');
        expect(image).toHaveAttribute('alt', 'Alt');
        expect(image).toHaveAttribute('srcSet', 'srcSet');

        expect(image).toHaveClass('ExternalClassImg');

        const container = image.parentElement?.parentElement;

        expect(container).toBeInTheDocument();

        expect(container).toHaveClass('ExternalClassLoadingContainer');
    });

    test('should show image after loading', () => {
        jest.spyOn(React, 'useState')
            .mockImplementationOnce((x?: unknown) => [x, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [true, () => null])
            .mockImplementation((x?: unknown) => [x, () => null]);

        render(
            <Image
                src="src"
                alt="Alt"
                classes={{
                    loadingContainer: 'ExternalClassLoadingContainer',
                }}
            />,
        );

        const image = screen.getByAltText('Alt');

        expect(image).toBeInTheDocument();

        const container = image.parentElement;

        expect(container).toBeInTheDocument();
    });

    test('should show error', () => {
        jest.spyOn(React, 'useState')
            .mockImplementationOnce((x?: unknown) => [x, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [true, () => null])
            .mockImplementation((x?: unknown) => [x, () => null]);

        render(
            <Image
                src="src"
                alt="Alt"
                classes={{
                    errorContainer: 'ExternalClassErrorContainer',
                }}
            />,
        );

        const image = screen.queryByAltText('Alt');

        expect(image).not.toBeInTheDocument();

        const errorContainer = screen.queryByText(Translator.trans('alerts.loading-error'));

        expect(errorContainer).toBeInTheDocument();
        expect(errorContainer).toHaveClass('ExternalClassErrorContainer');
    });

    test('should open preview', () => {
        const setPreviewState = jest.fn();

        jest.spyOn(React, 'useState')
            .mockImplementationOnce((x?: unknown) => [x, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [false, setPreviewState])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [true, () => null])
            .mockImplementation((x?: unknown) => [x, () => null]);

        render(<Image src="src" alt="Alt" preview />);

        const button = screen.getByRole('button');

        act(() => {
            fireEvent.click(button);
        });

        expect(setPreviewState).toHaveBeenCalledTimes(1);
    });

    test('should render preview', () => {
        const modals: ModalItem<ImageProps>[] = [];
        jest.spyOn(React, 'useState')
            .mockImplementationOnce(() => [modals, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [true, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [true, () => null])
            .mockImplementation((x?: unknown) => [x, () => null]);

        const { rerender } = render(<Image src="src" alt="Alt" preview />);

        modals.push({
            modalComponent: Image,
            id: '2',
            props: {
                src: '123',
                alt: 'Alt Preview',
            },
            priority: ModalPriority.Low,
        });

        jest.spyOn(React, 'useState')
            .mockImplementationOnce(() => [modals, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [false, () => null])
            .mockImplementationOnce(() => [true, () => null])
            .mockImplementation((x?: unknown) => [x, () => null]);

        rerender(<Image src="src" alt="Alt" preview />);

        const preview = screen.getByAltText('Alt Preview');

        expect(preview).toBeInTheDocument();
    });

    test('should render button with specified icon type and callback', () => {
        const actionCallback = jest.fn();

        render(<Image src="src" alt="Alt" actionIconType="ArrowDown" actionCallback={actionCallback} />);

        const button = screen.getByRole('button');

        const svgElement = button.querySelector('svg');

        act(() => {
            fireEvent.click(button);
        });

        expect(actionCallback).toHaveBeenCalledTimes(1);
        expect(svgElement).toBeInTheDocument();
    });

    test('should render button with specified element', () => {
        const actionCallback = jest.fn();

        const customElementValue = 'custom value';

        render(
            <Image
                src="src"
                alt="Alt"
                actionElement={<div>{customElementValue}</div>}
                actionCallback={actionCallback}
            />,
        );

        const customElement = screen.getByText(customElementValue);

        act(() => {
            fireEvent.click(customElement);
        });

        expect(actionCallback).toHaveBeenCalledTimes(1);
        expect(customElement).toBeInTheDocument();
    });
});
