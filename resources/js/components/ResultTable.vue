<template>
    <table class="table text-center table-bordered">
        <thead>
            <tr>
                <th style="width: 60%">Предмет</th>
                <th style="width: 30%">Группа</th>
                <th style="width: 10%">Часы</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(row, index) in rows" :key="index">
                <td
                    v-for="(cell, cellIndex) in row"
                    :key="cellIndex"
                    :colspan="cell.width"
                    :rowspan="cell.height"
                    :class="{
                        'fw-bold': cell.bold,
                        ['table-' + cell.tableStyle]: cell.tableStyle,
                        ['text-' + cell.alignment]: cell.alignment,
                    }"
                >
                    {{ cell.name }}
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
export default {
    props: {
        rows: {
            type: Array,
            required: true,
        },
    },
    methods: {
        // Другие методы компонента
    },
};
</script>
