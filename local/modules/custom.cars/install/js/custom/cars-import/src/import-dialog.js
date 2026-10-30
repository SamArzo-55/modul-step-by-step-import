import {ajax} from 'main.core';
import {Process} from 'ui.stepprocessing';
import {MESSAGES} from './messages';

const PROCESS_ID = 'custom-cars-import';
const TOKEN_PARAM = 'token';
const CANCEL_ACTION = 'cancel';

export class ImportDialog {
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
