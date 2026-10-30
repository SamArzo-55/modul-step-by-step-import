this.BX = this.BX || {};
this.BX.Custom = this.BX.Custom || {};
(function (exports,main_core) {
	'use strict';

	const {Event, Runtime, Type, ajax} = main_core;

	const REFRESH_DELAY = 100;
	const REFRESH_INTERVAL = 1000;

	class CarFormController {
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

	const CSS = Object.freeze({
	    hidden: 'custom-cars-hidden',
	    locked: 'custom-cars-locked',
	    error: 'custom-cars-error',
	    invalid: 'custom-cars-invalid',
	    danger: 'ui-ctl-danger',
	});

	const ATTR_ERROR = 'data-cars-error';

	const HOOKED_CODES = Object.freeze(['BRAND', 'MODEL', 'PLATE', 'YEAR', 'VIN', 'REG_DATE']);

	const TEXT_INPUT = 'input:not([type=hidden]):not([type=checkbox]):not([type=radio]), textarea';

	class ControlDom {
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

	const CONTROL_METHODS = ['getControlByIdRecursive', 'getControlById'];

	class EditorLocator {
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

	class EntityContext {
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

	class ErrorRenderer {
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

	class FieldValidator {
	    constructor({labels, required, firstYear, currentYear}) {
	        this.labels = labels;
	        this.required = required;
	        this.firstYear = firstYear;
	        this.currentYear = currentYear;
	    }

	    check(code, value) {
	        const label = this.labels[code];

	        if (this.required.includes(code) && value === '') {
	            return `${label} is required.`;
	        }

	        return code === 'YEAR' && value !== '' ? this.checkYear(label, value) : '';
	    }

	    checkYear(label, value) {
	        if (!/^\d+$/.test(value)) {
	            return `${label} must be a whole number, for example 2018.`;
	        }

	        const year = parseInt(value, 10);

	        if (year < this.firstYear) {
	            return `${label} cannot be earlier than ${this.firstYear}, the year the first car was built.`;
	        }

	        if (year > this.currentYear) {
	            return `${label} cannot be later than the current year (${this.currentYear}).`;
	        }

	        return '';
	    }
	}

	const DATE_FORMAT = 'DD.MM.YYYY HH:MI:SS';

	class RegistrationDateFiller {
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

	const FOCUSABLE = 'input, select, textarea, button';

	class StageRulesApplier {
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

	class ValidationHook {
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

	class VinChecker {
	    constructor(action) {
	        this.action = action;
	    }

	    async exists(vin, entityId) {
	        try {
	            const response = await ajax.runAction(this.action, {data: {vin, id: entityId}});

	            return response.data?.exists === true;
	        } catch {
	            return false;
	        }
	    }
	}

	function createCarForm(config) {
	    const locator = new EditorLocator();
	    const context = new EntityContext();
	    const dom = new ControlDom();
	    const renderer = new ErrorRenderer({fields: config.fields, locator});
	    const dateFiller = new RegistrationDateFiller({fieldName: config.fields.REG_DATE, locator, dom, context});
	    const stageRules = new StageRulesApplier({
	        fields: config.fields,
	        stages: config.stages,
	        stageNames: config.stageNames,
	        locator,
	        context,
	    });
	    const hook = new ValidationHook({
	        fields: config.fields,
	        locator,
	        dom,
	        context,
	        renderer,
	        dateFiller,
	        validator: new FieldValidator(config),
	        vinChecker: new VinChecker(config.api.vinExists),
	    });

	    return new CarFormController({locator, hook, dateFiller, stageRules, renderer});
	}

	let instance = null;

	function startCarForm(config = window.CustomCarsConfig) {
	    if (instance === null && config) {
	        instance = createCarForm(config);
	        instance.start();
	    }

	    return instance;
	}

	Event.ready(() => startCarForm());

	exports.startCarForm = startCarForm;

}((this.BX.Custom.CarsForm = this.BX.Custom.CarsForm || {}),BX));
