<template>
    <main class="director-page">
        <div class="director-layout">
            <header class="director-header">
                <div>
                    <p class="director-role">Кабинет заместителя директора</p>
                    <h1>Нагрузка кафедр</h1>
                </div>
                <div class="director-account">
                    <strong v-if="accountName">{{ accountName }}</strong>
                    <button type="button" class="btn btn-outline-secondary" :disabled="signingOut || saving" @click="logout">
                        {{ signingOut ? "Выход…" : "Выйти" }}
                    </button>
                </div>
            </header>

            <p v-if="notice" class="alert alert-success mb-0" role="status">{{ notice }}</p>
            <p v-if="actionError" class="alert alert-danger mb-0" role="alert">{{ actionError }}</p>

            <section v-if="loading" class="director-card director-empty" role="status">Загружаем нагрузку кафедр…</section>
            <section v-else-if="loadError" class="director-card director-empty">
                <p role="alert">Не удалось загрузить данные кабинета.</p>
                <button type="button" class="btn btn-outline-primary" @click="load">Повторить</button>
            </section>
            <div v-else class="director-workspace">
                <nav class="director-card director-nav" aria-labelledby="director-departments">
                    <h2 id="director-departments">Кафедры</h2>
                    <ul v-if="departments.length">
                        <li v-for="(department, index) in departments" :key="department.id">
                            <RouterLink :to="departmentPath(index)" class="director-department">
                                <span>{{ department.name }}</span>
                                <small>{{ formatHours(departmentHours(department)) }}</small>
                            </RouterLink>
                        </li>
                    </ul>
                    <p v-else class="director-muted mb-0">Кафедр пока нет.</p>
                </nav>

                <section v-if="!activeDepartment" class="director-card director-empty">
                    <h2>{{ departments.length ? "Выберите кафедру" : "Нет кафедр для распределения нагрузки" }}</h2>
                </section>
                <section v-else class="director-card director-detail" aria-labelledby="director-title">
                    <header class="director-detail-header">
                        <div>
                            <h2 id="director-title" ref="departmentHeading" tabindex="-1">{{ activeDepartment.name }}</h2>
                            <p class="director-muted mb-0">Выделено {{ formatHours(departmentHours(activeDepartment)) }}</p>
                        </div>
                        <button type="button" class="btn btn-primary" :disabled="saving || !!draft || !!pendingDelete || !canAdd"
                            @click="openEditor(null, $event.currentTarget)">
                            Добавить нагрузку
                        </button>
                    </header>
                    <p v-if="!canAdd" class="director-muted director-inset">Для добавления нагрузки нужны учебные группы и предметы.</p>

                    <form v-if="draft" class="director-editor" aria-labelledby="director-editor-title" @submit.prevent="saveRecord">
                        <h3 id="director-editor-title">{{ draft.id == null ? "Добавить нагрузку" : "Редактировать нагрузку" }}</h3>
                        <p v-if="editorError" class="alert alert-danger" role="alert">{{ editorError }}</p>
                        <fieldset :disabled="saving">
                            <div class="director-fields">
                                <div>
                                    <label for="director-group">Учебная группа</label>
                                    <select id="director-group" ref="groupField" v-model="draft.group_id" class="form-select" required>
                                        <option value="" disabled>Выберите группу</option>
                                        <option v-for="group in groups" :key="group.id.value" :value="group.id.value">{{ group.name.value }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="director-subject">Предмет</label>
                                    <select id="director-subject" v-model="draft.subject_id" class="form-select" required>
                                        <option value="" disabled>Выберите предмет</option>
                                        <option v-for="subject in subjects" :key="subject.id.value" :value="subject.id.value">{{ subject.name.value }}</option>
                                    </select>
                                </div>
                                <div v-if="draft.id != null">
                                    <label for="director-department">Кафедра</label>
                                    <select id="director-department" v-model="draft.department_id" class="form-select" required>
                                        <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="director-semester">Семестр</label>
                                    <input id="director-semester" v-model.number="draft.semester" class="form-control" type="number" min="1" max="10" required />
                                </div>
                                <div>
                                    <label for="director-hours">Выделенные часы</label>
                                    <input id="director-hours" v-model.number="draft.hours_allocated" class="form-control" type="number" min="1" max="10000" required />
                                </div>
                            </div>
                            <div class="director-actions">
                                <button type="button" class="btn btn-outline-secondary" @click="cancelEditor">Отмена</button>
                                <button type="submit" class="btn btn-primary">{{ saving ? "Сохраняем…" : "Сохранить" }}</button>
                            </div>
                        </fieldset>
                    </form>

                    <template v-if="activeDepartment.rows.length">
                        <div class="director-inset director-search">
                            <label for="director-search">Поиск по группе или предмету</label>
                            <input id="director-search" v-model.trim="search" type="search" class="form-control" />
                        </div>

                        <div v-if="visibleRows.length" class="table-responsive" role="region" aria-label="Нагрузка кафедры" tabindex="0">
                            <table class="table director-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Группа</th>
                                        <th scope="col">Предмет</th>
                                        <th scope="col" class="text-end">Семестр</th>
                                        <th scope="col" class="text-end">Часы</th>
                                        <th scope="col"><span class="visually-hidden">Действия</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="row in visibleRows" :key="row.id">
                                        <tr :class="{ 'table-active': draft?.id === row.id }">
                                            <td>{{ row.group?.name ?? "—" }}</td>
                                            <th scope="row">{{ row.subject?.name ?? "—" }}</th>
                                            <td class="text-end">{{ row.semester }}</td>
                                            <td class="text-end">{{ numberFormat.format(row.hours_allocated) }}</td>
                                            <td>
                                                <div class="director-row-actions">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" :disabled="saving || !!draft || !!pendingDelete"
                                                        @click="openEditor(row, $event.currentTarget)">Изменить</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" :disabled="saving || !!draft || !!pendingDelete"
                                                        @click="askDelete(row, $event.currentTarget)">Удалить</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="pendingDelete?.id === row.id">
                                            <td colspan="5">
                                                <div class="alert alert-danger mb-0 director-confirm">
                                                    <div>
                                                        <strong>Удалить запись о нагрузке?</strong>
                                                        <p class="mb-0">{{ row.subject?.name }} · {{ row.group?.name }} · {{ formatHours(row.hours_allocated) }}</p>
                                                        <p v-if="deleteError" class="mb-0 mt-2" role="alert">{{ deleteError }}</p>
                                                    </div>
                                                    <div class="director-actions">
                                                        <button type="button" class="btn btn-outline-secondary" data-cancel-delete :disabled="saving" @click="cancelDelete">Отмена</button>
                                                        <button type="button" class="btn btn-danger" :disabled="saving" @click="deleteRecord">
                                                            {{ saving ? "Удаляем…" : "Подтвердить удаление" }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="director-empty director-muted mb-0">По этому запросу ничего не найдено.</p>
                    </template>
                    <p v-else class="director-empty director-muted mb-0">Для этой кафедры пока нет записей о нагрузке.</p>
                </section>
            </div>
        </div>
    </main>
</template>

<script setup>
import { computed, inject, nextTick, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

const basePath = "/assistantDirector";
const apiPath = "/api/assistantDirector";
const numberFormat = new Intl.NumberFormat("ru-RU");
const formatHours = (hours) => numberFormat.format(hours) + " ч";
const departmentPath = (index) => basePath + "/" + (index + 1);
const fetchFunction = inject("fetchFunction");
const route = useRoute();
const router = useRouter();

const user = ref(null);
const departments = ref([]);
const groups = ref([]);
const subjects = ref([]);
const loading = ref(true);
const loadError = ref(false);
const saving = ref(false);
const signingOut = ref(false);
const search = ref("");
const notice = ref("");
const actionError = ref("");
const editorError = ref("");
const deleteError = ref("");
const draft = ref(null);
const pendingDelete = ref(null);
const departmentHeading = ref(null);
const groupField = ref(null);
let actionTrigger;

const accountName = computed(() => {
    if (!user.value) return "";
    const { lastname, firstname, middlename, login } = user.value;
    return [lastname, firstname, middlename].filter(Boolean).join(" ") || login;
});
const activeDepartment = computed(() => {
    const endpoint = route.params.endpoint;
    return typeof endpoint === "string" && /^[1-9]\d*$/.test(endpoint)
        ? departments.value[Number(endpoint) - 1]
        : undefined;
});
const canAdd = computed(() => groups.value.length > 0 && subjects.value.length > 0);
const departmentHours = (department) => department.rows.reduce((sum, row) => sum + Number(row.hours_allocated), 0);
const visibleRows = computed(() => {
    const query = search.value.toLocaleLowerCase("ru-RU");
    const rows = activeDepartment.value?.rows ?? [];
    if (!query) return rows;
    return rows.filter((row) =>
        row.id === pendingDelete.value?.id ||
        [row.group?.name, row.subject?.name].some((name) => name?.toLocaleLowerCase("ru-RU").includes(query))
    );
});

async function request(url, method = "GET", payload) {
    const headers = { Accept: "application/json" };
    if (method !== "GET") {
        headers["X-CSRF-TOKEN"] = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
    }
    if (payload) headers["Content-Type"] = "application/json";
    const response = await fetchFunction(url, { method, headers, body: payload ? JSON.stringify(payload) : undefined });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
}

async function load() {
    loading.value = true;
    loadError.value = false;
    try {
        user.value = await request("/user");
        if (!user.value?.id) throw new Error("User unavailable");
        const data = await request(apiPath + "/" + user.value.id);
        departments.value = data.departments.map((department) => ({
            id: department.id.value,
            name: department.name.value,
            rows: Object.values(department.department_loads.value ?? {}),
        }));
        groups.value = data.groups;
        subjects.value = data.subjects;
        if (route.params.endpoint && !activeDepartment.value) await router.replace(basePath);
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

async function openEditor(row, trigger) {
    if (saving.value || draft.value || pendingDelete.value || !activeDepartment.value) return;
    notice.value = "";
    editorError.value = "";
    actionTrigger = trigger;
    draft.value = {
        id: row?.id ?? null,
        department_id: activeDepartment.value.id,
        group_id: row?.group_id ?? row?.group?.id ?? "",
        subject_id: row?.subject_id ?? row?.subject?.id ?? "",
        semester: row?.semester ?? "",
        hours_allocated: row?.hours_allocated ?? "",
    };
    await nextTick();
    groupField.value?.focus();
}

async function restoreFocus() {
    await nextTick();
    if (actionTrigger?.isConnected && !actionTrigger.disabled) actionTrigger.focus();
    else departmentHeading.value?.focus();
    actionTrigger = null;
}

function cancelEditor() {
    draft.value = null;
    restoreFocus();
}

async function saveRecord() {
    if (!draft.value || !activeDepartment.value || saving.value) return;
    const source = activeDepartment.value;
    const { id, ...payload } = draft.value;
    const creating = id == null;
    saving.value = true;
    editorError.value = "";
    try {
        const row = creating
            ? await request(apiPath, "POST", { ...payload, user_id: user.value.id })
            : await request(apiPath + "/" + id, "PUT", payload);
        const destination = departments.value.find((department) => department.id === row.department_id);
        if (!creating && destination === source) {
            const index = source.rows.findIndex((item) => item.id === id);
            if (index !== -1) source.rows.splice(index, 1, row);
        } else {
            if (!creating) source.rows = source.rows.filter((item) => item.id !== id);
            destination?.rows.push(row);
        }
        notice.value = creating
            ? "Нагрузка добавлена."
            : destination && destination !== source
                ? `Запись перенесена в кафедру «${destination.name}».`
                : "Изменения сохранены.";
        draft.value = null;
        restoreFocus();
    } catch {
        const message = "Не удалось сохранить запись. Проверьте данные и попробуйте снова.";
        if (draft.value) editorError.value = message;
        else actionError.value = message;
    } finally {
        saving.value = false;
    }
}

async function askDelete(row, trigger) {
    notice.value = "";
    deleteError.value = "";
    actionTrigger = trigger;
    pendingDelete.value = row;
    await nextTick();
    document.querySelector("[data-cancel-delete]")?.focus();
}

function cancelDelete() {
    pendingDelete.value = null;
    restoreFocus();
}

async function deleteRecord() {
    if (!pendingDelete.value || !activeDepartment.value || saving.value) return;
    const { id } = pendingDelete.value;
    const department = activeDepartment.value;
    saving.value = true;
    deleteError.value = "";
    try {
        await request(apiPath + "/" + id, "DELETE");
        department.rows = department.rows.filter((row) => row.id !== id);
        notice.value = "Запись удалена.";
        pendingDelete.value = null;
        restoreFocus();
    } catch {
        const message = "Не удалось удалить запись. Повторите попытку.";
        if (pendingDelete.value) deleteError.value = message;
        else actionError.value = message;
    } finally {
        saving.value = false;
    }
}

async function logout() {
    signingOut.value = true;
    actionError.value = "";
    try {
        await request("/logout", "POST");
        await router.push("/login");
    } catch {
        actionError.value = "Не удалось выйти из кабинета. Повторите попытку.";
    } finally {
        signingOut.value = false;
    }
}

watch(() => route.params.endpoint, () => {
    draft.value = null;
    pendingDelete.value = null;
    actionTrigger = null;
    search.value = "";
    notice.value = "";
});
onMounted(load);
</script>

<style scoped>
.director-page {
    --director-text: #17243a;
    --director-muted: #52627a;
    --director-accent: #234fbd;
    --director-border: #dbe3ee;
    min-height: 100vh;
    padding: clamp(1rem, 4vw, 3.5rem);
    background: #f3f6fb;
    color: var(--director-text);
}

.director-layout {
    display: grid;
    gap: 1.25rem;
    max-width: 84rem;
    margin-inline: auto;
}

.director-header,
.director-account,
.director-detail-header,
.director-confirm {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .85rem;
}

.director-header h1 {
    margin: 0;
    font-size: clamp(1.75rem, 4vw, 2.75rem);
    font-weight: 750;
    letter-spacing: -.035em;
}

.director-role {
    margin: 0 0 .35rem;
    color: var(--director-accent);
    font-weight: 650;
}

.director-workspace {
    display: grid;
    grid-template-columns: minmax(13rem, 1fr) minmax(0, 3fr);
    align-items: start;
    gap: 1rem;
}

.director-card {
    min-width: 0;
    border: 1px solid var(--director-border);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 4px 16px rgb(27 53 94 / 5%);
}

.director-card h2,
.director-card h3 {
    margin: 0 0 .35rem;
    font-size: 1.2rem;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.director-nav,
.director-detail-header {
    padding: 1.15rem;
}

.director-nav ul {
    display: grid;
    gap: .4rem;
    margin: .85rem 0 0;
    padding: 0;
    list-style: none;
}

.director-department {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: .5rem;
    padding: .65rem .7rem;
    border: 1px solid var(--director-border);
    border-radius: .55rem;
    color: var(--director-text);
    text-decoration: none;
    overflow-wrap: anywhere;
}

.director-department small {
    color: var(--director-muted);
    white-space: nowrap;
}

.director-department:hover,
.director-department[aria-current="page"] {
    border-color: #a8bce8;
    background: #eef3fc;
    color: var(--director-accent);
}

.director-department[aria-current="page"] {
    font-weight: 700;
}

.director-inset {
    margin: 0 1.15rem 1rem;
}

.director-muted {
    color: var(--director-muted);
}

.director-empty {
    padding: clamp(1.25rem, 5vw, 3rem);
    text-align: center;
}

.director-editor {
    margin: 0 1.15rem 1rem;
    padding: 1rem;
    border: 1px solid #bacbec;
    border-radius: .75rem;
    background: #f5f8ff;
}

.director-editor h3 {
    margin-bottom: .85rem;
}

.director-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr));
    gap: .75rem;
}

.director-fields label,
.director-search label {
    display: block;
    margin-bottom: .3rem;
    font-size: .9rem;
    font-weight: 650;
}

.director-search {
    max-width: 24rem;
}

.director-actions,
.director-row-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: .5rem;
}

.director-editor .director-actions {
    margin-top: .9rem;
}

.director-detail .table-responsive {
    border-radius: 0 0 1rem 1rem;
}

.director-table {
    min-width: 32rem;
    --bs-table-border-color: var(--director-border);
}

.director-table thead th {
    background: #f5f7fb;
    color: var(--director-muted);
    font-size: .875rem;
}

.director-table > :not(caption) > * > * {
    padding: .7rem .85rem;
}

.director-table tbody tr:last-child > * {
    border-bottom: 0;
}

.director-table th[scope="row"] {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.director-table td.text-end {
    font-variant-numeric: tabular-nums;
}

@media (max-width: 48rem) {
    .director-workspace {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
