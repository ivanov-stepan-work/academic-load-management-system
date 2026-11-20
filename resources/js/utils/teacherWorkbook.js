import ExcelJS from "exceljs/dist/exceljs.min.js";
import { reportColumns } from "./teacherReport.js";

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

export async function createTeacherWorkbook(rows, teacherName) {
    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet(worksheetName(teacherName));
    sheet.columns = reportColumns.map(({ percent }) => ({ width: percent }));
    const header = reportColumns.map(({ label }) => ({ name: label, bold: true }));

    [header, ...rows].forEach((row, index) => {
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
