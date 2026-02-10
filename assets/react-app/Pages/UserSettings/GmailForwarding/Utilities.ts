import { AxiosError } from 'axios';
import { axios } from '@Services/Axios';
import { toast } from '@Utilities/Toast';

export function getFilesNames(filesCount: number | undefined) {
    if (!filesCount) {
        return '';
    }

    let filesString = '';

    for (let i = 1; i <= filesCount; i++) {
        filesString += `gmailFilter${i}.xml`;

        if (i !== filesCount && i !== filesCount - 1) {
            filesString += ', ';
        }
        if (i === filesCount - 1) {
            filesString += ' and ';
        }
    }

    return filesString;
}

export async function handleDownloadClick(event: MouseEvent) {
    event.preventDefault();

    const link = event.currentTarget as HTMLAnchorElement;
    const url = link.getAttribute('href');
    const filename = link.getAttribute('download');

    try {
        if (!url || !filename) {
            throw new Error("Url doesn't exist");
        }

        const response = await axios.get(url, { responseType: 'blob' });

        const blob = new Blob([response.data]);

        const blobUrl = URL.createObjectURL(blob);

        const tempLink = document.createElement('a');
        tempLink.href = blobUrl;
        tempLink.download = filename;

        document.body.appendChild(tempLink);

        tempLink.click();

        URL.revokeObjectURL(blobUrl);
        document.body.removeChild(tempLink);
    } catch (error) {
        if ((error as AxiosError).response?.status === 404) {
            toast('File not found', {
                type: 'error',
                toastId: 'download',
            });
            return;
        }

        toast((error as AxiosError).message, {
            type: 'error',
            toastId: 'download',
        });
    }
}
