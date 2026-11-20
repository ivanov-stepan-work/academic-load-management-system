<template>
    <main class="login-page">
        <div class="login-card">
            <header class="login-brand">
                <p class="login-brand-name">Кафедра</p>
                <p class="login-brand-description">Система управления учебной нагрузкой</p>
            </header>

            <form class="login-form" :aria-busy="submitting" @submit.prevent="login">
                <h1>Вход в систему</h1>

                <div class="login-feedback" role="alert">
                    <p v-if="errorMessage" class="login-error">{{ errorMessage }}</p>
                </div>

                <div class="login-field">
                    <label for="login-username">Логин</label>
                    <input
                        id="login-username"
                        v-model="credentials.login"
                        name="login"
                        type="text"
                        class="form-control"
                        autocomplete="username"
                        autocapitalize="none"
                        spellcheck="false"
                        :readonly="submitting"
                        required
                        @input="errorMessage = ''"
                    />
                </div>

                <div class="login-field">
                    <label for="login-password">Пароль</label>
                    <div class="login-password">
                        <input
                            id="login-password"
                            v-model="credentials.password"
                            name="password"
                            :type="passwordVisible ? 'text' : 'password'"
                            class="form-control"
                            autocomplete="current-password"
                            autocapitalize="none"
                            spellcheck="false"
                            :readonly="submitting"
                            required
                            @input="errorMessage = ''"
                        />
                        <button
                            type="button"
                            class="login-password-toggle"
                            aria-controls="login-password"
                            @click="passwordVisible = !passwordVisible"
                        >
                            {{ passwordVisible ? "Скрыть" : "Показать" }}<span class="visually-hidden"> пароль</span>
                        </button>
                    </div>
                </div>

                <button class="btn btn-primary login-submit" type="submit" :disabled="submitting">
                    <span v-if="submitting" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    {{ submitting ? "Выполняется вход…" : "Войти" }}
                </button>
            </form>
        </div>
    </main>
</template>

<script setup>
import { inject, reactive, ref } from "vue";
import { useRouter } from "vue-router";

const roleRoutes = {
    1: "/admin",
    2: "/assistantDirector",
    3: "/hoc",
    4: "/teacher",
};

const fetchFunction = inject("fetchFunction");
const router = useRouter();
const credentials = reactive({ login: "", password: "" });
const passwordVisible = ref(false);
const submitting = ref(false);
const errorMessage = ref("");

async function login() {
    if (submitting.value) return;

    submitting.value = true;
    errorMessage.value = "";

    try {
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content ?? "";
        const response = await fetchFunction("/login", {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(credentials),
        });

        if (response.status === 401) {
            errorMessage.value = "Неверный логин или пароль.";
            return;
        }
        if (response.status === 419) {
            errorMessage.value = "Срок действия страницы истёк. Обновите её и попробуйте снова.";
            return;
        }
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        const destination = roleRoutes[data.user?.role?.id];
        if (!destination) {
            errorMessage.value = "Для вашей учётной записи не настроен доступ к системе.";
            return;
        }

        credentials.password = "";
        await router.push(destination);
    } catch {
        errorMessage.value = "Не удалось войти. Попробуйте ещё раз.";
    } finally {
        submitting.value = false;
    }
}
</script>

<style scoped>
.login-page {
    display: grid;
    min-height: 100vh;
    min-height: 100dvh;
    place-items: center;
    padding: clamp(1rem, 5vw, 3rem);
    background: radial-gradient(ellipse at top, #e2ebfc, #f4f7fb 65%);
    color: #17243a;
}

.login-card {
    display: grid;
    grid-template-columns: minmax(0, .85fr) minmax(0, 1fr);
    width: min(100%, 49rem);
    overflow: hidden;
    border: 1px solid #dce5f2;
    border-radius: 1.4rem;
    background: #fff;
    box-shadow: 0 18px 54px rgb(32 62 111 / 13%);
}

.login-brand {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: clamp(1.5rem, 5vw, 3rem);
    background: linear-gradient(145deg, #18366e, #234fbd);
    color: #fff;
}

.login-brand-name {
    margin: 0 0 .5rem;
    font-size: clamp(1.75rem, 4vw, 2.75rem);
    font-weight: 750;
    letter-spacing: -.035em;
}

.login-brand-description {
    max-width: 18rem;
    margin: 0;
    color: #e1eaff;
}

.login-form {
    display: grid;
    gap: 1.1rem;
    padding: clamp(1.5rem, 5vw, 3rem);
}

.login-form h1 {
    margin: 0 0 .25rem;
    font-size: clamp(1.45rem, 4vw, 2rem);
    font-weight: 750;
    letter-spacing: -.025em;
}

.login-field {
    display: grid;
    gap: .4rem;
}

.login-field label {
    font-size: .9rem;
    font-weight: 650;
}

.login-field .form-control {
    min-height: 2.9rem;
    border-color: #c9d4e4;
    border-radius: .65rem;
    color: #17243a;
}

.login-password {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    border: 1px solid #c9d4e4;
    border-radius: .65rem;
}

.login-password:focus-within {
    border-color: #7595da;
    box-shadow: 0 0 0 .25rem rgb(35 79 189 / 18%);
}

.login-password .form-control {
    border: 0;
    border-start-end-radius: 0;
    border-end-end-radius: 0;
}

.login-password .form-control:focus {
    box-shadow: none;
}

.login-password-toggle {
    padding: .5rem .7rem;
    border: 0;
    border-inline-start: 1px solid #c9d4e4;
    border-start-end-radius: calc(.65rem - 1px);
    border-end-end-radius: calc(.65rem - 1px);
    background: #f4f7ff;
    color: #234fbd;
    font-size: .875rem;
    font-weight: 650;
}

.login-password input::-ms-reveal {
    display: none;
}

.login-password-toggle:hover {
    background: #e6eeff;
}

.login-password-toggle:focus-visible {
    outline: 2px solid #234fbd;
    outline-offset: -3px;
}

.login-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .55rem;
    min-height: 2.9rem;
    margin-top: .25rem;
    border-radius: .65rem;
    font-weight: 650;
    --bs-btn-bg: #234fbd;
    --bs-btn-border-color: #234fbd;
    --bs-btn-hover-bg: #1a429f;
    --bs-btn-hover-border-color: #1a429f;
}

.login-feedback {
    min-height: 4.25rem;
}

.login-error {
    margin: 0;
    padding: .65rem .8rem;
    border: 1px solid #f1c2c0;
    border-radius: .6rem;
    background: #fff2f1;
    color: #8f2424;
    font-size: .9rem;
}

@media (max-width: 42rem) {
    .login-card {
        grid-template-columns: minmax(0, 1fr);
        max-width: 27rem;
    }
}
</style>
