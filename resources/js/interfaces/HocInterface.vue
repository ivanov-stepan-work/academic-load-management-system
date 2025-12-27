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
                :customLimit="true"
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
        const separator = h("hr", {
            style: {
                borderColor: "rgba(0, 0, 0, 0.1)",
                margin: "20px",
            },
        });
        const route = useRoute();
        const router = useRouter();
        const dataTable = ref([]);

        const isTableVisible = ref(false);
        const editItem = ref([]);
        const table = ref(null);
        const sidebar = ref(null);
        const currentUser = ref(null);
        let editSidebar;
        const isDataFetched = ref(false);
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
            // console.log(item);
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
        const columnMappings = [
            "Содержание нагрузки",
            "Часы",
            "Часов в неделю",
        ];
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
                // console.log(row);
                const orderedRow = {
                    name: row.name.value,
                    hours_allocated: row.hours_allocated,
                    hours_per_week: row.hours_per_week,
                };
                return orderedRow;
            });
            return filteredData;
        };
        const fetchData = () => {
            fetchFunction("/api/hoc/" + currentUser.value.id)
                .then((response) => response.json())
                .then((data) => {
                    dataTable.value = data;
                    console.log(data);
                    isDataFetched.value = true;
                    let count = 1;
                    sideBarItems.value = [
                        {
                            header: "Редактирование данных",
                            hiddenOnCollapse: true,
                        },
                        ...dataTable.value.users.map((user) => ({
                            title:
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
                            child: [
                                ...user.study_years.map((study_year) => ({
                                    title: study_year.name.value,
                                    hours: study_year.total_hours_per_week,
                                    child: [
                                        ...study_year.half_years.map(
                                            (half_year) => ({
                                                isColorIndicator: true,
                                                hours: half_year.total_hours_per_week,
                                                title: half_year.name.value,
                                                child: [
                                                    ...half_year.types.map(
                                                        (type) => ({
                                                            // isColorIndicator: true,
                                                            hours: type.total_hours_per_week,
                                                            title: type.name
                                                                .value,
                                                            child: [
                                                                ...type.subtypes.map(
                                                                    (
                                                                        subtype
                                                                    ) => ({
                                                                        // isColorIndicator: true,
                                                                        hours: subtype.total_hours_per_week,
                                                                        title: subtype
                                                                            .name
                                                                            .value,
                                                                        items: subtype.items,
                                                                        href:
                                                                            "/hoc/" +
                                                                            count++,
                                                                        teacher_id:
                                                                            user
                                                                                .id
                                                                                .value,
                                                                        study_year:
                                                                            study_year
                                                                                .id
                                                                                .value,
                                                                        half_year:
                                                                            half_year
                                                                                .value
                                                                                .value,
                                                                        type_id:
                                                                            type
                                                                                .id
                                                                                .value,
                                                                        subtype_id:
                                                                            subtype
                                                                                .id
                                                                                .value,
                                                                    })
                                                                ),
                                                            ],
                                                        })
                                                    ),
                                                ],
                                            })
                                        ),
                                    ],
                                })),
                            ],
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
                    name: "department_load_id",
                    title: "Нагрузка выделенная на кафедру",
                    value: dataTable.value.department_loads.map(
                        (department_load) => ({
                            ...department_load,
                            id: department_load.id,
                            name: `[${department_load.group.name}] ${department_load.subject.name}`,
                            select:
                                originalRow.department_load.id ===
                                department_load.id,
                        })
                    ),
                    label: "select",
                },
                {
                    name: "hours_allocated",
                    title: "Выделенные часы",
                    value: originalRow.hours_allocated,
                    label: "number",
                    min: 1,
                    max: originalRow.max_hours,
                },
            ];
            if (originalRow) {
                selectedRow.value = [
                    {
                        name: "id",
                        value: originalRow.id.value,
                        href: "hoc",
                    },
                    ...data,
                ];
                openEditSideBar();
            }
        };

        const updateRow = (updatedRow) => {
            const index = editItem.value.items.value.findIndex(
                (row) => row.id.value === updatedRow.id.value
            );

            if (index !== -1)
                editItem.value.items.value.splice(index, 1, updatedRow);
            fetchData();
            clearSelectedRow();
            tableDisplay(editItem.value);
            closeEditSidebar();
        };
        const deleteRow = (idRow) => {
            const index = editItem.value.items.value.findIndex(
                (row) => row.id.value === idRow
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
            newRow["teacher_id"] = editItem.value.teacher_id;
            newRow["study_year_id"] = editItem.value.study_year;
            newRow["half_year"] = editItem.value.half_year;
            newRow["type_id"] = editItem.value.type_id;
            newRow["subtype_id"] = editItem.value.subtype_id;
            fetchFunction(`/api/hoc`, {
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
                    fetchData();
                    clearSelectedRow();
                    console.log();
                    editItem.value.items.value.push(data);
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
                const regex = /^\/hoc\/\d+$/;
                if (regex.test(currentPath)) {
                    // Выполняем редирект на /assistantDirector
                    router.push("/hoc");
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
                          name: "department_load_id",
                          title: "Нагрузка выделенная на кафедру",
                          value: dataTable.value.department_loads.map(
                              (department_load) => ({
                                  ...department_load,
                                  id: department_load.id,
                                  name: `[${department_load.group.name}] ${department_load.subject.name}`,
                              })
                          ),
                          label: "select",
                      },
                      {
                          name: "hours_allocated",
                          title: "Выделенные часы",
                          value: "",
                          label: "number",
                          min: 1,
                          max: computed(() => {
                              const selectedDepartmentLoadId = props.items.find(
                                  (item) => item.name === "department_load_id"
                              )?.selectedValue;
                              if (selectedDepartmentLoadId) {
                                  const departmentLoad =
                                      dataTable.value.department_loads.find(
                                          (dl) =>
                                              dl.id === selectedDepartmentLoadId
                                      );
                                  if (
                                      departmentLoad.computed_attributes &&
                                      departmentLoad.computed_attributes
                                          .difference_hours
                                  ) {
                                      return departmentLoad.computed_attributes
                                          .difference_hours;
                                  }
                              }
                              // Fallback max value
                              return 0; // Example fallback max value
                          }),
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
            checkAuth,
            getCsrfToken,
            currentUser,
        };
    },
};
</script>
