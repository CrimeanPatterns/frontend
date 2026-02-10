import variables from '../Styles/Variables.module.scss';

export type BreakpointsDesignation = 'xsm' | 'sm' | 'md' | 'lg' | 'xl' | 'xxl' | 'xxxl' | 'xxxxl';
export type Breakpoints = { [key in BreakpointsDesignation]: number };

export interface Theme {
    regularTextFontFamily: string;
    textColor: {
        primary: string;
        secondary: string;
        disabled: string;
        hover: string;
    };
    iconColor: {
        primary: string;
        secondary: string;
        disabled: string;
        active: string;
    };
    backgroundColor: {
        primary: string;
        primaryHover: string;
        primaryActive: string;
        secondary: string;
        main: string;
    };
    borderColor: {
        secondary: string;
        active: string;
    };
    dividerColor: string;
    disabledOpacity: string;
    errorColor: {
        main: string;
    };
    warningColor: {
        main: string;
    };
    breakpoints: Breakpoints;
}

export const theme: Theme = {
    regularTextFontFamily: variables.regularTextFontFamily || '',
    textColor: {
        primary: variables.textColorPrimary || '',
        secondary: variables.textColorSecondary || '',
        disabled: variables.textColorDisabled || '',
        hover: variables.textColorHover || '',
    },
    iconColor: {
        primary: variables.iconColorPrimary || '',
        secondary: variables.iconColorSecondary || '',
        disabled: variables.iconColorDisabled || '',
        active: variables.iconColorActive || '',
    },
    backgroundColor: {
        primary: variables.backgroundColorPrimary || '',
        primaryHover: variables.backgroundColorPrimaryHover || '',
        primaryActive: variables.backgroundColorPrimaryActive || '',
        secondary: variables.backgroundColorSecondary || '',
        main: variables.backgroundColorMain || '',
    },
    borderColor: {
        secondary: variables.borderColorSecondary || '',
        active: variables.borderColorActive || '',
    },
    dividerColor: variables.dividerColor || '',
    disabledOpacity: variables.disabledOpacity || '',
    errorColor: {
        main: variables.errorColor || '',
    },
    warningColor: {
        main: variables.warningColor || '',
    },
    breakpoints: {
        xsm: 320,
        sm: 576,
        md: 768,
        lg: 1024,
        xl: 1200,
        xxl: 1400,
        xxxl: 1600,
        xxxxl: 1920,
    },
};
