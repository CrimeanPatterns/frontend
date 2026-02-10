export const copyToClipboard = async (text: string): Promise<boolean> => {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (error) {
            return false;
        }
    } else {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        textarea.style.left = '-9999px';
        textarea.setAttribute('aria-hidden', 'true');
        document.body.appendChild(textarea);

        const activeElement = document.activeElement as HTMLElement | null;

        textarea.focus();
        textarea.select();

        let isSuccessful = false;

        try {
            document.execCommand('copy');
            isSuccessful = true;
        } catch (error) {
            isSuccessful = false;
        }
        document.body.removeChild(textarea);

        if (activeElement) {
            activeElement.focus();
        }

        return isSuccessful;
    }
};
