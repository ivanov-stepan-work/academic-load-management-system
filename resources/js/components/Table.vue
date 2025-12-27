<template>
    <table class="table text-center table-bordered">
        <thead>
            <tr>
                <th v-for="(column, index) in columns" :key="index">
                    {{ column }}
                </th>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="(row, rowIndex) in rows"
                :key="rowIndex"
                :class="{ 'table-active': selectedRowIndex === rowIndex }"
                @click="selectRow(rowIndex, row)"
            >
                <td v-for="(item, columnIndex) in row" :key="columnIndex">
                    {{ item }}
                </td>
            </tr>
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        <button type="button" class="btn btn-primary" @click="openAddModal">
            Добавить
        </button>
    </div>
    <ModalItem
        :key="modalKey"
        :customLimit="customLimit"
        :items="DynamicModalItems()"
        @save="saveItem"
    />
</template>
<script>
import { ref, computed, onMounted, toRefs } from "vue";
import { useRoute } from "vue-router";
import ModalItem from "./ModalItem.vue";
import { Modal } from "bootstrap";
import { fetchWithBaseUrl } from "./../utils/fetchWrapper";
export default {
    components: {
        ModalItem,
    },
    emits: ["rowSelected", "saves"],
    props: {
        columns: Array,
        rows: Array,
        modalItems: Object,
        customLimit: {
            type: Boolean,
            default: false,
        },
    },
    setup(props, { emit }) {
        const route = useRoute();

        const selectedRowIndex = ref(null);
        const selectedItem = ref(null);
        let modal;
        const modalKey = ref(0);
        const selectRow = async (rowIndex, row) => {
            try {
                selectedRowIndex.value = rowIndex;
                selectedItem.value = row;
                emit("rowSelected", rowIndex);
            } catch (error) {
                console.error("Error selecting row:", error);
            }
        };

        const clearSelection = () => {
            selectedRowIndex.value = null;
            selectedItem.value = null;
        };

        const saveItem = (newItem) => {
            console.log(newItem);
            emit("saves", newItem);

            modal.hide();
            modalKey.value++;
        };

        const openAddModal = () => {
            const modalElement = document.getElementById("openAddModal");
            modal = new Modal(modalElement);
            modal.show();
        };

        const DynamicModalItems = () => {
            console.log();
            return Array.isArray(props.modalItems)
                ? props.modalItems
                : props.modalItems[route.params.endpoint];
        };
        return {
            DynamicModalItems,
            openAddModal,
            saveItem,
            clearSelection,
            selectRow,
            selectedRowIndex,
            selectedItem,
            modal,
            modalKey,
        };
    },
};
</script>
<style scoped>
tr {
    cursor: pointer;
}
</style>
