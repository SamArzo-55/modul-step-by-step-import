import {ImportDialog} from './import-dialog';

export class ImportPage {
    constructor(root) {
        const {controller} = JSON.parse(root.dataset.config);

        this.root = root;
        this.dialog = new ImportDialog({controller});
    }

    init() {
        this.root.querySelector('[data-role="open"]')?.addEventListener('click', () => this.dialog.show());
        this.dialog.show();
    }

    static bindAll() {
        for (const root of document.querySelectorAll('[data-cars-import]')) {
            new ImportPage(root).init();
        }
    }
}
