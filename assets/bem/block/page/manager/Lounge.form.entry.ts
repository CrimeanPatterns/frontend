import './Lounge.form.scss';
import onReady from '../../../ts/service/on-ready';

interface SourceInterface {
    ChangeDate?: string;
    Property?: string;
    OldVal?: string;
    NewVal?: string;
}

onReady(() => {
    const openingHours = document.getElementById('lounge_openingHours');

    if (openingHours) {
        const html = `
            <div class="opening-hours-actions">
                <span class="status"></span>
            </div>
        `;

        openingHours.insertAdjacentHTML('afterend', html);
        openingHours.addEventListener('input', function () {
            checkOpeningHours(this as HTMLTextAreaElement);
        });
        openingHours.style.height = 'auto';
        checkOpeningHours(openingHours as HTMLTextAreaElement);
        openingHours.style.height = (openingHours.scrollHeight).toString() + 'px';
    }

    function setState(state: string, isError = false) {
        $('.opening-hours-actions .status')
            .removeClass('success error')
            .addClass(isError ? 'error' : 'success')
            .text(state);
    }

    function checkOpeningHours(elem: HTMLTextAreaElement) {
        const ugly = elem.value;

        try {
            const obj: unknown = JSON.parse(ugly);

            elem.value = JSON.stringify(obj, null, 4);
            setState('Valid JSON');
        } catch (e) {
            if (ugly.indexOf('{') === 0) {
                setState('Invalid JSON', true);
            } else if (ugly.trim() === '') {
                setState('Empty');
            } else {
                setState('Raw Text');
            }
        }
    }

    const textareas = document.querySelectorAll('textarea:not(#lounge_openingHours)');

    function autoAdjustTextareaHeight(textarea: HTMLTextAreaElement) {
        textarea.style.height = 'auto'; // Сбросить высоту
        textarea.style.height = (textarea.scrollHeight).toString() + 'px';
    }

    textareas.forEach((textarea) => {
        textarea.addEventListener('input', () => {
            autoAdjustTextareaHeight(textarea as HTMLTextAreaElement);
        });

        autoAdjustTextareaHeight(textarea as HTMLTextAreaElement);
    });

    let preview: null | Window = null;
    const loungeChanges = document.querySelectorAll('a.lounge-changes');

    loungeChanges.forEach((elem) => {
        elem.addEventListener('click', (e) => {
            e.preventDefault();

            const dataChanges = elem.getAttribute('data-changes');

            if (!dataChanges) {
                return;
            }

            const data: SourceInterface[] = JSON.parse(dataChanges) as SourceInterface[];

            if (preview) {
                preview.close();
            }

            preview = window.open("", "Preview", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1024, height=600");
            let table = '<table style="width: 100%; border: 1px solid black">' +
                '<tr style="background-color: beige; font-size: 1.2em; font-family: monospace;"><td style="width: 15%; padding: 5px;">Change Date</td><td style="width: 15%; padding: 5px;">Property</td><td style="min-width: 100px; padding: 5px;">Old Value</td><td style="min-width: 100px; padding: 5px;">New Value</td></tr>';

            data.forEach(function(item) {
                table += '<tr style="font-size: small;">' +
                    '<td style="padding: 5px;">' + item.ChangeDate + '</td>' +
                    '<td style="padding: 5px;">' + item.Property + '</td>' +
                    '<td style="background-color: cornsilk; padding: 5px;">' + item.OldVal + '</td>' +
                    '<td style="background-color: greenyellow; padding: 5px;">' + item.NewVal + '</td>' +
                    '</tr>';
            });

            table += '</table>';

            if (preview) {
                preview.document.write(table);
                preview.focus();
            }
        });
    });
});