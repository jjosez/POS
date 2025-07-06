class FilterClass {
    constructor({families = [], filters = []} = {}) {
        this.families = families;
        this.filters = filters;
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

    reset() {
        this.families = [];
        this.filters = [];
    }
}

const searchFilter = new FilterClass({
    families: [],
    filters: []
});

export {FilterClass, searchFilter};
