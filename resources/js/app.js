import "./bootstrap";
// Added: Actual Bootstrap JavaScript dependency
import "bootstrap";
// Added: Popper.js dependency for popover support in Bootstrap
import "@popperjs/core";
import { createApp } from "vue";
import { createRouter, createWebHistory } from "vueRouter";
import App from "./App.vue";
import AdminInterface from "./interfaces/AdminInterface.vue";
import AssistantDirectorInterface from "./interfaces/AssistantDirectorInterface.vue";
import HocInterface from "./interfaces/HocInterface.vue";
import TeacherInterface from "./interfaces/TeacherInterface.vue";
import LoginInterface from "./interfaces/LoginInterface.vue";
const routes = [
    { path: "/admin/:endpoint", component: AdminInterface },
    { path: "/admin", component: AdminInterface },
    {
        path: "/assistantDirector/:endpoint",
        component: AssistantDirectorInterface,
    },
    {
        path: "/assistantDirector/",
        component: AssistantDirectorInterface,
    },
    {
        path: "/hoc/",
        component: HocInterface,
    },
    {
        path: "/hoc/:endpoint",
        component: HocInterface,
    },
    {
        path: "/teacher/",
        component: TeacherInterface,
    },
    {
        path: "/teacher/:endpoint",
        component: TeacherInterface,
    },
    {
        path: "/login",
        component: LoginInterface,
    },
    { path: "/", redirect: "/login" },
];
const router = createRouter({
    // history: createWebHistory("/4Kurs/Ivanov/laravel/public/"),
    history: createWebHistory(),
    routes,
});

const app = createApp(App);
app.use(router);
app.mount("#app");
