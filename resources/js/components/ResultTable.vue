<template>
    <table class="teacher-results">
        <thead>
            <tr>
                <th v-for="column in columns" :key="column.label" scope="col" :style="{ width: column.percent + '%' }">
                    {{ column.label }}
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(row, index) in rows" :key="index"
                :class="[
                    row[0]?.tableStyle && 'report-' + row[0].tableStyle,
                    {
                        'report-heading': row[0]?.width === 3 && row[0].bold,
                        'report-total': row[0]?.width === 2,
                        'report-gap': row[0]?.width === 3 && row[0].name === '',
                    },
                ]">
                <td
                    v-for="(cell, cellIndex) in row"
                    :key="cellIndex"
                    :colspan="cell.width"
                    :rowspan="cell.height"
                    :class="{
                        'fw-bold': cell.bold,
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
import { reportColumns } from "../utils/teacherReport";

export default {
    props: {
        columns: {
            type: Array,
            default: () => reportColumns,
        },
        rows: {
            type: Array,
            required: true,
        },
    },
};
</script>

<style scoped>
.teacher-results {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    color: #17243a;
}

.teacher-results th,
.teacher-results td {
    padding: .65rem clamp(.75rem, 2vw, 1.2rem);
    border-bottom: 1px solid #e8edf5;
    overflow-wrap: anywhere;
}

.teacher-results thead th {
    background: #f6f8fc;
    color: #52627a;
    font-size: .875rem;
    text-align: start;
}

.teacher-results thead th:last-child,
.teacher-results td:last-child:not([colspan="3"]) {
    white-space: nowrap;
    text-align: end;
    font-variant-numeric: tabular-nums;
}

.teacher-results thead th:last-child {
    min-width: 5rem;
}

.report-heading.report-active,
.report-total.report-active {
    background: #f1f4f9;
}

.report-heading.report-light {
    background: #f8fafd;
    color: #465872;
}

.report-heading.report-light td {
    padding-inline-start: clamp(1rem, 3vw, 1.8rem);
}

.report-total {
    color: #465872;
    font-size: .875rem;
}

.report-total.report-light {
    background: #fbfcfe;
}

.teacher-results .report-gap td {
    padding: .2rem;
}

.teacher-results tbody tr:last-child td {
    border-bottom: 0;
}
</style>
