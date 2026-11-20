<template>
    <main class="hoc">
        <div class="hoc-layout">
            <header class="hoc-header">
                <div>
                    <p class="hoc-role">Кабинет заведующего кафедрой</p>
                    <h1>Учебная нагрузка</h1>
                </div>
                <div class="hoc-account">
                    <strong v-if="accountName">{{ accountName }}</strong>
                    <button class="btn btn-outline-secondary" type="button" :disabled="signingOut" @click="logout">
                        {{ signingOut ? "Выход…" : "Выйти" }}
                    </button>
                </div>
            </header>

            <p v-if="!loading && !loadError" class="hoc-card hoc-department-balance">
                Остаток часов кафедры <strong>{{ formatNumber(remainingDepartmentHours) }} ч</strong>
            </p>
            <p v-if="actionError" class="alert alert-danger mb-0" role="alert">{{ actionError }}</p>

            <section v-if="loading" class="hoc-card hoc-empty" aria-live="polite">
                <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
                <p class="mb-0">Загружаем учебную нагрузку…</p>
            </section>
            <section v-else-if="loadError" class="hoc-card hoc-empty" role="alert">
                <h2>Не удалось загрузить данные</h2>
                <button class="btn btn-outline-primary" type="button" @click="load">Повторить</button>
            </section>
            <section v-else-if="!data.users.length" class="hoc-card hoc-empty">
                <h2>Нет преподавателей</h2>
                <p class="mb-0">За кафедрой пока не закреплён ни один преподаватель.</p>
            </section>

            <template v-else>
                <nav class="hoc-card hoc-path" aria-label="Путь в каталоге">
                    <ol>
                        <li v-for="(crumb, index) in breadcrumbs" :key="index">
                            <span v-if="index === breadcrumbs.length - 1" class="btn btn-sm hoc-current-folder" aria-current="location">{{ crumb }}</span>
                            <RouterLink v-else class="btn btn-sm btn-outline-primary" :to="catalogLocation(selection.slice(0, index))">{{ crumb }}</RouterLink>
                        </li>
                    </ol>
                </nav>

                <p v-if="semesterWarning" :class="['hoc-warning', semesterWarning.className]">
                    <strong>{{ semesterWarning.label }}</strong>
                    · {{ formatNumber(semester.total_hours_per_week) }} ч/нед. за полугодие
                </p>

                <section v-if="!activeSection" class="hoc-card hoc-folder-panel" aria-labelledby="hoc-folder-title">
                    <h2 id="hoc-folder-title">{{ folderTitles[selection.length] }}</h2>
                    <ul v-if="folders.length" class="hoc-folders">
                        <li v-for="folder in folders" :key="folder.id">
                            <RouterLink :to="catalogLocation([...selection, folder.id])" class="hoc-folder">
                                <span class="hoc-folder-icon" aria-hidden="true"></span>
                                <span class="hoc-folder-content">
                                    <strong>{{ folder.label }}</strong>
                                    <small v-if="folder.hours != null" :class="folder.warning?.className" :aria-label="folder.warning ? formatNumber(folder.hours) + ' ч/нед. · ' + folder.warning.label : undefined">
                                        {{ formatNumber(folder.hours) }} ч/нед.
                                    </small>
                                    <small v-if="folder.allocatedHours != null">{{ formatNumber(folder.allocatedHours) }} ч выделено</small>
                                </span>
                                <span class="hoc-folder-arrow" aria-hidden="true">›</span>
                            </RouterLink>
                        </li>
                    </ul>
                    <p v-else class="hoc-folder-empty">В этой папке пока нет разделов.</p>
                </section>

                <section v-else class="hoc-card" aria-labelledby="hoc-load-title" :aria-busy="saving">
                    <header class="hoc-section-header">
                        <div>
                            <h2 id="hoc-load-title" tabindex="-1">{{ activeSection.labels[4] }}</h2>
                        </div>
                        <button
                            class="btn btn-primary"
                            type="button"
                            :disabled="saving || !departmentLoads.length"
                            @click="openAddModal"
                        >
                            Добавить нагрузку
                        </button>
                    </header>
                    <dl class="hoc-summary">
                        <div><dt>Записей</dt><dd>{{ formatNumber(rows.length) }}</dd></div>
                        <div><dt>Выделено часов</dt><dd>{{ formatNumber(totalHours) }}</dd></div>
                        <div><dt>Часов в неделю</dt><dd>{{ formatNumber(weeklyHours) }}</dd></div>
                    </dl>
                    <div v-if="rows.length" class="table-responsive" tabindex="0" role="region" aria-label="Записи учебной нагрузки">
                        <table class="table hoc-table align-middle mb-0" aria-labelledby="hoc-load-title">
                            <thead>
                                <tr>
                                    <th scope="col">Содержание нагрузки</th>
                                    <th scope="col" class="text-end">Часы</th>
                                    <th scope="col" class="text-end">Часов в неделю</th>
                                    <th scope="col"><span class="visually-hidden">Действия</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in rows" :key="row.id.value" @contextmenu.prevent="openRowMenu(row, $event)">
                                    <th scope="row">{{ row.name.value }}</th>
                                    <td class="text-end">{{ formatNumber(row.hours_allocated) }}</td>
                                    <td class="text-end">{{ formatNumber(row.hours_per_week) }}</td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-sm btn-outline-danger me-2"
                                            type="button"
                                            :disabled="saving"
                                            :aria-label="'Удалить: ' + row.name.value"
                                            @click="openRecordDialog(row, $event.currentTarget, true)"
                                        >
                                            Удалить
                                        </button>
                                        <button
                                            class="btn btn-sm btn-outline-secondary"
                                            type="button"
                                            :disabled="saving"
                                            data-row-actions
                                            aria-haspopup="true"
                                            :aria-expanded="rowMenu?.row.id.value === row.id.value"
                                            :aria-label="'Действия: ' + row.name.value"
                                            @click.stop="openRowMenu(row, $event)"
                                        >
                                            <span aria-hidden="true">⋯</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="hoc-empty">
                        <h3>Нагрузка ещё не распределена</h3>
                        <p class="mb-0">{{ departmentLoads.length
                            ? "Добавьте запись о нагрузке для выбранного преподавателя."
                            : "У кафедры пока нет нагрузки для распределения." }}</p>
                    </div>
                </section>
            </template>
        </div>

        <div v-if="rowMenu" class="hoc-context" :style="{ left: rowMenu.x + 'px', top: rowMenu.y + 'px' }" @click.stop>
            <button ref="menuAction" type="button" class="hoc-context-action" @click="editRow">Редактировать</button>
        </div>
        <div ref="editDialog" class="modal fade" tabindex="-1" aria-labelledby="hoc-edit-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <form v-if="editDraft" class="modal-content hoc-record-form" @submit.prevent="saveEdit">
                    <div class="modal-header">
                        <div>
                            <h2 id="hoc-edit-title" class="modal-title fs-5">{{ dialogTitle }}</h2>
                            <p class="hoc-dialog-context">{{ editDraft.section.labels.join(" · ") }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть" :disabled="saving"></button>
                    </div>
                    <div class="modal-body">
                        <p v-if="editError" class="alert alert-danger" role="alert">{{ editError }}</p>
                        <div v-if="editDraft.deleting" class="alert alert-danger border-danger mb-0">
                            <strong>{{ editDraft.row.name.value }}</strong>
                            <p class="mb-0 mt-1">Запись будет удалена. Выделенные часы вернутся в остаток кафедры.</p>
                        </div>
                        <template v-else>
                            <label for="hoc-edit-load" class="form-label fw-semibold">Нагрузка кафедры</label>
                            <select id="hoc-edit-load" v-model="editDraft.department_load_id" class="form-select" required :disabled="saving || !visibleLoads.length">
                                <option value="" disabled>Выберите нагрузку</option>
                                <option v-for="load in visibleLoads" :key="load.id" :value="load.id" :disabled="!canAllocate(load)">
                                    {{ load.name }}{{ canAllocate(load) ? "" : " · распределено" }}
                                </option>
                            </select>
                            <div class="form-check mt-2 mb-3">
                                <input id="hoc-show-distributed" v-model="showDistributed" type="checkbox" class="form-check-input" :disabled="saving" />
                                <label for="hoc-show-distributed" class="form-check-label">Показать распределённые нагрузки</label>
                            </div>
                            <p v-if="!selectableLoads.length" class="alert alert-info py-2">У кафедры нет нагрузки с доступными часами.</p>
                            <label for="hoc-edit-hours" class="form-label fw-semibold">Выделенные часы</label>
                            <input id="hoc-edit-hours" v-model.number="editDraft.hours_allocated" class="form-control"
                                type="number" min="1" :max="editMaxHours" required :disabled="saving || !editDraft.department_load_id"
                                :aria-describedby="editMaxHours != null ? 'hoc-hour-limit' : undefined" />
                            <p v-if="editMaxHours != null" id="hoc-hour-limit" class="hoc-hour-limit">Доступно для записи <strong>{{ formatNumber(editMaxHours) }} ч</strong></p>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" :disabled="saving">Отмена</button>
                        <button type="submit" class="btn" :class="editDraft.deleting ? 'btn-danger' : 'btn-primary'"
                            :disabled="saving || (!editDraft.deleting && !editDraft.department_load_id)">
                            {{ saving ? "Подождите…" : editDraft.deleting ? "Подтвердить удаление" : editDraft.row ? "Сохранить" : "Добавить нагрузку" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</template>

<script setup>
import { computed, inject, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Modal } from "bootstrap";

const folderTitles = ["Преподаватели", "Учебные годы", "Полугодия", "Типы нагрузки", "Подтипы нагрузки"];
const numberFormat = new Intl.NumberFormat("ru-RU", { maximumFractionDigits: 1 });
const formatNumber = (value) => value == null ? "—" : numberFormat.format(value);

const fetchFunction = inject("fetchFunction");
const route = useRoute();
const router = useRouter();
const data = ref({ users: [], department_loads: [] });
const loading = ref(true);
const loadError = ref(false);
const actionError = ref("");
const saving = ref(false);
const signingOut = ref(false);
const editDialog = ref(null);
const editDraft = ref(null);
const showDistributed = ref(false);
const editError = ref("");
const currentUser = ref(null);
const rowMenu = ref(null);
const menuAction = ref(null);
let editTrigger;

const valueOf = (field) => String(field.value);
const teacherName = (teacher) =>
    [teacher.lastname.value, teacher.firstname.value, teacher.middlename.value]
        .filter(Boolean).join(" ") || teacher.login.value;
const accountName = computed(() => {
    const user = currentUser.value;
    return user && ([user.lastname, user.firstname, user.middlename].filter(Boolean).join(" ") || user.login);
});

const sections = computed(() => {
    const result = [];
    for (const teacher of data.value.users) {
        for (const year of teacher.study_years) {
            for (const half of year.half_years) {
                for (const type of half.types) {
                    for (const subtype of type.subtypes) {
                        result.push({
                            endpoint: String(result.length + 1),
                            ids: [teacher.id, year.id, half.value, type.id, subtype.id].map(valueOf),
                            labels: [teacherName(teacher), year.name.value, half.name.value, type.name.value, subtype.name.value],
                            subtype,
                        });
                    }
                }
            }
        }
    }
    return result;
});

const activeSection = computed(() => sections.value.find(
    (section) => section.endpoint === route.params.endpoint
));
const childKeys = ["study_years", "half_years", "types", "subtypes"];
const folderId = (item) => valueOf(item.id ?? item.value);
const catalog = computed(() => {
    const queryPath = typeof route.query.catalog === "string" ? route.query.catalog.split(".") : [];
    const ids = activeSection.value?.ids ?? queryPath.slice(0, childKeys.length);
    const path = [];
    let children = data.value.users;
    for (const [index, id] of ids.entries()) {
        const item = children.find((child) => folderId(child) === id);
        if (!item) break;
        path.push(item);
        children = item[childKeys[index]] ?? [];
    }
    return { path, children };
});
const selection = computed(() => catalog.value.path.map(folderId));
const folderName = (item) => item.name?.value ?? teacherName(item);
const breadcrumbs = computed(() => ["Каталог нагрузки", ...catalog.value.path.map(folderName)]);
const semester = computed(() => catalog.value.path[2]);
const semesterWarning = computed(() => weeklyWarning(semester.value?.total_hours_per_week));
const folders = computed(() => {
    const isSubtypeLevel = selection.value.length === folderTitles.length - 1;
    const entries = catalog.value.children.map((item) => ({
        id: folderId(item),
        label: folderName(item),
        hours: item.total_hours_per_week,
        allocatedHours: isSubtypeLevel ? item.total_hours : null,
        warning: selection.value.length === 2 ? weeklyWarning(item.total_hours_per_week) : null,
    }));
    return isSubtypeLevel ? entries.sort((a, b) => b.allocatedHours - a.allocatedHours) : entries;
});

function weeklyWarning(hours) {
    if (hours >= 36) return { label: "Слишком высокая нагрузка", className: "hoc-overload" };
    if (hours >= 28) return { label: "Нагрузка близка к пределу", className: "hoc-near-limit" };
    return null;
}
const rows = computed(() => [...(activeSection.value?.subtype.items.value ?? [])]
    .sort((a, b) => b.hours_allocated - a.hours_allocated));
const totalHours = computed(() => rows.value.reduce((sum, row) => sum + Number(row.hours_allocated), 0));
const weeklyHours = computed(() => rows.value.reduce((sum, row) => sum + Number(row.hours_per_week), 0));
const departmentLoads = computed(() => data.value.department_loads.map((item) => ({
    ...item,
    name: `[${item.group.name}] ${item.subject.name}`,
})));
const remainingDepartmentHours = computed(() => data.value.department_loads.reduce(
    (sum, item) => sum + Number(item.computed_attributes?.difference_hours ?? 0), 0
));
const editMaxHours = computed(() => {
    const draft = editDraft.value;
    if (!draft) return undefined;
    if (draft.row && draft.department_load_id === draft.row.department_load.id) return draft.row.max_hours;
    return departmentLoads.value.find((load) => load.id === draft.department_load_id)
        ?.computed_attributes?.difference_hours;
});
const dialogTitle = computed(() => {
    if (editDraft.value?.deleting) return "Удалить нагрузку?";
    return editDraft.value?.row ? "Редактировать нагрузку" : "Добавить нагрузку";
});
const canAllocate = (load) => load.id === editDraft.value?.row?.department_load.id ||
    load.computed_attributes?.difference_hours > 0;
const selectableLoads = computed(() => departmentLoads.value.filter(canAllocate));
const visibleLoads = computed(() => showDistributed.value ? departmentLoads.value : selectableLoads.value);

function catalogLocation(ids) {
    if (ids.length === folderTitles.length) {
        const section = sections.value.find((item) => item.ids.every((id, index) => id === ids[index]));
        if (section) return "/hoc/" + section.endpoint;
    }
    return { path: "/hoc", query: ids.length ? { catalog: ids.join(".") } : {} };
}

async function request(url, options) {
    const response = await fetchFunction(url, options);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
}

async function refreshData() {
    data.value = await request("/api/hoc/" + currentUser.value.id);
}

async function load() {
    loading.value = true;
    loadError.value = false;
    try {
        const user = await request("/user");
        if (!user?.id) throw new Error("User unavailable");
        currentUser.value = user;
        await refreshData();
        if (route.params.endpoint && !activeSection.value) await router.replace("/hoc");
    } catch {
        loadError.value = true;
    } finally {
        loading.value = false;
    }
}

function openAddModal(event) {
    openRecordDialog(null, event.currentTarget);
}

function restoreEditFocus() {
    editDraft.value = null;
    if (editTrigger?.isConnected && !editTrigger.disabled) editTrigger.focus();
    else document.getElementById("hoc-load-title")?.focus();
    editTrigger = null;
}

function closeRowMenu(restoreFocus = false) {
    const trigger = rowMenu.value?.trigger;
    rowMenu.value = null;
    if (restoreFocus) trigger?.focus({ preventScroll: true });
}

async function openRowMenu(row, event) {
    if (saving.value) return;
    const trigger = event.currentTarget.closest("tr").querySelector("[data-row-actions]");
    const bounds = trigger.getBoundingClientRect();
    const atPointer = event.type === "contextmenu" && (event.clientX || event.clientY);
    rowMenu.value = {
        row,
        trigger,
        x: atPointer ? event.clientX : bounds.left,
        y: atPointer ? event.clientY : bounds.bottom + 4,
    };
    await nextTick();
    if (!menuAction.value || rowMenu.value?.trigger !== trigger) return;
    const menu = menuAction.value.parentElement.getBoundingClientRect();
    rowMenu.value.x = Math.max(8, Math.min(rowMenu.value.x, window.innerWidth - menu.width - 8));
    rowMenu.value.y = Math.max(8, Math.min(rowMenu.value.y, window.innerHeight - menu.height - 8));
    menuAction.value.focus({ preventScroll: true });
}

function dismissRowMenu() {
    closeRowMenu();
}

function handleEscape(event) {
    if (event.key === "Escape" && rowMenu.value) closeRowMenu(true);
}

function editRow() {
    if (!rowMenu.value) return;
    const { row, trigger } = rowMenu.value;
    openRecordDialog(row, trigger);
}

async function openRecordDialog(row, trigger, deleting = false) {
    if (!activeSection.value || saving.value) return;
    closeRowMenu();
    actionError.value = "";
    editError.value = "";
    showDistributed.value = false;
    editTrigger = trigger;
    editDraft.value = {
        row,
        deleting,
        section: activeSection.value,
        department_load_id: row?.department_load.id ?? "",
        hours_allocated: row?.hours_allocated ?? "",
    };
    await nextTick();
    Modal.getOrCreateInstance(editDialog.value).show();
}

async function syncAfterChange() {
    try {
        await refreshData();
    } catch {
        actionError.value = "Запись изменена, но не удалось обновить данные кафедры.";
    }
}

async function saveEdit() {
    if (!editDraft.value || saving.value) return;
    const { row, section, department_load_id, hours_allocated, deleting } = editDraft.value;
    const [teacher_id, study_year_id, half_year, type_id, subtype_id] = section.ids.map(Number);
    const payload = row
        ? { department_load_id, hours_allocated }
        : { department_load_id, hours_allocated, teacher_id, study_year_id, half_year, type_id, subtype_id };
    saving.value = true;
    editError.value = "";
    try {
        const result = await request(row ? "/api/hoc/" + row.id.value : "/api/hoc", deleting
            ? { method: "DELETE" }
            : {
                method: row ? "PUT" : "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
        const records = section.subtype.items.value;
        if (!row) records.push(result);
        else {
            const index = records.findIndex((item) => item.id.value === row.id.value);
            if (index !== -1) records.splice(index, 1, ...(deleting ? [] : [result]));
        }
        await syncAfterChange();
        saving.value = false;
        if (editDialog.value) Modal.getInstance(editDialog.value)?.hide();
    } catch {
        editError.value = deleting
            ? "Не удалось удалить запись. Повторите попытку."
            : row
                ? "Не удалось сохранить изменения. Проверьте введённые данные."
                : "Не удалось добавить нагрузку. Проверьте введённые данные.";
    } finally {
        saving.value = false;
    }
}

async function logout() {
    actionError.value = "";
    signingOut.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
        await request("/logout", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
        });
        await router.push("/login");
    } catch {
        actionError.value = "Не удалось выйти из кабинета. Повторите попытку.";
    } finally {
        signingOut.value = false;
    }
}

watch(() => route.fullPath, dismissRowMenu);

function keepEditOpenWhileSaving(event) {
    if (saving.value) event.preventDefault();
}

onMounted(() => {
    editDialog.value.addEventListener("hidden.bs.modal", restoreEditFocus);
    editDialog.value.addEventListener("hide.bs.modal", keepEditOpenWhileSaving);
    window.addEventListener("click", dismissRowMenu);
    window.addEventListener("keydown", handleEscape);
    window.addEventListener("resize", dismissRowMenu);
    window.addEventListener("scroll", dismissRowMenu, true);
    load();
});

onBeforeUnmount(() => {
    editDialog.value.removeEventListener("hidden.bs.modal", restoreEditFocus);
    editDialog.value.removeEventListener("hide.bs.modal", keepEditOpenWhileSaving);
    Modal.getInstance(editDialog.value)?.dispose();
    window.removeEventListener("click", dismissRowMenu);
    window.removeEventListener("keydown", handleEscape);
    window.removeEventListener("resize", dismissRowMenu);
    window.removeEventListener("scroll", dismissRowMenu, true);
});
</script>

<style scoped>
.hoc {
    --hoc-text: #17243a;
    --hoc-muted: #52627a;
    --hoc-border: #dbe3ee;
    --hoc-accent: #234fbd;
    min-height: 100vh;
    padding: clamp(1rem, 4vw, 3.5rem);
    background: #f3f6fb;
    color: var(--hoc-text);
}

.hoc-layout {
    display: grid;
    gap: 1.25rem;
    max-width: 80rem;
    margin-inline: auto;
}

.hoc-header,
.hoc-section-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.hoc-header h1 {
    margin: 0;
    font-size: clamp(1.75rem, 4vw, 2.75rem);
    font-weight: 750;
    letter-spacing: -0.035em;
}

.hoc-role {
    margin-bottom: .4rem;
    color: var(--hoc-accent);
    font-weight: 650;
}

.hoc-account {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem;
}

.hoc-record-form {
    border-color: var(--hoc-border);
    border-radius: 1rem;
}

.hoc-record-form .modal-header {
    align-items: flex-start;
    background: #f5f7fb;
}

.hoc-dialog-context {
    margin: .35rem 0 0;
    color: var(--hoc-muted);
    font-size: .875rem;
    overflow-wrap: anywhere;
}

.hoc-hour-limit {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: .25rem .75rem;
    margin: .75rem 0 0;
    padding: .5rem .75rem;
    border-radius: .5rem;
    background: #eef3fc;
    color: var(--hoc-accent);
    font-size: .875rem;
}

.hoc-department-balance {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .35rem .75rem;
    margin: 0;
    padding: .75rem 1.25rem;
    color: var(--hoc-muted);
}

.hoc-department-balance strong {
    color: var(--hoc-accent);
    font-size: 1.25rem;
    font-variant-numeric: tabular-nums;
}

.hoc-card {
    min-width: 0;
    border: 1px solid var(--hoc-border);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 4px 16px rgb(27 53 94 / 5%);
}

.hoc-card h2,
.hoc-card h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.hoc-folder-panel,
.hoc-section-header {
    padding: clamp(1rem, 2.5vw, 1.5rem);
}

.hoc-path {
    padding: .85rem 1.25rem;
}

.hoc-path ol {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .4rem .65rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.hoc-path li {
    display: flex;
    align-items: center;
    gap: .65rem;
    min-width: 0;
    overflow-wrap: anywhere;
}

.hoc-path li + li::before {
    content: "›";
    color: var(--hoc-muted);
}

.hoc-path .btn {
    min-width: 0;
    white-space: normal;
}

.hoc-current-folder {
    border-color: #c4d5f3;
    background: #eef3fc;
    color: var(--hoc-accent);
    font-weight: 700;
    cursor: default;
}

.hoc-folders {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 18rem), 1fr));
    gap: .75rem;
    margin: 1rem 0 0;
    padding: 0;
    list-style: none;
}

.hoc-folder {
    display: flex;
    align-items: center;
    gap: .8rem;
    min-height: 4.25rem;
    height: 100%;
    padding: .8rem 1rem;
    border: 1px solid var(--hoc-border);
    border-radius: .75rem;
    color: var(--hoc-text);
    text-decoration: none;
}

.hoc-folder:hover,
.hoc-folder:focus-visible {
    border-color: #9ab2e1;
    background: #eef3fc;
}

.hoc-folder-icon {
    position: relative;
    flex: 0 0 1.65rem;
    height: 1.1rem;
    border-radius: .2rem;
    background: #efbb54;
}

.hoc-folder-icon::before {
    position: absolute;
    top: -.22rem;
    left: 0;
    width: .7rem;
    height: .35rem;
    border-radius: .15rem .15rem 0 0;
    background: #efbb54;
    content: "";
}

.hoc-folder-content {
    display: grid;
    flex: 1;
    gap: .15rem;
    min-width: 0;
    overflow-wrap: anywhere;
}

.hoc-folder-content small,
.hoc-folder-arrow,
.hoc-folder-empty {
    color: var(--hoc-muted);
}

.hoc-folder-empty {
    margin: 1rem 0 0;
}

.hoc-warning {
    margin: 0;
    padding: .75rem 1rem;
    border: 1px solid currentColor;
    border-radius: .75rem;
}

.hoc-warning.hoc-overload {
    background: #fff1f0;
}

.hoc-warning.hoc-near-limit {
    background: #fff7df;
}

.hoc-overload,
.hoc-folder-content .hoc-overload {
    color: #9d2121;
}

.hoc-near-limit,
.hoc-folder-content .hoc-near-limit {
    color: #815100;
}

.hoc-context {
    position: fixed;
    z-index: 1040;
    min-width: 10rem;
    padding: .3rem;
    border: 1px solid var(--hoc-border);
    border-radius: .65rem;
    background: #fff;
    box-shadow: 0 8px 24px rgb(27 53 94 / 20%);
}

.hoc-context-action {
    width: 100%;
    padding: .5rem .75rem;
    border: 0;
    border-radius: .35rem;
    background: #fff;
    color: var(--hoc-text);
    text-align: left;
}

.hoc-context-action:hover,
.hoc-context-action:focus-visible {
    background: #eef3fc;
}

.hoc-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin: 0 clamp(1rem, 2.5vw, 1.5rem) 1.25rem;
    border-radius: .75rem;
    background: #eef3fc;
}

.hoc-summary div {
    padding: .75rem clamp(.5rem, 2vw, 1.25rem);
}

.hoc-summary dt {
    color: var(--hoc-muted);
    font-size: .875rem;
}

.hoc-summary dd {
    margin: 0;
    color: var(--hoc-accent);
    font-size: clamp(1.1rem, 3vw, 1.65rem);
    font-weight: 750;
    font-variant-numeric: tabular-nums;
}

.hoc-card .table-responsive {
    border-radius: 0 0 1rem 1rem;
}

.hoc-table {
    min-width: 34rem;
    --bs-table-color: var(--hoc-text);
    --bs-table-border-color: var(--hoc-border);
}

.hoc-table > :not(caption) > * > * {
    padding: .85rem 1rem;
}

.hoc-table thead th {
    background: #f5f7fb;
    color: var(--hoc-muted);
    font-size: .875rem;
}

.hoc-table tbody th {
    width: 100%;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.hoc-table td {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.hoc-table tbody tr:last-child > * {
    border-bottom: 0;
}

.hoc-empty {
    display: grid;
    justify-items: center;
    gap: .65rem;
    padding: clamp(1.5rem, 5vw, 3rem) 1rem;
    text-align: center;
}

.hoc-empty p {
    color: var(--hoc-muted);
}

</style>
