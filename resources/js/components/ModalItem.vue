<template>
    <div
        class="modal fade"
        id="openAddModal"
        tabindex="-1"
        aria-labelledby="itemModalLabel"
        aria-hidden="true"
    >
        <form @submit.prevent="saveItems">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="itemModalLabel">
                            Создание новой строки
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div
                            v-for="item in filteredItems"
                            :key="item.name"
                            class="flex-fill me-3 mb-3"
                        >
                            <div v-if="item.name !== 'id'">
                                <Select
                                    v-if="item.label === 'select'"
                                    :options="filteredOptions(item)"
                                    :header="item.title"
                                    v-model:selected-value="item.selectedValue"
                                    @change="updateMaxValue(item)"
                                />
                                <DynamicListGroup
                                    v-else-if="item.label === 'DLG'"
                                    :items="item.value"
                                    :header="item.title"
                                    @update:items="
                                        updateListGroup(item.name, $event)
                                    "
                                >
                                </DynamicListGroup>
                                <template v-else>
                                    <h5 class="mt-3">
                                        {{ item.title }}
                                        <template v-if="customLimit">
                                            < {{ computeMax(item) }}
                                        </template>
                                    </h5>
                                    <input
                                        :type="
                                            item.label === 'number'
                                                ? 'number'
                                                : item.name === 'password'
                                                ? 'password'
                                                : 'text'
                                        "
                                        class="form-control mt-3"
                                        v-model="item.value"
                                        :id="item.name"
                                        :placeholder="item.title"
                                        v-bind:min="
                                            item.label === 'number'
                                                ? item.min
                                                : null
                                        "
                                        :max="computeMax(item)"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div v-if="customLimit" class="form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="hideDistributed"
                                v-model="hideDistributed"
                            />
                            <label
                                class="form-check-label"
                                for="hideDistributed"
                            >
                                Скрыть все распределённые элементы
                            </label>
                        </div>
                        <div class="d-flex justify-content-between btn-lg mt-2">
                            <button type="submit" class="btn btn-primary">
                                Сохранить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
import { ref, computed } from "vue";
import DynamicListGroup from "./DynamicListGroup.vue";
import Select from "./Select.vue";
import { useRoute } from "vue-router";
export default {
    components: {
        DynamicListGroup,
        Select,
    },
    emits: ["save"],
    props: {
        items: Array,
        key: Number,
        customLimit: {
            type: Boolean,
            default: false,
        },
    },
    setup(props, { emit }) {
        const route = useRoute();
        const maxValue = ref(2600);
        const hideDistributed = ref(false);

        const filteredItems = computed(() => {
            if (hideDistributed.value) {
                return props.items.filter(
                    (item) =>
                        item.label !== "select" ||
                        item.value.some(
                            (option) =>
                                option.computed_attributes?.difference_hours > 0
                        )
                );
            }
            return props.items;
        });

        const filteredOptions = (item) => {
            if (hideDistributed.value && item.label === "select") {
                return item.value.filter(
                    (option) => option.computed_attributes?.difference_hours > 0
                );
            }
            return item.value;
        };

        const updateListGroup = (name, updatedItems) => {
            const item = props.items.find((item) => item.name === name);
            if (item) {
                item.value = updatedItems;
            }
        };

        const saveItems = () => {
            const data = {};
            props.items.forEach((item) => {
                if (item.label == "select") {
                    data[item.name] = item.selectedValue;
                } else if (item.label == "DLG") {
                    data[item.name] = item.value
                        .filter((option) => option.select)
                        .map((option) => option.id);
                } else {
                    data[item.name] = item.value;
                }
            });
            emit("save", data);
        };

        const computeMax = (item) => {
            return maxValue.value;
        };

        const updateMaxValue = (item) => {
            const selectedItem = item.value.find(
                (dl) => dl.id === item.selectedValue
            );

            maxValue.value =
                selectedItem?.computed_attributes?.difference_hours ?? 2600;
        };

        return {
            updateListGroup,
            saveItems,
            computeMax,
            updateMaxValue,
            maxValue,
            hideDistributed,
            filteredItems,
            filteredOptions,
        };
    },
};
</script>
