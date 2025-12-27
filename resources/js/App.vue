<template>
    <router-view></router-view>
</template>
<script>
import { defineComponent, provide } from "vue";
import { fetchWithBaseUrl } from "./utils/fetchWrapper.js";

export default defineComponent({
    setup() {
        provide("fetchFunction", fetchWithBaseUrl);
    },
});
</script>
