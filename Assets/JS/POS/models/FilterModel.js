class FilterClass {
    constructor({families = [], filters = [], currentFamily = null, breadcrumb = []} = {}) {
        this.families = families;
        this.filters = filters;
        this.currentFamily = currentFamily;
        this.breadcrumb = breadcrumb;
    }

    deleteFamilyFilter(index) {
        if (index < 0 || index >= this.families.length) return;
        this.families.splice(index, 1);
    }

    getFamilyFilter(index) {
        const family = this.families[index];
        if (!family) return null;
        return {...family, index};
    }

    toggleFamilyFilter(code, description, thumbnail) {
        if (!code) return;

        const index = this.families.findIndex(element => element.code === code);

        if (index !== -1) {
            this.families.splice(index, 1);
        } else {
            this.families.unshift({code, description, thumbnail});
        }
    }

    navigateToFamily(familia) {
        this.currentFamily = familia;
        if (familia) {
            const index = this.breadcrumb.findIndex(f => f.codfamilia === familia.codfamilia);
            if (index === -1) {
                this.breadcrumb.push(familia);
            } else {
                this.breadcrumb = this.breadcrumb.slice(0, index + 1);
            }
        } else {
            this.breadcrumb = [];
        }
    }

    navigateBack() {
        this.breadcrumb.pop();
        this.currentFamily = this.breadcrumb[this.breadcrumb.length - 1] || null;
    }

    reset() {
        this.families = [];
        this.filters = [];
        this.currentFamily = null;
        this.breadcrumb = [];
    }
}

const searchFilter = new FilterClass({
    families: [],
    filters: []
});

export {FilterClass, searchFilter};
