<template>
    <sidebar-menu
        :menu="items"
        :theme="selectedTheme"
        :show-one-child="true"
        @item-click="onItemClick"
    />
</template>

<script>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import SidebarMenu from "./sidebar/SidebarMenu.vue";

export default {
    components: {
        SidebarMenu,
    },
    name: "App",
    props: {
        items: {
            type: Array,
            required: true,
        },
    },
    setup(props, { emit }) {
        const selectedTheme = ref("white-theme");
        const isOnMobile = ref(false);
        const router = useRouter();

        const getCsrfToken = () => {
            const metaTag = document.head.querySelector(
                'meta[name="csrf-token"]'
            );
            return metaTag ? metaTag.content : "";
        };

        const onItemClick = (event, item) => {
            if (item.logout) {
                fetch("/logout", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        router.push("/login");
                    });
            }
            emit("clearTable");
            emit("clearSelectedRow");
            if (item.items) emit("menuItemClick", item);
            else if (Number.isInteger(item.index))
                emit("menuItemClick", item.index);
        };

        return {
            selectedTheme,
            isOnMobile,
            onItemClick,
        };
    },
};
</script>
