this.BX = this.BX || {};
this.BX.Custom = this.BX.Custom || {};
(function (exports,main_core,ui_stepprocessing) {
	'use strict';

	const {Event, ajax} = main_core;
	const {Process} = ui_stepprocessing;

	const MESSAGES = Object.freeze({
	    dialog: {
	        DialogTitle: 'Import cars from Excel',
	        DialogSummary: 'Select an .xlsx file. The column titles must be in the first row. Large files are processed in portions.',
	        DialogStartButton: 'Start import',
	        DialogStopButton: 'Stop',
	        DialogCloseButton: 'Close',
	        RequestCanceling: 'Stopping...',
	        RequestCanceled: 'The import was stopped.',
	        RequestCompleted: 'The import is finished.',
	    },
	    fileTitle: 'Excel file (.xlsx)',
	    fileRequired: 'Select an .xlsx file.',
	    steps: {
	        upload: ['Uploading the file...', 'Uploading'],
	        index: ['Preparing the text values...', 'Preparing'],
	        parse: ['Reading the rows...', 'Reading'],
	        import: ['Creating the cars...', 'Importing'],
	    },
	});

	const PROCESS_ID = 'custom-cars-import';
	const TOKEN_PARAM = 'token';
	const CANCEL_ACTION = 'cancel';

	class ImportDialog {
	    constructor({controller, messages = MESSAGES}) {
	        this.controller = controller;
	        this.messages = messages;
	        this.process = null;
	        this.token = null;
	    }

	    show() {
	        this.token = null;
	        this.process = this.createProcess();
	        this.process.showDialog();
	    }

	    createProcess() {
	        const {messages} = this;

	        return new Process({
	            id: PROCESS_ID,
	            controller: this.controller,
	            messages: messages.dialog,
	            optionsFields: {
	                file: {
	                    name: 'file',
	                    type: 'file',
	                    title: messages.fileTitle,
	                    obligatory: true,
	                    emptyMessage: messages.fileRequired,
	                },
	            },
	            queue: this.createQueue(),
	            handlers: {RequestStop: () => this.cancel()},
	            showButtons: {start: true, stop: true, close: true},
	        });
	    }

	    createQueue() {
	        const {steps} = this.messages;

	        return [
	            {
	                action: 'uploadFile',
	                title: steps.upload[0],
	                progressBarTitle: steps.upload[1],
	                handlers: {StepCompleted: (state, result) => this.rememberToken(result)},
	            },
	            {action: 'indexStrings', title: steps.index[0], progressBarTitle: steps.index[1]},
	            {action: 'parseSheet', title: steps.parse[0], progressBarTitle: steps.parse[1]},
	            {action: 'importRows', title: steps.import[0], progressBarTitle: steps.import[1]},
	            {action: 'finalize', finalize: true},
	        ];
	    }

	    rememberToken(result) {
	        const token = (result?.data ?? result)?.TOKEN;

	        if (token) {
	            this.token = token;
	            this.process.setParam(TOKEN_PARAM, token);
	        }
	    }

	    cancel() {
	        if (this.token === null) {
	            return;
	        }

	        const noop = () => {};

	        ajax.runAction(`${this.controller}.${CANCEL_ACTION}`, {data: {[TOKEN_PARAM]: this.token}}).then(noop, noop);
	        this.token = null;
	    }
	}

	class ImportPage {
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

	Event.ready(() => ImportPage.bindAll());

	exports.ImportDialog = ImportDialog;
	exports.ImportPage = ImportPage;

}((this.BX.Custom.CarsImport = this.BX.Custom.CarsImport || {}),BX,BX.UI.StepProcessing));
