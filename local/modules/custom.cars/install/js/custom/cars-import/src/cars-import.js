import {Event} from 'main.core';
import {ImportDialog} from './import-dialog';
import {ImportPage} from './import-page';

Event.ready(() => ImportPage.bindAll());

export {ImportDialog, ImportPage};
