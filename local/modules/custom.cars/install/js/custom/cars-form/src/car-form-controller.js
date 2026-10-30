import {Runtime} from 'main.core';

const REFRESH_DELAY = 100;
const REFRESH_INTERVAL = 1000;

export class CarFormController {
    constructor({locator, hook, dateFiller, stageRules, renderer}) {
        this.locator = locator;
        this.hook = hook;
        this.dateFiller = dateFiller;
        this.stageRules = stageRules;
        this.renderer = renderer;
        this.schedule = Runtime.debounce(() => this.refresh(), REFRESH_DELAY);
    }

    start() {
        if (!window.location.pathname.includes('/details/')) {
            return;
        }

        if ('MutationObserver' in window) {
            new MutationObserver(this.schedule).observe(document.body, {childList: true, subtree: true});
        }

        window.setInterval(() => this.refresh(), REFRESH_INTERVAL);
        window.BX.addCustomEvent('BX.UI.EntityEditor:onInit', this.schedule);

        this.refresh();
    }

    refresh() {
        const editor = this.locator.getEditor();

        if (!editor) {
            return;
        }

        this.hook.install(editor);
        this.dateFiller.prefill(editor);
        this.stageRules.apply(editor);
        this.renderer.render(editor);
    }
}
