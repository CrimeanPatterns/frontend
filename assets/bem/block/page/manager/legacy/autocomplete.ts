import autocomplete from 'autocompleter';
import 'autocompleter/autocomplete.css';
import './autocomplete.scss';
import axios from '../../../../ts/service/axios';
import onReady from '../../../../ts/service/on-ready';

interface AutocompleteItem {
    label: string;
    value: string;
}

function init() {
    const autocompleters = document.querySelectorAll<HTMLInputElement>('[data-source]');

    for (const autocompleter of autocompleters) {
        const source = autocompleter.getAttribute('data-source');
        const param = autocompleter.getAttribute('data-param') || 'term';
        const wait = autocompleter.getAttribute('data-wait') || 500;
        const minLength = autocompleter.getAttribute('data-min-length') || 1;

        if (!source) {
            continue;
        }

        autocomplete<AutocompleteItem>({
            input: autocompleter,
            emptyMsg: 'No items found',
            minLength: +minLength,
            debounceWaitMs: +wait,
            fetch: async (text: string, update: (items: AutocompleteItem[]) => void) => {
                const url = new URL(source);
                url.searchParams.set(param, text);

                const suggestions = await axios.get<AutocompleteItem[]>(url.toString());
                update(suggestions.data);
            },
            onSelect: (item: AutocompleteItem) => {
                autocompleter.value = item.value;
                autocompleter.focus();
            },
            customize: (input: HTMLInputElement | HTMLTextAreaElement, inputRect: DOMRect, container: HTMLDivElement, maxHeight: number) => {
                container.style.width = '';
            }
        });
    }
}

onReady(init);