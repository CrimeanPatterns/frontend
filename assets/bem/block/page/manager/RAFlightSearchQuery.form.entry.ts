import Router from '../../../ts/service/router';
import onReady from '../../../ts/service/on-ready';

interface ParserListFormData {
    from?: string;
    to?: string;
    fromDate?: string;
    toDate?: string;
    flightClasses?: number;
    numberOfPassengers?: number;
    excludedParsers?: string[];
}

interface ParserListResponseRow {
    from: string;
    to: string;
    depDate: string;
    flightClass: string;
    passengers: number;
    excluded: string[];
    excludedAll: boolean;
}

function toggleParsersMultiselect(active: boolean): void {
    const parsersMultiselect = document.getElementById('ra_flight_search_query_parsers');

    if (!parsersMultiselect) {
        return;
    }

    if (active) {
        parsersMultiselect.removeAttribute('disabled');
    } else {
        parsersMultiselect.setAttribute('disabled', 'disabled');
    }
}

function toggleExcludeParsersMultiselect(active: boolean): void {
    const excludedParsersMultiselect = document.getElementById('ra_flight_search_query_excludeParsers');

    if (!excludedParsersMultiselect) {
        return;
    }

    if (active) {
        excludedParsersMultiselect.removeAttribute('disabled');
    } else {
        excludedParsersMultiselect.setAttribute('disabled', 'disabled');
    }
}

function toggleParsersTable(show: boolean): void {
    const parsersTable = document.getElementById('ra_flight_search_query_parsers_table');

    if (parsersTable) {
        if (show) {
            parsersTable.style.display = 'block';
        } else {
            parsersTable.style.display = 'none';
            parsersTable.remove();
        }
    } else {
        if (show) {
            const autoSelectParsers = document.querySelector<HTMLInputElement>('input[name="ra_flight_search_query[autoSelectParsers]"]');

            if (!autoSelectParsers) {
                return;
            }

            const parent = autoSelectParsers.closest('.form-group') as HTMLElement;
            const html = `
                <div id="ra_flight_search_query_parsers_table">
                    <p>
                        <a class="btn btn-sm" data-toggle="collapse" href="#collapseTable" role="button" aria-expanded="false" aria-controls="collapseTable">
                            Excluded Parsers
                        </a>
                    </p>
                    <div class="collapse" id="collapseTable">
                        
                    </div>
                </div>
            `;

            parent.insertAdjacentHTML('afterend', html);
        }
    }
}

function getFormData(): ParserListFormData {
    const form = document.querySelector<HTMLFormElement>('form[name="ra_flight_search_query"]');
    const data: ParserListFormData = {};

    if (form) {
        data.from = form.querySelector<HTMLInputElement>('input[name="ra_flight_search_query[fromAirports]"]')?.value;
        data.to = form.querySelector<HTMLInputElement>('input[name="ra_flight_search_query[toAirports]"]')?.value;
        data.fromDate = form.querySelector<HTMLInputElement>('input[name="ra_flight_search_query[fromDate]"]')?.value;
        data.toDate = form.querySelector<HTMLInputElement>('input[name="ra_flight_search_query[toDate]"]')?.value;
        data.flightClasses = parseInt(form.querySelector<HTMLInputElement>('select[name="ra_flight_search_query[flightClass]"]')?.value || '0');
        data.numberOfPassengers = parseInt(form.querySelector<HTMLInputElement>('select[name="ra_flight_search_query[adults]"]')?.value || '0');
        data.excludedParsers = Array.from(form.querySelectorAll<HTMLInputElement>('select[name="ra_flight_search_query[excludeParsers][]"] option:checked')).map((option) => option.value);
    }

    return data;
}

function isValidFormData(data: ParserListFormData): boolean {
    return data.from != null
        && data.from.length > 0
        && data.to != null
        && data.to.length > 0
        && data.fromDate != null
        && data.fromDate.length > 0
        && data.toDate != null
        && data.toDate.length > 0
        && data.flightClasses != null
        && data.numberOfPassengers != null;
}

function updateForm(): void {
    const autoSelectParsers = document.querySelector<HTMLInputElement>('input[name="ra_flight_search_query[autoSelectParsers]"]');

    if (!autoSelectParsers) {
        return;
    }

    const formData = getFormData();
    const isValid = isValidFormData(formData);

    if (!isValid) {
        autoSelectParsers.checked = false;
        autoSelectParsers.setAttribute('disabled', 'disabled');
    } else {
        autoSelectParsers.removeAttribute('disabled');
    }

    const autoSelectParsersChecked = autoSelectParsers.checked;

    if (autoSelectParsersChecked) {
        toggleParsersMultiselect(false);
        toggleExcludeParsersMultiselect(true);
        toggleParsersTable(true);

        const route = Router.generate('aw_enhanced_action', {
            schema: 'RAFlightSearchQuery',
            action: 'detect-parsers',
        });

        $.post(route, formData, (data: ParserListResponseRow[]) => {
            let table;

            if (data.length === 0) {
                table = `
                    <div class="alert alert-info" role="alert">
                        No excluded parsers found.
                    </div>
                `;
            } else {
                table = `
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-warning">
                            <tr>
                                <th>Route</th>
                                <th>DepDate</th>
                                <th>Flight Class</th>
                                <th>Passengers</th>
                                <th>Excluded</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map((row) => `
                                <tr ${row.excludedAll ? 'class="table-danger" title="All parsers are excluded"' : ''}>
                                    <td>${row.from} - ${row.to}</td>
                                    <td>${row.depDate}</td>
                                    <td>${row.flightClass}</td>
                                    <td>${row.passengers}</td>
                                    <td>${row.excluded.join('<br>')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            const parsersTable = document.getElementById('collapseTable');

            if (parsersTable) {
                parsersTable.innerHTML = table;
            }
        }).catch((error) => {
            console.error(error);
            toggleParsersTable(false);
        });
    } else {
        toggleParsersMultiselect(true);
        toggleExcludeParsersMultiselect(false);
        toggleParsersTable(false);
    }
}

function onFormChange(event: Event): void {
    const target = event.target as HTMLElement;

    if (target.closest('form[name="ra_flight_search_query"]')) {
        updateForm();
    }
}

function debounce<T extends (...args: any[]) => void>(func: T, wait: number): T {
    let timeoutId: ReturnType<typeof setTimeout> | null = null;

    const debouncedFunction = (...args: Parameters<T>) => {
        if (timeoutId !== null) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(() => {
            func(...args);
        }, wait);
    };

    return debouncedFunction as T;
}

onReady(() => {
    updateForm();
    document.addEventListener('change', debounce(onFormChange, 800));
});