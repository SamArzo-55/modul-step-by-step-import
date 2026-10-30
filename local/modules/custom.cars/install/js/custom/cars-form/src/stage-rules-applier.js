import {CSS} from './constants';

const FOCUSABLE = 'input, select, textarea, button';

export class StageRulesApplier {
    constructor({fields, stages, stageNames, locator, context}) {
        this.fields = fields;
        this.stages = stages;
        this.stageNames = stageNames;
        this.locator = locator;
        this.context = context;
    }

    apply(editor) {
        const stageCode = this.context.stageCode(editor);
        const rules = this.stages[stageCode] ?? {locked: [], hidden: []};
        const stageName = this.stageNames[stageCode] ?? '';

        for (const name of Object.values(this.fields)) {
            const block = this.locator.getBlock(editor, name);

            if (block) {
                block.classList.toggle(CSS.hidden, rules.hidden.includes(name));
                this.setLocked(block, rules.locked.includes(name), stageName);
            }
        }
    }

    setLocked(block, locked, stageName) {
        for (const control of block.querySelectorAll(FOCUSABLE)) {
            if (locked && !control.disabled) {
                control.disabled = true;
                control.dataset.carsLocked = 'Y';
            } else if (!locked && control.dataset.carsLocked === 'Y') {
                control.disabled = false;
                delete control.dataset.carsLocked;
            }
        }

        block.classList.toggle(CSS.locked, locked);

        if (locked) {
            block.setAttribute('title', `Locked at stage "${stageName}"`);
        } else {
            block.removeAttribute('title');
        }

        this.guardClicks(block);
    }

    guardClicks(block) {
        if (block.dataset.carsGuard === 'Y') {
            return;
        }

        block.dataset.carsGuard = 'Y';

        const stop = (event) => {
            if (block.classList.contains(CSS.locked)) {
                event.stopPropagation();
                event.preventDefault();
            }
        };

        block.addEventListener('click', stop, true);
        block.addEventListener('dblclick', stop, true);
    }
}
