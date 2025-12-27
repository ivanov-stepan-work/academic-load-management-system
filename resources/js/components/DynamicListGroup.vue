<template>
    <h4 class="mt-3">{{ header }}</h4>
    <ul class="list-group mt-1">
        <li
            class="mb-1"
            v-for="item in localItems"
            :key="item.id"
            :class="['list-group-item', { active: item.select }]"
            aria-current="item.select ? 'true' : null"
            @click="toggleSelection(item.id)"
        >
            {{ item.name }}
        </li>
    </ul>
</template>

<script>
import { ref, watch } from "vue";

export default {
    name: "DynamicListGroup",
    props: {
        items: {
            type: Array,
            required: true,
        },
        header: {
            type: String,
            required: true,
        },
    },
    setup(props, { emit }) {
        const localItems = ref([...props.items]);

        watch(
            () => props.items,
            (newItems) => {
                localItems.value = [...newItems];
            },
            { deep: true }
        );

        const toggleSelection = (id) => {
            const updatedItems = localItems.value.map((item) =>
                item.id === id ? { ...item, select: !item.select } : item
            );
            localItems.value = updatedItems;
            emit("update:items", updatedItems);
        };

        return {
            localItems,
            toggleSelection,
        };
    },
};
</script>

<style scoped>
li {
    cursor: pointer;
}
</style>
