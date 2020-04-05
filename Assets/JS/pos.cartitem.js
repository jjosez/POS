class CartItem {

    constructor(code = "", description = "", amount = 0, price = 0, discount = 0,
                total = 0, taxes=0) {
        this._code = code;
        this._description = description;
        this._amount = amount;
        this._price = price;
        this._discount = discount;
        this._total = total;
        this._taxes = taxes;
    }

    get code() {
        return this._code;
    }

    set code(value) {
        this._code = this.validateTypeOf(value, 'string');
    }

    get description() {
        return this._description;
    }

    set description(value) {
        this._description = this.validateTypeOf(value, 'string');
    }

    get amount() {
        return this._amount;
    }

    set amount(value) {
        this._amount = this.validateTypeOf(value, 'number');
    }

    get price() {
        return this._price;
    }

    set price(value) {
        this._price = this.validateTypeOf(value, 'number');
    }

    get discount() {
        return this._discount;
    }

    set discount(value) {
        this._discount = this.validateTypeOf(value, 'number');
    }

    get total() {
        return this._total;
    }

    set total(value) {
        this._total = this.validateTypeOf(value, 'number');
    }

    get taxes() {
        return this._taxes;
    }

    set taxes(value) {
        this._taxes = this.validateTypeOf(value, 'number');
    }

    validateTypeOf(param , type) {
        switch (type) {
            case 'string':
                if (param.lenght < 1) {return ""}
                break;
            case 'number':
                if (param.lenght < 1) {return 0}
                break;
        }
    }
}