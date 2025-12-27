<template>
    <sidebar-menu
        v-model:collapsed="collapsed"
        :menu="menu"
        :theme="selectedTheme"
        :show-one-child="true"
        @update:collapsed="onToggleCollapse"
        @item-click="onItemClick"
    />
</template>

<script>
import { h } from "vue";
import SidebarMenu from "../sidebar/SidebarMenu.vue";
const separator = h("hr", {
    style: {
        borderColor: "rgba(0, 0, 0, 0.1)",
        margin: "20px",
    },
});

export default {
    components: {
        SidebarMenu,
    },
    name: "App",
    data() {
        return {
            menu: [
                {
                    header: "Редактирование данных",
                    hiddenOnCollapse: true,
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
                    title: "Группы",
                    href: "/admin/groups",
                },
                {
                    title: "Роли",
                    href: "/admin/roles",
                },
                {
                    title: "Специальности",
                    href: "/admin/specialties",
                },
                {
                    title: "Подтипы",
                    href: "/admin/subtypes",
                },
                {
                    title: "Типы",
                    href: "/admin/types",
                },
                {
                    title: "Статусы преподавателя",
                    href: "/admin/teacher_status",
                },
                {
                    title: "Предметы",
                    href: "/admin/subjects",
                },
                {
                    component: separator,
                },
                {
                    title: "Badge",
                    badge: {
                        text: "LOL",
                        class: "vsm--badge_default",
                    },
                },
                {
                    // href: "/page",
                    title: "Dropdown Page",
                    child: [
                        {
                            // href: "/page/sub-page-1",
                            title: "Sub Page 01",
                        },
                        {
                            // href: "/page/sub-page-2",
                            title: "Sub Page 02",
                        },
                    ],
                },
                {
                    title: "Multiple Level",
                    child: [
                        {
                            title: "page",
                        },
                        {
                            title: "Level 2 ",
                            child: [
                                {
                                    title: "page",
                                },
                                {
                                    title: "Page",
                                },
                            ],
                        },
                        {
                            title: "Page",
                        },
                        {
                            title: "Another Level 2",
                            child: [
                                {
                                    title: "Level 3",
                                    child: [
                                        {
                                            title: "Page",
                                        },
                                        {
                                            title: "Page",
                                        },
                                    ],
                                },
                            ],
                        },
                    ],
                },
            ],
            collapsed: false,
            themes: [
                {
                    name: "Default theme",
                    input: "",
                },
                {
                    name: "White theme",
                    input: "white-theme",
                },
            ],
            selectedTheme: "white-theme",
            isOnMobile: false,
        };
    },
    mounted() {
        this.onResize();
        window.addEventListener("resize", this.onResize);
    },
    methods: {
        onToggleCollapse(collapsed) {
            console.log("onToggleCollapse");
        },
        handleInstallationClick() {
            console.log("Installation clicked!");
            // Ваша логика для элемента "Installation"
        },
        handleBasicUsageClick() {
            console.log("Basic Usage clicked!");
        },
        // Другие методы обработки кликов для других элементов меню

        onItemClick(event, item) {
            this.$emit("clearTable");
            const endpoint = item.href.split("/").pop();
            this.$emit("clearSelectedRow");

            this.$emit("menuItemClick", endpoint);
        },

        onResize() {
            if (window.innerWidth <= 767) {
                this.isOnMobile = true;
                this.collapsed = true;
            } else {
                this.isOnMobile = false;
                this.collapsed = false;
            }
        },
    },
};
</script>
