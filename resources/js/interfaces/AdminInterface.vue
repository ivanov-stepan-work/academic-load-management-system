<template>
    <main class="admin-page">
        <div class="admin-layout">
            <header class="admin-header">
                <div>
                    <p class="admin-role">Кабинет администратора</p>
                    <h1>Управление данными</h1>
                </div>
                <div class="admin-account">
                    <strong v-if="accountName">{{ accountName }}</strong>
                    <button type="button" class="btn btn-outline-secondary" :disabled="saving || signingOut" @click="logout">
                        {{ signingOut ? "Выход…" : "Выйти" }}
                    </button>
                </div>
            </header>

            <p v-if="notice" class="alert alert-success mb-0" role="status">{{ notice }}</p>
            <p v-if="actionError" class="alert alert-danger mb-0" role="alert">{{ actionError }}</p>

            <nav class="admin-card admin-nav" aria-label="Разделы управления данными">
                <RouterLink v-for="(item, key) in sections" :key="key" :to="'/admin/' + key" class="admin-tab">
                    {{ item.title }}
                </RouterLink>
            </nav>

            <section v-if="loading" class="admin-card admin-empty" role="status">Загружаем данные…</section>
            <section v-else-if="loadError" class="admin-card admin-empty">
                <p role="alert">Не удалось загрузить данные кабинета.</p>
                <button type="button" class="btn btn-outline-primary" @click="load">Повторить</button>
            </section>
            <section v-else-if="!section" class="admin-card admin-empty">
                <h2>{{ route.params.endpoint ? "Раздел не найден" : "Выберите раздел" }}</h2>
            </section>
            <section v-else class="admin-card admin-detail" aria-labelledby="admin-section-title">
                <header class="admin-section-header">
                    <div>
                        <h2 id="admin-section-title" ref="sectionHeading" tabindex="-1">{{ section.title }}</h2>
                        <p class="admin-muted mb-0">Записей {{ numberFormat.format(records.length) }}</p>
                    </div>
                    <button type="button" class="btn btn-primary" :disabled="working || !!draft || !!deleting"
                        @click="openEditor(null, $event.currentTarget)">Добавить запись</button>
                </header>

                <form v-if="draft" class="admin-editor" aria-labelledby="admin-editor-title" @submit.prevent="saveRecord">
                    <h3 id="admin-editor-title" ref="editorHeading" tabindex="-1">
                        {{ draft.id == null ? "Добавить запись" : "Редактировать запись" }}
                    </h3>
                    <p v-if="operationError" class="alert alert-danger" role="alert">{{ operationError }}</p>
                    <fieldset :disabled="working">
                        <div class="admin-fields">
                            <template v-for="field in visibleFields" :key="field.key">
                                <fieldset v-if="field.type === 'checkboxes'" class="admin-choices">
                                    <legend>{{ field.label }}</legend>
                                    <div v-if="field.options.length" class="admin-choice-list">
                                        <label v-for="option in field.options" :key="option.id" class="admin-choice">
                                            <input v-model="draft.values[field.key]" type="checkbox" class="form-check-input" :value="option.id" />
                                            {{ option.label }}
                                        </label>
                                    </div>
                                    <p v-else class="admin-muted mb-0">Нет доступных вариантов.</p>
                                </fieldset>
                                <div v-else>
                                    <label :for="'admin-' + field.key">{{ field.label }}</label>
                                    <select v-if="field.type === 'select'" :id="'admin-' + field.key" v-model="draft.values[field.key]"
                                        class="form-select" :required="field.required">
                                        <option :value="null" :disabled="field.required">{{ field.required ? "Выберите из списка" : "Не назначен" }}</option>
                                        <option v-for="option in field.options" :key="option.id" :value="option.id">{{ option.label }}</option>
                                    </select>
                                    <input v-else :id="'admin-' + field.key" v-model="draft.values[field.key]" class="form-control"
                                        :type="field.type ?? 'text'" :required="field.required" :min="field.min"
                                        :max="field.max" :minlength="field.type === 'password' ? 8 : undefined"
                                        :autocomplete="field.type === 'password' ? 'new-password' : undefined" />
                                    <p v-if="field.type === 'password'" class="form-text mb-0">Минимум 8 символов.</p>
                                </div>
                            </template>
                        </div>
                        <div class="admin-actions">
                            <button type="button" class="btn btn-outline-secondary" @click="closeAction">Отмена</button>
                            <button type="submit" class="btn btn-primary">{{ saving ? "Сохраняем…" : "Сохранить" }}</button>
                        </div>
                    </fieldset>
                </form>

                <div v-if="records.length" class="admin-search">
                    <label for="admin-search">Поиск в разделе</label>
                    <input id="admin-search" v-model.trim="search" type="search" class="form-control" />
                </div>
                <div v-if="visibleRows.length" class="table-responsive" role="region" aria-labelledby="admin-section-title" tabindex="0">
                    <table class="table admin-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th v-for="[, label] in section.columns" :key="label" scope="col">{{ label }}</th>
                                <th scope="col"><span class="visually-hidden">Действия</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="{ id, record, cells } in visibleRows" :key="id">
                                <tr :class="{ 'table-active': draft?.id === id }">
                                    <th scope="row">{{ cells[0] || "—" }}</th>
                                    <td v-for="(cell, index) in cells.slice(1)" :key="index">{{ cell || "—" }}</td>
                                    <td>
                                        <div class="admin-row-actions">
                                            <button type="button" class="btn btn-sm btn-outline-primary" :disabled="working || !!draft || !!deleting"
                                                @click="openEditor(record, $event.currentTarget)">Изменить</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" :disabled="working || !!draft || !!deleting"
                                                @click="askDelete(id, $event.currentTarget)">Удалить</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="deleting?.id === id">
                                    <td :colspan="section.columns.length + 1">
                                        <div class="alert alert-danger mb-0">
                                            <strong>Удалить запись «{{ cells[0] }}»?</strong>
                                            <p v-if="operationError" class="mb-0 mt-2" role="alert">{{ operationError }}</p>
                                            <div class="admin-actions">
                                                <button type="button" class="btn btn-outline-secondary" data-admin-cancel-delete :disabled="working" @click="closeAction">Отмена</button>
                                                <button type="button" class="btn btn-danger" :disabled="working" @click="deleteRecord">
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
                <p v-else class="admin-empty admin-muted mb-0">
                    {{ records.length ? "По этому запросу ничего не найдено." : "В этом разделе пока нет записей." }}
                </p>
            </section>
        </div>
    </main>
</template>

<script setup>
import { computed, inject, nextTick, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

const nameSection = (title, label) => ({
    title,
    columns: [["name", label]],
    fields: [{ key: "name", label, required: true }],
});

const sections = {
    users: {
        title: "Пользователи",
        columns: [["login", "Логин"], ["fullName", "ФИО"], ["email", "Почта"], ["roles", "Роли"], ["teacher_statuses", "Статусы"], ["departments", "Кафедры"]],
        fields: [
            { key: "login", label: "Логин", required: true },
            { key: "password", label: "Пароль", type: "password", required: true, createOnly: true },
            { key: "email", label: "Электронная почта", type: "email" },
            { key: "lastname", label: "Фамилия" },
            { key: "firstname", label: "Имя" },
            { key: "middlename", label: "Отчество" },
            { key: "roles", label: "Роли", type: "checkboxes", source: "roles" },
            { key: "teacher_statuses", label: "Статусы преподавателя", type: "checkboxes", source: "teacher_statuses" },
            { key: "departments", label: "Кафедры", type: "checkboxes", source: "departments" },
        ],
    },
    departments: {
        title: "Кафедры",
        columns: [["name", "Название"], ["user", "Заведующий кафедрой"]],
        fields: [
            { key: "name", label: "Название кафедры", required: true },
            { key: "user_id", label: "Заведующий кафедрой", type: "select", source: "users", relation: "user" },
        ],
    },
    specialties: {
        title: "Специальности",
        columns: [["name", "Название"], ["department", "Кафедра"]],
        fields: [
            { key: "name", label: "Название специальности", required: true },
            { key: "department_id", label: "Кафедра", type: "select", source: "departments", relation: "department", required: true },
        ],
    },
    groups: {
        title: "Группы",
        columns: [["name", "Название"], ["student_count", "Студентов"], ["specialty", "Специальность"]],
        fields: [
            { key: "name", label: "Название группы", required: true },
            { key: "student_count", label: "Количество студентов", type: "number", min: 1, max: 1000, required: true },
            { key: "specialty_id", label: "Специальность", type: "select", source: "specialties", relation: "specialty", required: true },
        ],
    },
    subjects: nameSection("Предметы", "Название предмета"),
    study_years: {
        title: "Учебные годы",
        columns: [["name", "Учебный год"]],
        fields: [
            { key: "start_year", label: "Год начала", type: "number", required: true },
            { key: "end_year", label: "Год окончания", type: "number", required: true },
        ],
    },
    types: nameSection("Формы обучения", "Название формы обучения"),
    subtypes: nameSection("Виды деятельности", "Название вида деятельности"),
    teacher_statuses: nameSection("Статусы преподавателей", "Название статуса"),
};

const route = useRoute();
const router = useRouter();
const fetchFunction = inject("fetchFunction");
const numberFormat = new Intl.NumberFormat("ru-RU");
const data = ref({});
const user = ref(null);
const loading = ref(true);
const loadError = ref(false);
const saving = ref(false);
const signingOut = ref(false);
const search = ref("");
const notice = ref("");
const actionError = ref("");
const operationError = ref("");
const draft = ref(null);
const deleting = ref(null);
const sectionHeading = ref(null);
const editorHeading = ref(null);
let actionTrigger;

const wrappedValue = (record, key) => record?.[key]?.value;
const fullName = (person) => [person.lastname, person.firstname, person.middlename].filter(Boolean).join(" ");
const formattedName = (record) => fullName({
    lastname: wrappedValue(record, "lastname"),
    firstname: wrappedValue(record, "firstname"),
    middlename: wrappedValue(record, "middlename"),
});
const accountName = computed(() => {
    if (!user.value) return "";
    const account = data.value.users?.find((item) => wrappedValue(item, "id") === user.value.id);
    return account
        ? formattedName(account) || wrappedValue(account, "login")
        : fullName(user.value) || user.value.login;
});
const endpoint = computed(() => route.params.endpoint);
const section = computed(() => Object.hasOwn(sections, endpoint.value) ? sections[endpoint.value] : null);
const records = computed(() => section.value ? data.value[endpoint.value] ?? [] : []);
const working = computed(() => saving.value || signingOut.value);

function cellText(record, key) {
    if (key === "fullName") return formattedName(record);
    const value = wrappedValue(record, key);
    if (Array.isArray(value)) return value.map((item) => item.name).join(", ");
    if (key === "user" && value) return fullName(value) || value.login;
    return String(value?.name ?? value ?? "");
}

const visibleRows = computed(() => {
    const query = search.value.toLocaleLowerCase("ru-RU");
    return records.value.map((record) => ({
        id: wrappedValue(record, "id"),
        record,
        cells: section.value.columns.map(([key]) => cellText(record, key)),
    })).filter(({ id, cells }) => id === deleting.value?.id ||
        cells.some((cell) => cell.toLocaleLowerCase("ru-RU").includes(query)));
});

function fieldOptions(field) {
    const options = data.value[field.source] ?? [];
    if (field.source !== "users") {
        return options.map((item) => ({ id: wrappedValue(item, "id"), label: wrappedValue(item, "name") }));
    }
    const currentId = draft.value?.values[field.key];
    return options.filter((item) => wrappedValue(item, "id") === currentId ||
        wrappedValue(item, "roles")?.some((role) => Number(role.id) === 3))
        .map((item) => ({ id: wrappedValue(item, "id"), label: formattedName(item) || wrappedValue(item, "login") }));
}

const visibleFields = computed(() => (section.value?.fields ?? [])
    .filter((field) => !field.createOnly || draft.value?.id == null)
    .map((field) => field.source ? { ...field, options: fieldOptions(field) } : field));

async function request(url, method = "GET", payload) {
    const headers = { Accept: "application/json" };
    if (method !== "GET") headers["X-CSRF-TOKEN"] = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
    if (payload) headers["Content-Type"] = "application/json";
    const response = await fetchFunction(url, { method, headers, body: payload ? JSON.stringify(payload) : undefined });
    if (!response.ok) {
        const error = new Error(`HTTP ${response.status}`);
        error.status = response.status;
        throw error;
    }
    return response.status === 204 ? null : response.json();
}

async function load() {
    loading.value = true;
    loadError.value = false;
    try {
        if (!user.value) user.value = await request("/user");
        if (!user.value?.id) throw new Error("User unavailable");
        data.value = await request("/api/admin");
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

async function openEditor(record, trigger) {
    const id = wrappedValue(record, "id") ?? null;
    const values = {};
    for (const field of section.value.fields) {
        if (field.createOnly && id != null) continue;
        const value = wrappedValue(record, field.relation ?? field.key);
        if (field.type === "checkboxes") values[field.key] = (value ?? []).map((item) => item.id);
        else if (field.type === "select") values[field.key] = value?.id ?? null;
        else values[field.key] = value ?? "";
    }
    notice.value = "";
    operationError.value = "";
    actionTrigger = trigger;
    draft.value = { id, endpoint: endpoint.value, values };
    await nextTick();
    editorHeading.value?.focus();
}

async function askDelete(id, trigger) {
    notice.value = "";
    operationError.value = "";
    actionTrigger = trigger;
    deleting.value = { id, endpoint: endpoint.value };
    await nextTick();
    document.querySelector("[data-admin-cancel-delete]")?.focus();
}

async function closeAction() {
    draft.value = null;
    deleting.value = null;
    const trigger = actionTrigger;
    actionTrigger = null;
    await nextTick();
    if (trigger?.isConnected && !trigger.disabled) trigger.focus();
    else sectionHeading.value?.focus();
}

async function applyChange(url, method, payload, successMessage) {
    if (working.value) return;
    const origin = endpoint.value;
    saving.value = true;
    operationError.value = "";
    actionError.value = "";
    try {
        await request(url, method, payload);
        notice.value = successMessage;
        try {
            data.value = await request("/api/admin");
        } catch {
            loadError.value = true;
        }
        saving.value = false;
        if (endpoint.value === origin) await closeAction();
    } catch (error) {
        const message = error.status === 422
            ? "Сервер отклонил данные. Проверьте заполнение и уникальность значений."
            : method === "DELETE"
                ? "Не удалось удалить запись. Проверьте связанные данные."
                : "Не удалось сохранить запись. Повторите попытку.";
        if (endpoint.value === origin) operationError.value = message;
        else actionError.value = message;
    } finally {
        saving.value = false;
    }
}

function saveRecord() {
    if (!draft.value) return;
    const { id, endpoint, values } = draft.value;
    const url = "/api/" + endpoint;
    if (id == null) return applyChange(url, "POST", values, "Запись добавлена.");
    return applyChange(url + "/" + id, "PUT", values, "Изменения сохранены.");
}

function deleteRecord() {
    if (!deleting.value) return;
    const { id, endpoint } = deleting.value;
    return applyChange("/api/" + endpoint + "/" + id, "DELETE", undefined, "Запись удалена.");
}

async function logout() {
    if (working.value) return;
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

watch(endpoint, () => {
    draft.value = null;
    deleting.value = null;
    actionTrigger = null;
    search.value = "";
    notice.value = "";
});
onMounted(load);
</script>

<style scoped>
.admin-page {
    --admin-text: #17243a;
    --admin-muted: #52627a;
    --admin-accent: #234fbd;
    --admin-border: #dbe3ee;
    min-height: 100vh;
    padding: clamp(1rem, 4vw, 3.5rem);
    background: #f3f6fb;
    color: var(--admin-text);
}

.admin-layout {
    display: grid;
    gap: 1.2rem;
    max-width: 84rem;
    margin-inline: auto;
}

.admin-header,
.admin-account,
.admin-section-header,
.admin-nav,
.admin-actions,
.admin-row-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .65rem;
}

.admin-header,
.admin-section-header {
    justify-content: space-between;
}

.admin-header h1 {
    margin: 0;
    font-size: clamp(1.75rem, 4vw, 2.75rem);
    font-weight: 750;
    letter-spacing: -.035em;
}

.admin-role {
    margin: 0 0 .35rem;
    color: var(--admin-accent);
    font-weight: 650;
}

.admin-card {
    min-width: 0;
    border: 1px solid var(--admin-border);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 4px 16px rgb(27 53 94 / 5%);
}

.admin-nav,
.admin-section-header {
    padding: 1rem 1.2rem;
}

.admin-nav {
    flex-wrap: nowrap;
    overflow-x: auto;
}

.admin-tab:first-child {
    margin-inline-start: auto;
}

.admin-tab:last-child {
    margin-inline-end: auto;
}

.admin-tab {
    flex: 0 0 auto;
    padding: .5rem .75rem;
    border: 1px solid var(--admin-border);
    border-radius: .55rem;
    color: var(--admin-text);
    text-decoration: none;
    white-space: nowrap;
}

.admin-tab:hover,
.admin-tab[aria-current="page"] {
    border-color: #b4c8ed;
    background: #eef3fc;
    color: var(--admin-accent);
}

.admin-tab[aria-current="page"] {
    border-color: var(--admin-accent);
}

.admin-card h2,
.admin-card h3 {
    margin: 0 0 .35rem;
    font-size: 1.2rem;
    font-weight: 700;
}

.admin-empty {
    padding: clamp(1.25rem, 5vw, 3rem);
    text-align: center;
}

.admin-muted {
    color: var(--admin-muted);
}

.admin-editor,
.admin-search {
    margin: 0 1.2rem 1rem;
}

.admin-editor {
    padding: 1rem;
    border: 1px solid #bacbec;
    border-radius: .75rem;
    background: #f5f8ff;
}

.admin-editor h3 {
    margin-bottom: 1rem;
}

.admin-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr));
    gap: .85rem;
}

.admin-fields > div > label,
.admin-search label,
.admin-choices legend {
    display: block;
    margin-bottom: .35rem;
    font-size: .9rem;
    font-weight: 650;
}

.admin-choices {
    min-width: 0;
}

.admin-choice-list {
    max-height: 12rem;
    overflow-y: auto;
    padding: .5rem .7rem;
    border: 1px solid var(--admin-border);
    border-radius: .5rem;
    background: #fff;
}

.admin-choice {
    display: flex;
    align-items: baseline;
    gap: .55rem;
    padding-block: .25rem;
    overflow-wrap: anywhere;
}

.admin-choice input {
    flex-shrink: 0;
}

.admin-search {
    max-width: 24rem;
}

.admin-actions,
.admin-row-actions {
    justify-content: flex-end;
}

.admin-actions {
    margin-top: .8rem;
}

.admin-detail .table-responsive {
    border-radius: 0 0 1rem 1rem;
}

.admin-table {
    min-width: 31rem;
    --bs-table-border-color: var(--admin-border);
}

.admin-table > :not(caption) > * > * {
    padding: .7rem .85rem;
    overflow-wrap: anywhere;
}

.admin-table thead th {
    background: #f5f7fb;
    color: var(--admin-muted);
    font-size: .875rem;
}

.admin-table tbody th {
    font-weight: 650;
}

.admin-table td:last-child {
    min-width: 11rem;
}

.admin-table tbody tr:last-child > * {
    border-bottom: 0;
}
</style>
