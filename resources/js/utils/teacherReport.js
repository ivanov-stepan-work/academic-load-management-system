export const reportColumns = [
    { label: "Предмет", percent: 56 },
    { label: "Группа", percent: 30 },
    { label: "Часы", percent: 14 },
];

const sectionRow = (name, tableStyle) => [{ name, width: 3, tableStyle, bold: true }];
const emptySectionRow = (name, tableStyle) => sectionRow(`${name} · Всего 0 ч`, tableStyle);
const gapRow = () => [{ name: "", width: 3 }];

function appendTotals(rows, item, tableStyle) {
    const scope = `· ${item.name}`;
    for (const [label, value] of [
        [`Всего часов ${scope}`, item.total_hours],
        [`Часов в неделю ${scope}`, item.total_hours_per_week],
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

export function buildTeacherSemesterRows(semester) {
    const rows = [];
    for (const type of semester.types) {
        if (!type.subtypes.some((subtype) => subtype.teacher_loads.length)) {
            rows.push(emptySectionRow(type.name, "active"));
            continue;
        }
        rows.push(sectionRow(type.name, "active"));
        for (const subtype of type.subtypes) {
            if (!subtype.teacher_loads.length) {
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
            appendTotals(rows, subtype, "light");
        }
        appendTotals(rows, type, "active");
        rows.push(gapRow());
    }
    if (rows.at(-1)?.[0].name === "") rows.pop();
    return rows;
}
