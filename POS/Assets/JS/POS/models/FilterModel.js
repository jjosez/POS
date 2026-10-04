/**
 * Pure state container for the family filter + navigation.
 * No DOM access, no async, no event emission.
 *
 * - filters:        families selected as filters (multi).
 * - currentFamily:  family currently being explored (navigation only).
 * - breadcrumb:     path of families traversed from the root.
 * - codcliente:     selected customer code.
 *
 * Filtering and navigation are independent: clearing one does not reset the
 * other, unless reset() is called.
 */
class FilterClass {
    constructor({
        filters = [],
        currentFamily = null,
        breadcrumb = [],
        codcliente = ''
    } = {}) {
        this.filters = filters;
        this.currentFamily = currentFamily;
        this.breadcrumb = breadcrumb;
        this.codcliente = codcliente;
    }

    addFamily({code, description = '', thumbnail = ''} = {}) {
        if (!code) return;

        const exists = this.filters.some(f => f.code === code);
        if (exists) return;

        this.filters.unshift({code, description, thumbnail});
    }

    removeFamily(code) {
        if (!code) return;

        const index = this.filters.findIndex(f => f.code === code);
        if (index === -1) return;

        this.filters.splice(index, 1);
    }

    hasFamily(code) {
        if (!code) return false;

        return this.filters.some(f => f.code === code);
    }

    clearFilters() {
        this.filters = [];
    }

    clearNavigation() {
        this.currentFamily = null;
        this.breadcrumb = [];
    }

    getFamiliesPayload() {
        return this.filters.map(f => ({
            code: f.code,
            description: f.description,
            thumbnail: f.thumbnail
        }));
    }

    getSelectedCodes() {
        return this.filters.map(f => f.code);
    }

    navigateTo(family) {
        this.currentFamily = family || null;

        if (!family) {
            this.breadcrumb = [];
            return;
        }

        const index = this.breadcrumb.findIndex(f => f.codfamilia === family.codfamilia);

        if (index === -1) {
            this.breadcrumb.push(family);
        } else {
            this.breadcrumb = this.breadcrumb.slice(0, index + 1);
        }
    }

    navigateBack() {
        if (this.breadcrumb.length === 0) {
            this.currentFamily = null;
            return null;
        }

        this.breadcrumb.pop();
        this.currentFamily = this.breadcrumb[this.breadcrumb.length - 1] || null;

        return this.currentFamily;
    }

    setCustomer(codcliente) {
        this.codcliente = codcliente || '';
    }

    reset() {
        this.filters = [];
        this.currentFamily = null;
        this.breadcrumb = [];
        this.codcliente = '';
    }
}

const searchFilter = new FilterClass({
    filters: [],
    codcliente: ''
});

export {FilterClass, searchFilter};
