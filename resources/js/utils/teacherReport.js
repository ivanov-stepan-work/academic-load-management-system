export const reportColumns = [
    { label: "Предмет", percent: 60 },
    { label: "Группа", percent: 30 },
    { label: "Часы", percent: 10 },
];

const sectionRow = (name, tableStyle) => [{ name, width: 3, tableStyle, bold: true }];
const emptySectionRow = (name, tableStyle) => sectionRow(`${name} · Всего 0 ч`, tableStyle);
const gapRow = () => [{ name: "", width: 3 }];

function appendTotals(rows, item, tableStyle, showScope) {
    const scope = `· ${item.name}`;
    for (const [label, value] of [
        [showScope ? `Всего часов ${scope}` : "Общее кол-во часов:", item.total_hours],
        [showScope ? `Часов в неделю ${scope}` : "Часов в неделю:", item.total_hours_per_week],
    ]) {
        rows.push([
            {
                name: label,
                width: 2,
                alignment: "end",
                tableStyle,
                bold: true,
            },
            { name: Number(value), tableStyle, bold: true },
        ]);
    }
}

export function buildTeacherRows(semesters, { compact = false } = {}) {
    const rows = [];
    for (const semester of semesters) {
        if (!compact) rows.push(sectionRow(semester.name, "primary"));
        for (const type of semester.types) {
            if (compact && !type.subtypes.some((subtype) => subtype.teacher_loads.length)) {
                rows.push(emptySectionRow(type.name, "active"));
                continue;
            }
            rows.push(sectionRow(type.name, "active"));
            for (const subtype of type.subtypes) {
                if (compact && !subtype.teacher_loads.length) {
                    rows.push(emptySectionRow(subtype.name, "light"));
                    continue;
                }
                rows.push(sectionRow(subtype.name, "light"));
                for (const load of subtype.teacher_loads) {
                    rows.push([
                        { name: load.department_load?.subject?.name ?? "—" },
                        { name: load.department_load?.group?.name ?? "—" },
                        { name: Number(load.hours_allocated) },
                    ]);
                }
                appendTotals(rows, subtype, "light", compact);
            }
            appendTotals(rows, type, "active", compact);
            rows.push(gapRow());
        }
        if (!compact) {
            appendTotals(rows, semester, "primary", false);
            rows.push(gapRow());
        }
    }
    return rows;
}
