import {CarFormController} from './car-form-controller';
import {ControlDom} from './control-dom';
import {EditorLocator} from './editor-locator';
import {EntityContext} from './entity-context';
import {ErrorRenderer} from './error-renderer';
import {FieldValidator} from './field-validator';
import {RegistrationDateFiller} from './registration-date-filler';
import {StageRulesApplier} from './stage-rules-applier';
import {ValidationHook} from './validation-hook';
import {VinChecker} from './vin-checker';

export function createCarForm(config) {
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
