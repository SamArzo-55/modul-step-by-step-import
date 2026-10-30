import {ATTR_ERROR, CSS} from './constants';

export class ErrorRenderer {
    constructor({fields, locator}) {
        this.fields = fields;
        this.messages = new Map();
        this.locator = locator;
        this.watched = new WeakMap();
    }

    show(name, message) {
        this.messages.set(name, message);
        this.render(this.locator.getEditor());
    }

    clear(name) {
        if (this.messages.delete(name)) {
            this.render(this.locator.getEditor());
        }
    }

    render(editor) {
        if (!editor) {
            return;
        }

        for (const name of Object.values(this.fields)) {
            const block = this.locator.getBlock(editor, name);

            if (block?.parentNode) {
                this.watchInput(block, name);
                this.renderBlock(block, name, (this.messages.get(name) ?? ''));
            }
        }
    }

    renderBlock(block, name, message) {
        const node = this.findNode(block, name);

        if (message === '') {
            node?.remove();
            this.markInvalid(block, false);

            return;
        }

        if (!node) {
            block.parentNode.insertBefore(this.createNode(name, message), block.nextSibling);
        } else if (node.textContent !== message) {
            node.textContent = message;
        }

        this.markInvalid(block, true);
    }

    createNode(name, message) {
        const node = document.createElement('div');

        node.className = CSS.error;
        node.setAttribute(ATTR_ERROR, name);
        node.textContent = message;

        return node;
    }

    findNode(block, name) {
        for (let next = block.nextSibling; next; next = next.nextSibling) {
            if (next.nodeType !== Node.ELEMENT_NODE) {
                continue;
            }

            if (next.getAttribute(ATTR_ERROR) === name) {
                return next;
            }

            if (!next.hasAttribute(ATTR_ERROR)) {
                break;
            }
        }

        return null;
    }

    markInvalid(block, invalid) {
        for (const container of block.querySelectorAll('.ui-ctl')) {
            if (invalid) {
                container.classList.add(CSS.danger, CSS.invalid);
            } else if (container.classList.contains(CSS.invalid)) {
                container.classList.remove(CSS.danger, CSS.invalid);
            }
        }
    }

    watchInput(block, name) {
        if (this.watched.get(block) === name) {
            return;
        }

        this.watched.set(block, name);

        const handler = (event) => {
            if (event.isTrusted) {
                this.clear(name);
            }
        };

        block.addEventListener('input', handler, true);
        block.addEventListener('change', handler, true);
    }
}
