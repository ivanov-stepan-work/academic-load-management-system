<template>
    <div
        class="offcanvas offcanvas-end"
        data-bs-scroll="true"
        tabindex="-1"
        id="openEditSideBar"
        aria-labelledby="offcanvasScrollingLabel"
    >
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasScrollingLabel">
                Редактировать запись
            </h5>
            <button
                type="button"
                class="btn-close text-reset"
                data-bs-dismiss="offcanvas"
                aria-label="Close"
            ></button>
        </div>
        <div class="offcanvas-body">
            <form @submit.prevent="saveChanges">
                <div class="">
                    <div v-for="item in items" :key="item.name" class="">
                        <div v-if="item.name !== 'id'">
                            <template v-if="item.value === null">
                                <h5 class="mt-3">{{ item.title }}</h5>
                                <input
                                    type="text"
                                    class="form-control mt-3"
                                    v-model="item.value"
                                    :id="item.name"
                                    :placeholder="item.title"
                                />
                            </template>
                            <Select
                                v-else-if="item.label === 'select'"
                                :options="item.value"
                                :header="item.title"
                                v-model:selected-value="item.selectedValue"
                            >
                            </Select>
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
                                <h5 class="mt-3">{{ item.title }}</h5>
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
                                    v-bind:max="
                                        item.label === 'number'
                                            ? item.max
                                            : null
                                    "
                                />
                            </template>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between btn-lg mt-2">
                    <button type="submit" class="btn btn-primary">
                        Сохранить
                    </button>
                    <button
                        type="button"
                        class="btn btn-danger btn-lg"
                        @click="deleteElement"
                    >
                        Удалить
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
<script>
import DynamicListGroup from "./DynamicListGroup.vue";
import Select from "./Select.vue";
import { useRoute } from "vue-router";
import { Offcanvas } from "bootstrap";
import { inject, watch, computed } from "vue";
export default {
    components: {
        DynamicListGroup,
        Select,
    },
    emits: ["delete", "save", "close"],
    props: {
        items: {
            type: Array,
            required: true,
        },
    },

    setup(props, { emit }) {
        const route = useRoute();
        const fetchFunction = inject("fetchFunction");
        const saveChanges = () => {
            const dataToSave = {};
            dataToSave["href"] = props.items[0].href;
            props.items.forEach((item) => {
                if (item.label == "select") {
                    dataToSave[item.name] = item.selectedValue;
                } else if (item.label == "DLG") {
                    dataToSave[item.name] = item.value
                        .filter((option) => option.select)
                        .map((option) => option.id);
                } else {
                    // console.log(item.name);
                    dataToSave[item.name] = item.value;
                }
            });
            // console.log(dataToSave);
            fetchFunction(
                `/api/${
                    dataToSave.href ? dataToSave.href : route.params.endpoint
                }/${dataToSave.id}`,
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(dataToSave),
                }
            )
                .then((response) => response.json())
                .then((data) => {
                    emit("save", data);
                })
                .catch((error) => {
                    console.error("Ошибка при обновлении данных:", error);
                });
        };
        const deleteElement = () => {
            fetchFunction(
                `/api/${
                    props.items[0].href
                        ? props.items[0].href
                        : route.params.endpoint
                }/${props.items[0].value}`,
                {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                    },
                }
            )
                .then((response) => response.json())
                .then((data) => {
                    console.log(data + " " + props.items[0].value);
                    if (data === 200) emit("delete", props.items[0].value);
                });
        };
        const updateListGroup = (name, updatedItems) => {
            const item = props.items.find((item) => item.name === name);
            if (item) {
                item.value = updatedItems;
                console.log(item.value);
            }
        };
        return {
            deleteElement,
            saveChanges,
            fetchFunction,
            route,
            updateListGroup,
        };
    },
};
</script>

<style scoped></style>
