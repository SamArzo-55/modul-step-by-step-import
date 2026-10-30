import './car-form.css';
import {Event} from 'main.core';
import {createCarForm} from './create-car-form';

let instance = null;

export function startCarForm(config = window.CustomCarsConfig) {
    if (instance === null && config) {
        instance = createCarForm(config);
        instance.start();
    }

    return instance;
}

Event.ready(() => startCarForm());
