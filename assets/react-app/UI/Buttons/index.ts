export * from './PrimaryButton';
export * from './IconButton';
export * from './SecondaryButton';
export * from './TextButton';

export type ButtonProps<P> = P & {
    onClick?: () => void;
    className?: string;
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
};
