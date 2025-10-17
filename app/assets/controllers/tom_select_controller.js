import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    static values = {
        options: Object
    }

    connect() {
        const defaultOptions = {
            plugins: {
                remove_button: {
                    title: 'Remove this item',
                }
            },
            maxItems: null,
            allowEmptyOption: true,
            closeAfterSelect: false,
            hidePlaceholder: false,
        };

        const options = { ...defaultOptions, ...this.optionsValue };

        this.tomSelect = new TomSelect(this.element, options);
    }

    disconnect() {
        if (this.tomSelect) {
            this.tomSelect.destroy();
        }
    }
}
