<template>
    <div class="d-flex w-100">
        <Sidebar
            ref="sidebar"
            @clearSelectedRow="clearSelectedRow"
            @menuItemClick="tableDisplay"
            :items="sideBarItems"
        />
        <div class="flex-grow-1" :style="{ marginLeft: sidebarWidth + 'px' }">
            <Table
                v-if="isTableVisible"
                ref="table"
                :columns="columnMappings"
                :rows="tableData"
                :modalItems="computedData"
                @rowSelected="editRow"
                @saves="saveRow"
            />
            <edit-sidebar
                :items="selectedRow"
                @close="clearSelectedRow"
                @save="updateRow"
                @delete="deleteRow"
            />
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, watch, inject, h } from "vue";
import { useRoute, useRouter } from "vue-router";
import EditSidebar from "../components/EditSidebar.vue";
import Table from "../components/Table.vue";
import { Offcanvas } from "bootstrap";
import Sidebar from "../components/SideBar.vue";

export default {
    components: {
        Sidebar,
        Table,
        EditSidebar,
    },
    setup() {
        const tableData = ref([]);
        const selectedRow = ref([]);
        const fetchFunction = inject("fetchFunction");
        const currentUser = ref(null);
        const separator = h("hr", {
            style: {
                borderColor: "rgba(0, 0, 0, 0.1)",
                margin: "20px",
            },
        });
        const checkAuth = () => {
            fetchFunction("/user")
                .then((response) => response.json())
                .then((data) => {
                    console.log(data);
                    currentUser.value = data;
                    console.log(currentUser.value.id);
                    fetchData();
                });
        };
        const getCsrfToken = () => {
            const metaTag = document.head.querySelector(
                'meta[name="csrf-token"]'
            );
            return metaTag ? metaTag.content : "";
        };

        const route = useRoute();
        const router = useRouter();
        const dataTable = ref([]);

        const isTableVisible = ref(false);
        const editItem = ref([]);
        const sidebar = ref(null);
        const table = ref(null);
        let editSidebar;
        const isDataFetched = ref(false);
        const openEditSideBar = () => {
            let modalElement = document.getElementById("openEditSideBar");
            if (!editSidebar) {
                editSidebar = new Offcanvas(modalElement, {
                    backdrop: true,
                });

                modalElement.addEventListener(
                    "hidden.bs.offcanvas",
                    function () {
                        clearSelectedRow();
                    }
                );
            }
            editSidebar.show();
        };
        const closeEditSidebar = () => {
            if (editSidebar) {
                editSidebar.hide();
            }
        };
        const tableDisplay = (item) => {
            editItem.value = item;
            let data = filteredData(item.items, "table");
            data.forEach((row) => {
                if (row.roles) {
                    row.roles = row.roles.map((role) => role.name).join(", ");
                }
                if (row.teacher_statuses)
                    row.teacher_statuses = row.teacher_statuses
                        .map((teacherStatus) => teacherStatus.name)
                        .join(", ");
                if (row.departments)
                    row.departments = row.departments
                        .map((department) => department.name)
                        .join(", ");
                if (row.user) {
                    row.user.lastname == null &&
                    row.user.firstname == null &&
                    row.user.middlename == null
                        ? (row.user = row.user.login)
                        : (row.user = [
                              row.user.lastname,
                              row.user.firstname,
                              row.user.middlename,
                          ].join(" "));
                }
                if (row.department) row.department = row.department.name;
                if (row.specialty) row.specialty = row.specialty.name;
            });
            tableData.value = data;
            isTableVisible.value = true;
        };
        const columnMappings = ["Группа", "Предмет", "Семестр", "Часы"];
        const sideBarItems = ref([]);
        const filteredData = (data) => {
            const dataCopy = [...data.value];
            // Используем метод map для обработки каждого объекта в массиве
            const filteredData = dataCopy.map((row) => {
                const {
                    id,
                    group_id,
                    subject_id,
                    created_at,
                    updated_at,
                    department_id,
                    department,
                    ...dataToPrint
                } = row;
                Object.keys(dataToPrint).forEach((key) => {
                    if (
                        typeof dataToPrint[key] === "object" &&
                        dataToPrint[key] !== null &&
                        "name" in dataToPrint[key]
                    ) {
                        dataToPrint[key] = dataToPrint[key].name;
                    }
                });
                const orderedRow = {
                    group: row.group ? row.group.name : null,
                    subject: row.subject ? row.subject.name : null,
                    semester: row.semester,
                    hours_allocated: row.hours_allocated,
                };
                return orderedRow;
            });
            return filteredData;
        };
        const fetchData = () => {
            fetchFunction("/api/assistantDirector/" + currentUser.value.id)
                .then((response) => response.json())
                .then((data) => {
                    dataTable.value = data;
                    isDataFetched.value = true;
                    let count = 1;
                    sideBarItems.value = [
                        {
                            header: "Редактирование данных",
                            hiddenOnCollapse: true,
                        },
                        ...dataTable.value.departments.map((department) => ({
                            title: `[${department.titleRight}] ${department.titleLeft}`,
                            items: department.department_loads,
                            href: "/assistantDirector/" + count++,
                            department_id: department.id.value,
                        })),
                        {
                            component: separator,
                        },
                        {
                            title: "Выйти",
                            logout: true,
                        },
                    ];
                })
                .catch((error) => {
                    console.error("Ошибка при получении данных:", error);
                });
        };

        const editRow = (rowIndex) => {
            let originalRow = editItem.value.items.value[rowIndex];
            console.log(originalRow);
            let data;
            data = [
                {
                    name: "department_id",
                    title: "Кафедра",
                    value: dataTable.value.departments.map((department) => ({
                        ...department,
                        name: department.name.value,
                        id: department.id.value,
                        select:
                            originalRow.department_id === department.id.value,
                    })),
                    label: "select",
                },
                {
                    name: "group_id",
                    title: "Учебная группа",
                    value: dataTable.value.groups.map((group) => ({
                        ...group,
                        name: group.name.value,
                        id: group.id.value,
                        select: originalRow.group.id === group.id.value,
                    })),
                    label: "select",
                },
                {
                    name: "subject_id",
                    title: "Учебный предмет",
                    value: dataTable.value.subjects.map((subject) => ({
                        ...subject,
                        name: subject.name.value,
                        id: subject.id.value,
                        select: originalRow.subject.id === subject.id.value,
                    })),
                    label: "select",
                },

                {
                    name: "semester",
                    title: "Семестр",
                    value: originalRow.semester,
                    label: "number",
                    min: 1,
                    max: 10,
                },
                {
                    name: "hours_allocated",
                    title: "Выделенные часы",
                    value: originalRow.hours_allocated,
                    label: "number",
                    min: 1,
                    max: 10000,
                },
            ];
            if (originalRow) {
                selectedRow.value = [
                    {
                        name: "id",
                        value: originalRow.id,
                        href: "assistantDirector",
                    },
                    ...data,
                ];
                openEditSideBar();
            }
        };

        const updateRow = (updatedRow) => {
            const index = editItem.value.items.value.findIndex(
                (row) => row.id === updatedRow.id
            );

            if (
                editItem.value.items.value[index].department_id !==
                updatedRow.department_id
            ) {
                console.log(index);
                if (index !== -1) editItem.value.items.value.splice(index, 1);
            } else {
                editItem.value.items.value.splice(index, 1, updatedRow);
            }
            fetchData();
            clearSelectedRow();
            tableDisplay(editItem.value);
            closeEditSidebar();
        };
        const deleteRow = (idRow) => {
            const index = editItem.value.items.value.findIndex(
                (row) => row.id === idRow
            );
            console.log(index);
            if (index !== -1) {
                editItem.value.items.value.splice(index, 1);
            }
            fetchData();
            clearSelectedRow();
            tableDisplay(editItem.value);
            closeEditSidebar();
        };
        const saveRow = (newRow) => {
            newRow["department_id"] = editItem.value.department_id;
            newRow["user_id"] = currentUser.value.id;
            console.log(newRow);
            fetchFunction(`/api/assistantDirector`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(newRow),
            })
                .then((response) => response.json())
                .then((data) => {
                    console.log("Данные с сервера: ");
                    console.log(data);
                    editItem.value.items.value.push(data);
                    fetchData();
                    clearSelectedRow();
                    tableDisplay(editItem.value);
                })
                .catch((error) => {
                    console.error("Ошибка при обновлении данных:", error);
                });
        };

        const clearSelectedRow = () => {
            selectedRow.value = [];
            if (table.value) {
                table.value.clearSelection();
            }
        };
        const checkAndRedirect = () => {
            // Проверяем если isTableVisible равно false
            if (!isTableVisible.value) {
                // Получаем текущий путь
                const currentPath = route.path;

                // Проверяем путь на соответствие шаблону
                const regex = /^\/assistantDirector\/\d+$/;
                if (regex.test(currentPath)) {
                    // Выполняем редирект на /assistantDirector
                    router.push("/assistantDirector");
                }
            }
        };
        onMounted(() => {
            checkAuth();
            checkAndRedirect();
        });
        const computedData = computed(() => {
            return isDataFetched.value
                ? [
                      {
                          name: "group_id",
                          title: "Учебная группа",
                          value: dataTable.value.groups.map((group) => ({
                              ...group,
                              name: group.name.value,
                              id: group.id.value,
                          })),
                          label: "select",
                      },
                      {
                          name: "subject_id",
                          title: "Учебный предмет",
                          value: dataTable.value.subjects.map((subject) => ({
                              ...subject,
                              name: subject.name.value,
                              id: subject.id.value,
                          })),
                          label: "select",
                      },
                      {
                          name: "semester",
                          title: "Семестр",
                          value: "",
                          label: "number",
                          min: 1,
                          max: 10,
                      },
                      {
                          name: "hours_allocated",
                          title: "Выделенные часы",
                          value: "",
                          label: "number",
                          min: 1,
                          max: 10000,
                      },
                  ]
                : [];
        });
        const sidebarWidth = computed(() => {
            if (sidebar.value) {
                return sidebar.value.$el.clientWidth; // Получаем ширину элемента sidebar
            }
            return 0;
        });
        return {
            dataTable,
            selectedRow,
            fetchData,
            editRow,
            updateRow,
            clearSelectedRow,
            saveRow,
            deleteRow,
            fetchFunction,
            sidebar,
            table,
            sidebarWidth,
            computedData,
            sideBarItems,
            openEditSideBar,
            editSidebar,
            columnMappings,
            tableDisplay,
            tableData,
            isTableVisible,
            currentUser,
            checkAuth,
            getCsrfToken,
        };
    },
};
</script>
