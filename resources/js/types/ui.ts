export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type ToastAction = {
    label: string;
    onClick: () => void;
};

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
    description?: string;
    action?: ToastAction;
};
