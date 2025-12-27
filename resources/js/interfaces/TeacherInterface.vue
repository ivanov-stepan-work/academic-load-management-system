<template>
    <div class="d-flex w-100">
        <Sidebar
            ref="sidebar"
            @menuItemClick="tableDisplay"
            :items="sideBarItems"
        />
        <Table
            :rows="tableArray"
            ref="table"
            :style="{ marginLeft: sidebarWidth + 'px' }"
        />
        <div class="flex-grow-1"></div>
    </div>
</template>

<script>
import { ref, computed, onMounted, watch, inject, h } from "vue";
import { useRoute, useRouter } from "vue-router";
import EditSidebar from "../components/EditSidebar.vue";
import Table from "../components/ResultTable.vue";
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
        const sidebar = ref(null);
        const table = ref(null);
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
        const currentUser = ref(null);
        const tableArray = ref([]);
        const isTableVisible = ref(false);
        const editItem = ref([]);
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
        const tableDisplay = (item) => {
            if (!item || !item.items || typeof item.items !== "object") {
                console.error("Invalid item structure:", item);
                return;
            }

            editItem.value = item;
            tableArray.value = [];
            // return;
            Object.values(editItem.value.items.items).forEach(
                (halfYear, halfYearIndex) => {
                    if (!Array.isArray(halfYear.types)) {
                        console.error("Invalid halfYear structure:", halfYear);
                        return;
                    }
                    tableArray.value.push([
                        {
                            name: halfYear.name,
                            width: 3,
                            height: 1,
                            bold: true,
                            tableStyle: "primary",
                        },
                    ]);
                    halfYear.types.forEach((type) => {
                        tableArray.value.push([
                            {
                                name: type.name,
                                width: 3,
                                height: 1,
                                tableStyle: "active",
                                isType: true,
                                bold: true,
                            },
                        ]);

                        if (!Array.isArray(type.subtypes)) {
                            console.error("Invalid type structure:", type);
                            return;
                        }

                        type.subtypes.forEach((subtype) => {
                            tableArray.value.push([
                                {
                                    tableStyle: "light",
                                    name: subtype.name,
                                    width: 3,
                                    height: 1,
                                    isSubtype: true,
                                    bold: true,
                                },
                            ]);

                            if (!Array.isArray(subtype.teacher_loads)) {
                                console.error(
                                    "Invalid subtype structure:",
                                    subtype
                                );
                                return;
                            }
                            console.log(subtype);
                            subtype.teacher_loads.forEach((load) => {
                                console.log(subtype);
                                const row = new Array(3).fill({
                                    name: "",
                                    width: 1,
                                    height: 1,
                                });
                                console.log(halfYearIndex);
                                if (halfYearIndex === 0) {
                                    row[0] = {
                                        name: load.department_load.subject.name,
                                        width: 1,
                                        height: 1,
                                    };
                                    row[1] = {
                                        name: load.department_load.group.name,
                                        width: 1,
                                        height: 1,
                                    };
                                    row[2] = {
                                        name: load.hours_allocated,
                                        width: 1,
                                        height: 1,
                                    };
                                } else {
                                    row[0] = {
                                        name: load.department_load.subject.name,
                                        width: 1,
                                        height: 1,
                                    };
                                    row[1] = {
                                        name: load.department_load.group.name,
                                        width: 1,
                                        height: 1,
                                    };
                                    row[2] = {
                                        name: load.hours_allocated,
                                        width: 1,
                                        height: 1,
                                    };
                                }
                                tableArray.value.push(row);
                            });
                            tableArray.value.push([
                                {
                                    name: "Общее кол-во часов:",
                                    bold: true,
                                    width: 2,
                                    height: 1,
                                    alignment: "end",
                                    tableStyle: "light",
                                },
                                {
                                    tableStyle: "light",
                                    name: subtype.total_hours,
                                    width: 1,
                                    height: 1,
                                    bold: true,
                                },
                            ]);
                            tableArray.value.push([
                                {
                                    name: "Часов в неделю:",
                                    bold: true,
                                    width: 2,
                                    height: 1,
                                    alignment: "end",
                                    tableStyle: "light",
                                },
                                {
                                    tableStyle: "light",
                                    name: subtype.total_hours_per_week,
                                    width: 1,
                                    height: 1,
                                    bold: true,
                                },
                            ]);
                        });
                        tableArray.value.push([
                            {
                                name: "Общее кол-во часов:",
                                bold: true,
                                width: 2,
                                height: 1,
                                alignment: "end",
                                tableStyle: "active",
                            },
                            {
                                name: type.total_hours,
                                width: 1,
                                height: 1,
                                bold: true,
                                tableStyle: "active",
                            },
                        ]);
                        tableArray.value.push(
                            [
                                {
                                    name: "Часов в неделю:",
                                    bold: true,
                                    width: 2,
                                    height: 1,
                                    alignment: "end",
                                    tableStyle: "active",
                                },
                                {
                                    name: type.total_hours_per_week,
                                    width: 1,
                                    height: 1,
                                    bold: true,
                                    tableStyle: "active",
                                },
                            ],
                            [
                                {
                                    name: "",
                                    bold: true,
                                    width: 3,
                                    height: 1,
                                    alignment: "end",
                                },
                            ]
                        );
                    });
                    tableArray.value.push([
                        {
                            name: "Общее кол-во часов:",
                            bold: true,
                            width: 2,
                            height: 1,
                            alignment: "end",
                            tableStyle: "primary",
                        },
                        {
                            tableStyle: "primary",
                            name: halfYear.total_hours,
                            width: 1,
                            height: 1,
                            bold: true,
                        },
                    ]);
                    tableArray.value.push(
                        [
                            {
                                name: "Часов в неделю:",
                                bold: true,
                                width: 2,
                                height: 1,
                                alignment: "end",
                                tableStyle: "primary",
                            },
                            {
                                tableStyle: "primary",
                                name: halfYear.total_hours_per_week,
                                width: 1,
                                height: 1,
                                bold: true,
                            },
                        ],
                        [
                            {
                                name: "",
                                bold: true,
                                width: 3,
                                height: 1,
                                alignment: "end",
                            },
                        ]
                    );
                }
            );

            isTableVisible.value = true;
            console.log(tableArray.value);
        };

        const sideBarItems = ref([]);
        const fetchData = () => {
            fetchFunction("/api/teacher/" + currentUser.value.id)
                .then((response) => response.json())
                .then((data) => {
                    dataTable.value = data;
                    console.log(data);
                    let count = 0;
                    sideBarItems.value = [
                        {
                            header: "Редактирование данных",
                            hiddenOnCollapse: true,
                        },
                        ...dataTable.value.map((study_year) => ({
                            title: study_year.name,
                            href: "/teacher/" + count++,
                            items: study_year,
                        })),
                        {
                            component: separator,
                        },
                        {
                            title: "Выйти",
                            logout: true,
                        },
                    ];
                    // console.log(sideBarItems.value);
                })
                .catch((error) => {
                    console.error("Ошибка при получении данных:", error);
                });
        };

        const sidebarWidth = computed(() => {
            if (sidebar.value) {
                return sidebar.value.$el.clientWidth; // Получаем ширину элемента sidebar
            }
            return 0;
        });
        const checkAndRedirect = () => {
            // Проверяем если isTableVisible равно false
            if (!isTableVisible.value) {
                // Получаем текущий путь
                const currentPath = route.path;

                // Проверяем путь на соответствие шаблону
                const regex = /^\/teacher\/\d+$/;
                if (regex.test(currentPath)) {
                    // Выполняем редирект на /assistantDirector
                    // router.push("/teacher");
                }
            }
        };
        onMounted(() => {
            checkAuth();
            checkAndRedirect();
        });

        return {
            dataTable,
            fetchData,
            fetchFunction,
            sideBarItems,
            tableDisplay,
            tableData,
            isTableVisible,
            tableArray,
            sidebar,
            table,
            sidebarWidth,
            checkAuth,
            currentUser,
            getCsrfToken,
        };
    },
};
</script>
