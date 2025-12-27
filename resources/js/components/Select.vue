<template>
    <div>
        <h5 class="mt-3">{{ header }}</h5>
        <select class="form-select mt-3" v-model="selectedOption">
            <option
                v-for="option in options"
                :key="option.id"
                :value="option.id"
            >
                {{ option.name }}
            </option>
        </select>
    </div>
</template>

<script>
export default {
    props: {
        options: {
            type: Array,
            required: true,
        },
        header: {
            type: String,
            required: true,
        },
        selectedValue: {
            type: [String, Number],
            default: null,
        },
    },
    emits: ["update:selectedValue"],
    data() {
        return {
            selectedOption: this.selectedValue,
        };
    },
    watch: {
        selectedOption(newVal) {
            this.$emit("update:selectedValue", newVal);
        },
        selectedValue(newVal) {
            this.selectedOption = newVal;
        },
    },
    created() {
        const defaultOption = this.options.find((option) => option.select);
        if (defaultOption) {
            this.selectedOption = defaultOption.id;
        }
    },
};
</script>
