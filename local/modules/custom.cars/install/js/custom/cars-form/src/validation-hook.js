import {HOOKED_CODES} from './constants';

export class ValidationHook {
    constructor({fields, locator, dom, context, validator, vinChecker, renderer, dateFiller}) {
        this.fields = fields;
        this.locator = locator;
        this.dom = dom;
        this.context = context;
        this.validator = validator;
        this.vinChecker = vinChecker;
        this.renderer = renderer;
        this.dateFiller = dateFiller;
        this.hooked = new WeakSet();
    }

    install(editor) {
        for (const code of HOOKED_CODES) {
            const name = this.fields[code];
            const control = name ? this.locator.getControl(editor, name) : null;

            if (control && !this.hooked.has(control) && typeof control.validate === 'function') {
                this.hooked.add(control);
                this.wrap(editor, control, code, name);
            }
        }
    }

    wrap(editor, control, code, name) {
        const hook = this;
        const original = control.validate;

        control.validate = function (...args) {
            if (code === 'REG_DATE') {
                hook.dateFiller.fill(editor, this);
            }

            const outcome = original.apply(this, args);
            const finish = (valid = true) => hook.finish({editor, control: this, result: args[0], code, name, valid});

            return typeof outcome?.then === 'function' ? outcome.then(finish) : finish(outcome);
        };
    }

    finish({editor, control, result, code, name, valid}) {
        if (code === 'REG_DATE' || !this.dom.isEditing(control) || this.dom.isHidden(control)) {
            return valid;
        }

        const value = this.dom.value(control);
        const message = this.validator.check(code, value);

        if (message !== '') {
            return this.reject(control, result, name, message);
        }

        if (code === 'VIN' && value !== '') {
            return this.vinChecker.exists(value, this.context.entityId(editor)).then((exists) => {
                if (exists) {
                    return this.reject(control, result, name, `A car with VIN code "${value}" already exists.`);
                }

                this.renderer.clear(name);

                return valid;
            });
        }

        this.renderer.clear(name);

        return valid;
    }

    reject(control, result, name, message) {
        const {BX} = window;

        if (typeof result?.addError === 'function' && BX.UI?.EntityValidationError) {
            result.addError(BX.UI.EntityValidationError.create({field: control, message}));
        }

        this.renderer.show(name, message);

        return false;
    }
}
