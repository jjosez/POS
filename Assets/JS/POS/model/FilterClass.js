class FilterClass {
    constructor({families = [], filters = []} = {}) {
        this.families = families;
        this.filters = filters;
    }

    deleteFamilyFilter(index) {
        this.families.splice(index, 1);
    }

    getFamilyFilter(index) {
        this.families[index].index = index;
        return this.families[index];
    }

    setFamilyFilter(code, description, thumbnail) {
        if (!code) return;

        const index = this.families.findIndex(element => element.code === code);

        if (index !== -1) {
            this.families.splice(index, 1);
            return;
        }

        this.families.unshift({code, description, thumbnail});
    }
}

export default FilterClass;
