<template>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form
                    @submit.prevent="login"
                    class="border p-4 rounded bg-light"
                >
                    <h2 class="mb-4">Приложение "Кафедра". Авторизация</h2>
                    <div class="mb-3">
                        <input
                            type="text"
                            class="form-control"
                            v-model="loginData.login"
                            placeholder="Логин"
                            required
                        />
                    </div>
                    <div class="mb-3">
                        <input
                            type="password"
                            class="form-control"
                            v-model="loginData.password"
                            placeholder="Пароль"
                            required
                        />
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        Войти
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, inject } from "vue";
import { useRouter } from "vue-router";

export default {
    setup() {
        const fetchFunction = inject("fetchFunction");
        const loginData = ref({
            login: "",
            password: "",
        });
        const user = ref();
        const currentUser = ref(null); // Добавлено для хранения информации о текущем пользователе
        const router = useRouter();

        const login = async () => {
            try {
                const response = await fetchFunction("/login", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                    body: JSON.stringify(loginData.value),
                });
                const data = await response.json();
                if (data.user) {
                    console.log(data.user.role.id);
                    switch (data.user.role.id) {
                        case 1:
                            router.push("/admin");
                            break;
                        case 2:
                            router.push("/assistantDirector");
                            break;
                        case 3:
                            router.push("/hoc");
                            break;
                        case 4:
                            router.push("/teacher");
                            break;
                        default:
                            router.push("/login");
                    }
                } else {
                    console.error("Ошибка: пользователь не найден.");
                }
            } catch (err) {
                console.error("Ошибка входа:", err);
            }
        };

        const logout = async () => {
            try {
                const response = await fetch("/logout", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                });
                await response.json();
                router.push("/login");
            } catch (err) {
                console.error("Ошибка выхода:", err);
            }
        };

        const checkAuth = async () => {
            try {
                const response = await fetch("/user", {
                    method: "GET",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                });
                const data = await response.json();
                currentUser.value = data; // Сохранение данных о текущем пользователе
            } catch (err) {
                console.error("Ошибка проверки аутентификации:", err);
            }
        };

        const getCsrfToken = () => {
            const metaTag = document.head.querySelector(
                'meta[name="csrf-token"]'
            );
            return metaTag ? metaTag.content : "";
        };

        return {
            loginData,
            user,
            currentUser,
            logout,
            login,
            checkAuth, // Добавлен метод для проверки аутентификации
            fetchFunction,
            getCsrfToken,
        };
    },
};
</script>

<style>
/* Custom styles */
body {
    background-color: #f8f9fa;
}
</style>
