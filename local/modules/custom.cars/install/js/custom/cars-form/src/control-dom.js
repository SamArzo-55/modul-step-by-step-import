import {Type} from 'main.core';
import {CSS} from './constants';

const TEXT_INPUT = 'input:not([type=hidden]):not([type=checkbox]):not([type=radio]), textarea';

export class ControlDom {
    wrapper(control) {
        return Type.isFunction(control.getWrapper) ? control.getWrapper() : null;
    }

    input(control) {
        return this.wrapper(control)?.querySelector(TEXT_INPUT) ?? null;
    }

    value(control) {
        const input = this.input(control);

        if (input) {
            return input.value.trim();
        }

        return Type.isFunction(control.getRuntimeValue) ? String(control.getRuntimeValue() || '').trim() : '';
    }

    isEditing(control) {
        const modes = window.BX.UI?.EntityEditorMode;

        return !modes || !Type.isFunction(control.getMode) || control.getMode() === modes.edit;
    }

    isHidden(control) {
        return this.wrapper(control)?.classList.contains(CSS.hidden) ?? false;
    }
}
