import {Type} from 'main.core';

export class EntityContext {
    entityId(editor) {
        let id = Type.isFunction(editor.getEntityId) ? editor.getEntityId() : 0;

        if (!id) {
            id = /\/details\/(\d+)\//.exec(window.location.pathname)?.[1] ?? 0;
        }

        return parseInt(id, 10) || 0;
    }

    stageCode(editor) {
        const model = Type.isFunction(editor.getModel) ? editor.getModel() : null;
        const stageId = Type.isFunction(model?.getField) ? model.getField('STAGE_ID', '') : '';

        return Type.isStringFilled(stageId) ? stageId.slice(stageId.lastIndexOf(':') + 1) : '';
    }
}
