export class FieldValidator {
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
