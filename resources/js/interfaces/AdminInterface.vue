<template>
    <div class="d-flex w-100">
        <sidebar
            ref="sidebar"
            @clearSelectedRow="clearSelectedRow"
            :items="sideBarItems"
        />
        <div class="flex-grow-1" :style="{ marginLeft: sidebarWidth + 'px' }">
            <Table
                v-if="checkPath()"
                ref="table"
                :columns="getColumnNames()"
                :rows="filterData"
                :modalItems="computedData"
                @rowSelected="editRow"
                @saves="saveRow"
            />
            <EditSidebar
                ref="editSidebar"
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
import { useRoute } from "vue-router";
import EditSidebar from "../components/EditSidebar.vue";
import Table from "../components/Table.vue";
import { Offcanvas } from "bootstrap";
import Sidebar from "../components/SideBar.vue";

export default {
    components: {
        EditSidebar,
        Table,
        Sidebar,
    },
    setup() {
        const selectedRow = ref(null);
        const fetchFunction = inject("fetchFunction");
        const separator = h("hr", {
            style: {
                borderColor: "rgba(0, 0, 0, 0.1)",
                margin: "20px",
            },
        });
        // watch(selectedRow, (newValue) => {
        //     if (newValue) {
        //         openEditSideBar();
        //     } else {
        //         closeEditSidebar();
        //     }
        // });
        const route = useRoute();
        const dataTable = ref([]);

        const sidebar = ref(null);
        const table = ref(null);
        let editSidebar;
        const isDataFetched = ref(false);

        const columnMappings = {
            users: [
                "Логин",
                "Электронная почта",
                "Имя",
                "Фамилия",
                "Отчество",
                "Роли",
                "Статусы",
                "Кафедры",
            ],
            groups: ["Название", "Количество студентов", "Специальность"],
            departments: ["Название", "ФИО заведующего"],
            roles: ["Название роли"],
            specialties: ["Название специальности", "Кафедра"],
            subtypes: ["Название вида деятельности"],
            types: ["Название формы обучения"],
            teacher_statuses: ["Название статуса"],
            subjects: ["Наименование предмета"],
            study_years: ["Учебный год"],
        };
        const sideBarItems = [
            {
                header: "Редактирование данных",
                hiddenOnCollapse: true,
            },
            {
                title: "Предметы",
                href: "/admin/subjects",
            },
            {
                title: "Роли",
                href: "/admin/roles",
            },
            {
                title: "Формы обучения",
                href: "/admin/types",
            },
            {
                title: "Статусы преподавателя",
                href: "/admin/teacher_statuses",
            },
            {
                title: "Виды деятельности",
                href: "/admin/subtypes",
            },
            {
                title: "Учебные года",
                href: "/admin/study_years",
            },
            {
                title: "Пользователи",
                href: "/admin/users",
            },
            {
                title: "Кафедры",
                href: "/admin/departments",
            },
            {
                title: "Специальности",
                href: "/admin/specialties",
            },
            {
                title: "Группы",
                href: "/admin/groups",
            },

            {
                title: "Выйти",
                logout: true,
            },
            {
                component: separator,
            },
        ];

        const filterData = computed(() => {
            if (!checkPath()) return [];
            if (!isDataFetched.value) return [];
            console.log(dataTable.value);
            let data = filteredData(
                dataTable.value[route.params.endpoint],
                "table"
            );
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
            return data;
        });
        const filteredData = (data, pred) => {
            return data.map((row) => {
                let newRow = { ...row };
                for (let prop in newRow) {
                    if (
                        newRow[prop] &&
                        newRow[prop].label &&
                        newRow[prop].label.includes(pred)
                    ) {
                        newRow[prop] = newRow[prop].value;
                    } else {
                        delete newRow[prop];
                    }
                }
                return newRow;
            });
        };
        const fetchData = () => {
            fetchFunction("/api/admin")
                .then((response) => response.json())
                .then((data) => {
                    dataTable.value = data;
                    isDataFetched.value = true;
                    console.log(dataTable.value);
                })
                .catch((error) => {
                    console.error("Ошибка при получении данных:", error);
                });
        };

        const getColumnNames = () => {
            return columnMappings[route.params.endpoint] || [];
        };
        const checkPath = () => {
            return route.params.endpoint !== undefined;
        };
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
        const editRow = (rowIndex) => {
            let originalRow = dataTable.value[route.params.endpoint][rowIndex];
            let data;
            console.log(originalRow);
            switch (route.params.endpoint) {
                case "users":
                    data = [
                        {
                            name: "login",
                            title: "Логин",
                            value: originalRow.login.value,
                        },
                        {
                            name: "email",
                            title: "Электронная почта",
                            value: originalRow.email.value,
                        },
                        {
                            name: "firstname",
                            title: "Имя",
                            value: originalRow.firstname.value,
                        },
                        {
                            name: "lastname",
                            title: "Фамилия",
                            value: originalRow.lastname.value,
                        },
                        {
                            name: "middlename",
                            title: "Отчество",
                            value: originalRow.middlename.value,
                        },
                        {
                            name: "roles",
                            title: "Роли",
                            value: dataTable.value.roles.map((role) => ({
                                ...role,
                                name: role.name.value,
                                id: role.id.value,
                                select: originalRow.roles.value.some(
                                    (dataRole) => dataRole.id === role.id.value
                                ),
                            })),
                            label: "DLG",
                        },
                        {
                            name: "teacher_statuses",
                            title: "Статусы преподавателя",
                            value: dataTable.value.teacher_statuses.map(
                                (teacherStatus) => ({
                                    ...teacherStatus,
                                    name: teacherStatus.name.value,
                                    id: teacherStatus.id.value,
                                    select: originalRow.teacher_statuses.value.some(
                                        (dataTeacherStatus) =>
                                            dataTeacherStatus.id ===
                                            teacherStatus.id.value
                                    ),
                                })
                            ),

                            label: "DLG",
                        },
                        {
                            name: "departments",
                            title: "Кафедры",
                            value: dataTable.value.departments.map(
                                (department) => ({
                                    ...department,
                                    name: department.name.value,
                                    id: department.id.value,
                                    selectedValue: null,
                                    select: originalRow.departments.value.some(
                                        (dataDepartment) =>
                                            dataDepartment.id ===
                                            department.id.value
                                    ),
                                })
                            ),
                            label: "DLG",
                        },
                    ];
                    break;
                case "teacher_statuses":
                    data = [
                        {
                            name: "name",
                            title: "Название статуса",
                            value: originalRow.name.value,
                        },
                    ];
                    break;
                case "types":
                    data = [
                        {
                            name: "name",
                            title: "Название типа",
                            value: originalRow.name.value,
                        },
                    ];
                    break;
                case "subjects":
                    data = [
                        {
                            name: "name",
                            title: "Название предмета",
                            value: originalRow.name.value,
                        },
                    ];
                    break;
                case "subtypes":
                    data = [
                        {
                            name: "name",
                            title: "Название вида деятельности",
                            value: originalRow.name.value,
                        },
                    ];
                    break;
                case "study_years":
                    data = [
                        {
                            name: "start_year",
                            label: "number",
                            title: "Год начала учебного года",
                            value: originalRow.start_year.value,
                        },
                        {
                            name: "end_year",
                            label: "number",
                            title: "Год конца учебного года",
                            value: originalRow.end_year.value,
                        },
                    ];
                    break;
                case "roles":
                    data = [
                        {
                            name: "name",
                            title: "Название роли",
                            value: originalRow.name.value,
                        },
                    ];
                    break;
                case "departments":
                    data = [
                        {
                            name: "name",
                            title: "Наименование кафедры",
                            value: originalRow.name.value,
                        },
                        {
                            name: "user_id",
                            title: "Заведующий кафедрой",
                            value: dataTable.value.users
                                .filter((user) => user.roles.value[0].id == 3)
                                .map((user) => ({
                                    ...user,
                                    name:
                                        user.lastname.value == null &&
                                        user.firstname.value == null &&
                                        user.middlename.value == null
                                            ? user.login.value
                                            : (user.lastname.value != null
                                                  ? user.lastname.value + " "
                                                  : "") +
                                              (user.firstname.value != null
                                                  ? user.firstname.value + " "
                                                  : "") +
                                              (user.middlename.value != null
                                                  ? user.middlename.value
                                                  : ""),
                                    id: user.id.value,
                                    select:
                                        originalRow.user.value &&
                                        originalRow.user.value.id ===
                                            user.id.value,
                                })),
                            label: "select",
                        },
                    ];
                    break;
                case "specialties":
                    data = [
                        {
                            name: "name",
                            title: "Наименование специальности",
                            value: originalRow.name.value,
                        },
                        {
                            name: "department_id",
                            title: "Кафедра",
                            value: dataTable.value.departments.map(
                                (department) => ({
                                    ...department,
                                    name: department.name.value,
                                    id: department.id.value,
                                    select:
                                        originalRow.department.value.id ===
                                        department.id.value,
                                })
                            ),
                            label: "select",
                        },
                    ];
                    break;
                case "groups":
                    data = [
                        {
                            name: "name",
                            title: "Наименование группы",
                            value: originalRow.name.value,
                        },
                        {
                            name: "student_count",
                            label: "number",
                            title: "Количество студентов",
                            value: originalRow.student_count.value,
                            min: 1,
                            max: 1000,
                        },
                        {
                            name: "specialty_id",
                            title: "Специальность",
                            value: dataTable.value.specialties.map(
                                (specialty) => ({
                                    ...specialty,
                                    name: specialty.name.value,
                                    id: specialty.id.value,
                                    select:
                                        originalRow.specialty.value.id ===
                                        specialty.id.value,
                                })
                            ),
                            label: "select",
                        },
                    ];
                    break;
                default:
                    break;
            }
            if (originalRow) {
                selectedRow.value = [
                    { name: "id", value: originalRow.id.value },
                    ...data,
                ];
                openEditSideBar();
            }
        };

        const updateRow = (updatedRow) => {
            const index = dataTable.value[route.params.endpoint].findIndex(
                (row) => row.id.value === updatedRow.id.value
            );
            if (index !== -1) {
                dataTable.value[route.params.endpoint].splice(
                    index,
                    1,
                    updatedRow
                );
            }
            clearSelectedRow();
        };
        const deleteRow = (idRow) => {
            const index = dataTable.value[route.params.endpoint].findIndex(
                (row) => row.id.value === idRow
            );
            if (index !== -1) {
                dataTable.value[route.params.endpoint].splice(index, 1);
            }
            clearSelectedRow();
        };
        const saveRow = (newRow) => {
            fetchFunction(`/api/${route.params.endpoint}`, {
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
                    dataTable.value[route.params.endpoint].push(newRow);
                    fetchData();
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
            closeEditSidebar();
        };
        onMounted(() => {
            fetchData();
        });
        const computedData = computed(() => {
            console.log(dataTable.value.users);
            return isDataFetched.value
                ? {
                      users: [
                          { name: "login", title: "Логин", value: "" },
                          { name: "password", title: "Пароль", value: "" },
                          {
                              name: "email",
                              title: "Электронная почта",
                              value: "",
                          },
                          { name: "firstname", title: "Имя", value: "" },
                          { name: "lastname", title: "Фамилия", value: "" },
                          { name: "middlename", title: "Отчество", value: "" },
                          {
                              name: "roles",
                              title: "Роли",
                              value: dataTable.value.roles.map((role) => ({
                                  ...role,
                                  name: role.name.value,
                                  id: role.id.value,
                              })),
                              label: "DLG",
                          },
                          {
                              name: "teacher_statuses",
                              title: "Статусы преподавателя",
                              value: dataTable.value.teacher_statuses.map(
                                  (teacherStatus) => ({
                                      ...teacherStatus,
                                      name: teacherStatus.name.value,
                                      id: teacherStatus.id.value,
                                  })
                              ),

                              label: "DLG",
                          },
                          {
                              name: "departments",
                              title: "Кафедры",
                              value: dataTable.value.departments.map(
                                  (department) => ({
                                      ...department,
                                      name: department.name.value,
                                      id: department.id.value,
                                  })
                              ),
                              label: "DLG",
                          },
                      ],
                      teacher_statuses: [
                          {
                              name: "name",
                              title: "Название статуса",
                              value: "",
                          },
                      ],
                      types: [
                          { name: "name", title: "Название типа", value: "" },
                      ],
                      subjects: [
                          {
                              name: "name",
                              title: "Название предмета",
                              value: "",
                          },
                      ],
                      subtypes: [
                          {
                              name: "name",
                              title: "Название вида деятельности",
                              value: "",
                          },
                      ],
                      study_years: [
                          {
                              name: "start_year",
                              label: "number",
                              title: "Год начала учебного года",
                              value: "",
                          },
                          {
                              name: "end_year",
                              label: "number",
                              title: "Год конца учебного года",
                              value: "",
                          },
                      ],
                      roles: [
                          { name: "name", title: "Название роли", value: "" },
                      ],
                      departments: [
                          {
                              name: "name",
                              title: "Наименование кафедры",
                              value: "",
                          },
                          {
                              name: "user_id",
                              title: "Заведующий кафедрой",
                              value: dataTable.value.users
                                  .filter((user) => user.roles.value[0].id == 3)
                                  .map((user) => ({
                                      ...user,
                                      name:
                                          user.lastname.value == null &&
                                          user.firstname.value == null &&
                                          user.middlename.value == null
                                              ? user.login.value
                                              : (user.lastname.value != null
                                                    ? user.lastname.value + " "
                                                    : "") +
                                                (user.firstname.value != null
                                                    ? user.firstname.value + " "
                                                    : "") +
                                                (user.middlename.value != null
                                                    ? user.middlename.value
                                                    : ""),
                                      id: user.id.value,
                                  })),
                              label: "select",
                          },
                      ],
                      specialties: [
                          {
                              name: "name",
                              title: "Наименование специальности",
                              value: "",
                          },
                          {
                              name: "department_id",
                              title: "Кафедра",
                              value: dataTable.value.departments.map(
                                  (department) => ({
                                      ...department,
                                      name: department.name.value,
                                      id: department.id.value,
                                  })
                              ),
                              label: "select",
                          },
                      ],
                      groups: [
                          {
                              name: "name",
                              title: "Наименование группы",
                              value: "",
                          },
                          {
                              name: "student_count",
                              title: "Количество студентов",
                              value: "",
                              label: "number",
                              min: 1,
                              max: 1000,
                          },
                          {
                              name: "specialty_id",
                              title: "Специальность",
                              value: dataTable.value.specialties.map(
                                  (specialty) => ({
                                      ...specialty,
                                      name: specialty.name.value,
                                      id: specialty.id.value,
                                  })
                              ),
                              label: "select",
                          },
                      ],
                  }
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
            filterData,
            fetchData,
            getColumnNames,
            editRow,
            updateRow,
            clearSelectedRow,
            sidebar,
            table,
            sidebarWidth,
            saveRow,
            deleteRow,
            fetchFunction,
            computedData,
            sideBarItems,
            openEditSideBar,
            editSidebar,
            checkPath,
        };
    },
};
</script>
