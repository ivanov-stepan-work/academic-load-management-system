<template>
    <main class="teacher-page">
        <div class="teacher-layout">
            <header class="teacher-header">
                <div>
                    <p class="teacher-role">Кабинет преподавателя</p>
                    <h1>Учебная нагрузка</h1>
                </div>
                <div class="teacher-account">
                    <strong v-if="accountName">{{ accountName }}</strong>
                    <button class="btn btn-outline-secondary" type="button" :disabled="signingOut" @click="logout">
                        {{ signingOut ? "Выход…" : "Выйти" }}
                    </button>
                </div>
            </header>

            <p v-if="actionError" class="alert alert-danger mb-0" role="alert">{{ actionError }}</p>
            <section v-if="loading" class="teacher-card teacher-empty" role="status">Загружаем учебную нагрузку…</section>
            <section v-else-if="loadError" class="teacher-card teacher-empty">
                <p role="alert">Не удалось загрузить учебную нагрузку.</p>
                <button class="btn btn-outline-primary" type="button" @click="load">Повторить</button>
            </section>
            <section v-else-if="!years.length" class="teacher-card teacher-empty">Учебные годы пока не добавлены.</section>
            <template v-else>
                <section class="teacher-card teacher-toolbar" aria-label="Параметры отчёта">
                    <div>
                        <label for="teacher-year" class="form-label fw-semibold">Учебный год</label>
                        <select id="teacher-year" class="form-select" :value="selectedIndex ?? ''" @change="selectYear">
                            <option v-if="selectedIndex == null" value="" disabled>Выберите учебный год</option>
                            <option v-for="year in yearOptions" :key="year.id" :value="year.index">{{ year.name }}</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="button" :disabled="!semesters.length || exporting" @click="downloadReport">
                        {{ exporting ? "Готовим файл…" : "Скачать Excel (.xlsx)" }}
                    </button>
                </section>

                <section v-if="selectedYear" class="teacher-year-report" aria-labelledby="teacher-report-title">
                    <h2 id="teacher-report-title">Нагрузка за {{ selectedYear.name }} учебный год</h2>
                    <div v-if="semesterReports.length" class="teacher-semesters">
                        <section v-for="{ semester, rows } in semesterReports" :key="semester.value"
                            class="teacher-card teacher-report" :aria-labelledby="'teacher-semester-' + semester.value">
                            <header class="teacher-report-header">
                                <h3 :id="'teacher-semester-' + semester.value">{{ semester.name }}</h3>
                                <p class="teacher-summary">
                                    <strong>{{ formatNumber(semester.total_hours) }} ч</strong>
                                    <span>{{ formatNumber(semester.total_hours_per_week) }} ч/нед.</span>
                                </p>
                            </header>
                            <div v-if="rows.length" class="table-responsive" role="region"
                                :aria-labelledby="'teacher-semester-' + semester.value" tabindex="0">
                                <ResultTable :columns="reportColumns" :rows="rows" />
                            </div>
                            <p v-else class="teacher-muted teacher-empty mb-0">В этом полугодии пока нет разделов нагрузки.</p>
                        </section>
                    </div>
                    <p v-else class="teacher-card teacher-empty mb-0">На этот учебный год нагрузка пока не назначена.</p>
                </section>
                <section v-else class="teacher-card teacher-empty">Выберите учебный год, чтобы посмотреть нагрузку.</section>
            </template>
        </div>
    </main>
</template>

<script setup>
import { computed, inject, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import ResultTable from "../components/ResultTable.vue";
import { buildTeacherRows, reportColumns } from "../utils/teacherReport";

const route = useRoute();
const router = useRouter();
const fetchFunction = inject("fetchFunction");
const user = ref(null);
const years = ref([]);
const loading = ref(true);
const loadError = ref(false);
const actionError = ref("");
const signingOut = ref(false);
const exporting = ref(false);
const numberFormat = new Intl.NumberFormat("ru-RU", { maximumFractionDigits: 1 });
const formatNumber = (value) => numberFormat.format(value);

const accountName = computed(() => {
    if (!user.value) return "";
    const { lastname, firstname, middlename, login } = user.value;
    return [lastname, firstname, middlename].filter(Boolean).join(" ") || login;
});
const yearOptions = computed(() => years.value.map((year, index) => ({ ...year, index }))
    .sort((a, b) => b.name.localeCompare(a.name, "ru", { numeric: true })));
const selectedIndex = computed(() => {
    const endpoint = route.params.endpoint;
    if (endpoint == null) return yearOptions.value[0]?.index;
    return /^\d+$/.test(endpoint) && years.value[Number(endpoint)] ? Number(endpoint) : undefined;
});
const selectedYear = computed(() => years.value[selectedIndex.value]);
const semesters = computed(() => Object.values(selectedYear.value?.items ?? {}));
const semesterReports = computed(() => semesters.value.map((semester) => ({
    semester,
    rows: buildTeacherRows([semester], { compact: true }),
})));

function selectYear(event) {
    router.push("/teacher/" + event.target.value);
}

async function request(url, options) {
    const response = await fetchFunction(url, options);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
}

async function load() {
    loading.value = true;
    loadError.value = false;
    try {
        user.value = await request("/user");
        if (!user.value?.id) throw new Error("User unavailable");
        years.value = await request("/api/teacher/" + user.value.id);
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

async function downloadReport() {
    if (exporting.value || !selectedYear.value || !semesters.value.length) return;
    const rows = buildTeacherRows(semesters.value);
    const year = selectedYear.value.name;
    exporting.value = true;
    actionError.value = "";
    try {
        const { createTeacherWorkbook } = await import("../utils/teacherWorkbook");
        const file = await createTeacherWorkbook(rows, accountName.value);
        const url = URL.createObjectURL(file);
        const link = document.createElement("a");
        link.href = url;
        link.download = `Учебная нагрузка ${year}.xlsx`;
        document.body.append(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch {
        actionError.value = "Не удалось подготовить Excel-файл. Попробуйте ещё раз.";
    } finally {
        exporting.value = false;
    }
}

async function logout() {
    signingOut.value = true;
    actionError.value = "";
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
        await request("/logout", { method: "POST", headers: { "X-CSRF-TOKEN": csrfToken } });
        await router.push("/login");
    } catch {
        actionError.value = "Не удалось выйти из кабинета. Попробуйте ещё раз.";
    } finally {
        signingOut.value = false;
    }
}

onMounted(load);
</script>

<style scoped>
.teacher-page {
    --teacher-muted: #52627a;
    --teacher-border: #dbe3ee;
    min-height: 100vh;
    padding: clamp(1rem, 4vw, 3.5rem);
    background: #f3f6fb;
    color: #17243a;
}

.teacher-layout {
    display: grid;
    gap: 1.15rem;
    max-width: 94rem;
    margin-inline: auto;
}

.teacher-header,
.teacher-account,
.teacher-toolbar {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: .8rem;
}

.teacher-header h1 {
    margin: 0;
    font-size: clamp(1.75rem, 4vw, 2.75rem);
    font-weight: 750;
    letter-spacing: -.035em;
}

.teacher-role {
    margin: 0 0 .35rem;
    color: #234fbd;
    font-weight: 650;
}

.teacher-card {
    min-width: 0;
    border: 1px solid var(--teacher-border);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 4px 16px rgb(27 53 94 / 5%);
}

.teacher-toolbar,
.teacher-report-header {
    padding: clamp(1rem, 2.5vw, 1.35rem);
}

.teacher-toolbar {
    align-items: flex-end;
}

.teacher-toolbar .form-select {
    min-width: 12rem;
}

.teacher-year-report {
    display: grid;
    gap: .85rem;
}

.teacher-year-report h2,
.teacher-report h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
}

.teacher-semesters {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
    gap: 1rem;
}

.teacher-summary {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .35rem .8rem;
    margin: .35rem 0 0;
}

.teacher-summary strong {
    color: #234fbd;
    font-size: 1.3rem;
}

.teacher-muted,
.teacher-summary span {
    color: var(--teacher-muted);
}

.teacher-report .table-responsive {
    border-radius: 0 0 1rem 1rem;
}

.teacher-report :deep(table) {
    min-width: 29rem;
    margin-bottom: 0;
}

.teacher-empty {
    padding: clamp(1.5rem, 5vw, 3rem);
    text-align: center;
}

@media (max-width: 66rem) {
    .teacher-semesters {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
