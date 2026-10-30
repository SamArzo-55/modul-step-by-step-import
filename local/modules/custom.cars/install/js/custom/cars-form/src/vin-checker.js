import {ajax} from 'main.core';

export class VinChecker {
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
