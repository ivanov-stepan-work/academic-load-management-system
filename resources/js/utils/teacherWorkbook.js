import ExcelJS from "exceljs/dist/exceljs.min.js";
import { buildTeacherSemesterRows, reportColumns } from "./teacherReport.js";

const mimeType = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

function worksheetName(teacherName) {
    const name = (teacherName ?? "")
        .replace(/[*?:/\\[\]]/g, " ")
        .replace(/\s+/g, " ")
        .trim()
        .slice(0, 31)
        .replace(/[\uD800-\uDBFF]$/, "")
        .replace(/^[ ']+|[ ']+$/g, "");
    return name && name.toLowerCase() !== "history" ? name : "Учебная нагрузка";
}

function semesterRows(semester) {
    const summary = [
        ["Всего часов за полугодие", semester.total_hours],
        ["Часов в неделю", semester.total_hours_per_week],
    ].map(([label, hours]) => [
        { name: label, width: 2, bold: true },
        { name: Number(hours), bold: true },
    ]);
    return [
        [{ name: semester.name, width: 3, bold: true }],
        ...summary,
        reportColumns.map(({ label }) => ({ name: label, bold: true })),
        ...buildTeacherSemesterRows(semester),
        [],
    ];
}

export async function createTeacherWorkbook(semesters, teacherName) {
    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet(worksheetName(teacherName));
    sheet.columns = [{ width: 60 }, { width: 24 }, { width: 14 }];

    semesters.flatMap(semesterRows).forEach((row, index) => {
        const rowNumber = index + 1;
        let column = 1;
        for (const item of row) {
            const width = item.width ?? 1;
            if (width > 1) sheet.mergeCells(rowNumber, column, rowNumber, column + width - 1);

            const cell = sheet.getCell(rowNumber, column);
            cell.value = item.name;
            if (item.bold) cell.font = { bold: true };
            column += width;
        }
    });

    return new Blob([await workbook.xlsx.writeBuffer()], { type: mimeType });
}
