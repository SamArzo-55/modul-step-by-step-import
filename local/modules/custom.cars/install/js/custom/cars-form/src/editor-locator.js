import {Type} from 'main.core';

const CONTROL_METHODS = ['getControlByIdRecursive', 'getControlById'];

export class EditorLocator {
    getEditor() {
        const {BX} = window;

        for (const provider of [BX.Crm?.EntityEditor, BX.UI?.EntityEditor]) {
            const editor = Type.isFunction(provider?.getDefault) ? provider.getDefault() : null;

            if (editor) {
                return editor;
            }
        }

        return null;
    }

    getControl(editor, name) {
        for (const method of CONTROL_METHODS) {
            const control = Type.isFunction(editor[method]) ? editor[method](name) : null;

            if (control) {
                return control;
            }
        }

        return null;
    }

    getBlock(editor, name) {
        const wrapper = this.getControl(editor, name)?.getWrapper?.();

        if (wrapper) {
            return wrapper;
        }

        return document.querySelector(`[data-cid="${name}"]`)
            ?? document.querySelector(`[name="${name}"]`)?.closest('.ui-entity-editor-content-block')
            ?? null;
    }
}
