const DATE_FORMAT = 'DD.MM.YYYY HH:MI:SS';

export class RegistrationDateFiller {
    constructor({fieldName, locator, dom, context}) {
        this.fieldName = fieldName;
        this.locator = locator;
        this.dom = dom;
        this.context = context;
        this.prefilled = new WeakSet();
    }

    prefill(editor) {
        const control = this.fieldName ? this.locator.getControl(editor, this.fieldName) : null;

        if (!control || this.prefilled.has(control) || this.context.entityId(editor) > 0 || !this.dom.input(control)) {
            return;
        }

        this.prefilled.add(control);
        this.fill(editor, control);
    }

    fill(editor, control) {
        const input = this.dom.input(control);

        if (this.context.entityId(editor) > 0 || !this.dom.isEditing(control) || !input || input.disabled || input.value.trim() !== '') {
            return;
        }

        input.value = this.formatNow();
        input.dispatchEvent(new Event('input', {bubbles: true}));
        input.dispatchEvent(new Event('change', {bubbles: true}));
        control.markAsChanged?.();
    }

    formatNow() {
        const {BX} = window;

        return BX.date.format(BX.date.convertBitrixFormat(DATE_FORMAT), new Date());
    }
}
