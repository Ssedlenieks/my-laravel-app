<template>
    <div class="login-wrapper">
        <div class="login">
            <h2>{{ isLogin ? 'Log In' : 'Create an account' }}</h2>
            <form @submit.prevent="handleSubmit">
                <div v-if="!isLogin" class="input-group">
                    <label for="username">Username</label>
                    <input v-model="username" type="text" id="username" />
                    <p v-if="usernameError" class="error-text">{{ usernameError }}</p>
                </div>

                <div class="input-group">
                    <label for="email">Email</label>
                    <input v-model="email" type="text" id="email" />
                    <p v-if="emailError" class="error-text">{{ emailError }}</p>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <input v-model="password" type="password" id="password" />
                    <p v-if="passwordError" class="error-text">{{ passwordError }}</p>
                </div>

                <div v-if="!isLogin" class="input-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input v-model="confirmPassword" type="password" id="confirmPassword" />
                    <p v-if="confirmPasswordError" class="error-text">{{ confirmPasswordError }}</p>
                </div>

                <button type="submit" :disabled="!formIsValid">
                    {{ isLogin ? 'Enter' : 'Registration' }}
                </button>
            </form>

            <p @click="toggleMode" class="toggle-text">
                {{ isLogin ? 'Create an account' : 'Log In' }}
            </p>

            <transition name="fade">
                <div v-if="visible" class="error-notification">
                    {{ message }}
                </div>
            </transition>

            <button @click="goHome" class="home-button">Go to Home</button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

// Regex pārbaudes priekš reģistrācijas
const usernameRegex = /^[A-Za-z0-9_]{7,}$/;
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const passwordRegex = /^(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?])(?=.*\d).{8,}$/;

const isLogin = ref(true);
const username = ref('');
const email = ref('');
const password = ref('');
const confirmPassword = ref('');
const visible = ref(false);
const message = ref('');

const goHome = () => {
    router.visit('/');
};

const toggleMode = () => {
    isLogin.value = !isLogin.value;
    username.value = '';
    email.value = '';
    password.value = '';
    confirmPassword.value = '';
};

// Validācija, paziņojumi par nepareizo formātu
const usernameError = computed(() => {
    if (isLogin.value) return '';
    if (!username.value) return 'Username is required';
    if (!usernameRegex.test(username.value)) return 'At least 7 characters, without special characters';
    return '';
});

const emailError = computed(() => {
    if (!email.value) return 'Email is required';
    if (!emailRegex.test(email.value)) return 'Invalid email format';
    return '';
});

const passwordError = computed(() => {
    if (!password.value) return 'Password is required';
    if (!passwordRegex.test(password.value)) return 'Minimum of 8 chars, including special character and number';
    return '';
});

const confirmPasswordError = computed(() => {
    if (isLogin.value) return '';
    if (confirmPassword.value !== password.value) return 'Passwords do not match';
    return '';
});

const formIsValid = computed(() => {
    const baseValid = !emailError.value && !passwordError.value;
    if (isLogin.value) {
        return baseValid;
    }
    return baseValid && !usernameError.value && !confirmPasswordError.value;
});

const showMessage = (msg) => {
    message.value = msg;
    visible.value = true;
    setTimeout(() => {
        visible.value = false;
    }, 3000);
};

const handleSubmit = async () => {
    if (!formIsValid.value) {
        showMessage('Please correct the errors above');
        return;
    }

    const formData = {
        ...(isLogin.value ? {} : { username: username.value }),
        email: email.value,
        password: password.value,
        ...(isLogin.value ? {} : { password_confirmation: confirmPassword.value }),
    };

    try {
        const endpoint = isLogin.value ? '/login' : '/register';
        await axios.get('/sanctum/csrf-cookie');
        await axios.post(endpoint, formData, { withCredentials: true });
        showMessage(isLogin.value ? 'Login successful' : 'Registration successful');
        setTimeout(() => window.location.href = '/profile', 1000);
    } catch (error) {
        const res = error.response;
        if (res && res.status === 401) {
            showMessage('Incorrect email or password');
        } else if (res?.data?.message) {
            showMessage('Error: ' + res.data.message);
        } else {
            showMessage('Unexpected error occurred');
        }
        console.error(error);
    }
};
</script>
<style scoped>
.login-wrapper {
    background-image: url('https://theplanetapp.com/wp-content/uploads/2022/08/fast-fashion-1-scaled-1-scaled.webp');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login {
    max-width: 600px;
    width: 90vw;
    margin: 50px auto;
    padding: 20px;
    text-align: center;
    background-color: var(--post-bg);
    border: 1px solid var(--post-border);
    border-radius: 10px;
    box-shadow: rgba(0, 0, 0, 0.1) 1px 1px 10px;
    color: var(--post-text);
    transition: background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}

h2 {
    font-family: 'Perfectly Vintages';
    font-size: 40px;
    font-weight: normal;
    margin-bottom: 30px;
    color: var(--post-text);
}

.input-group {
    margin-bottom: 20px;
    text-align: left;
}

label {
    display: block;
    font-family: 'Perfectly Vintages';
    font-size: 20px;
    margin-bottom: 10px;
    margin-left: 15px;
    color: var(--post-text);
}

input {
    width: calc(100% - 30px);
    padding: 12px;
    margin-left: 15px;
    font-size: 16px;
    border: 1px solid var(--input-border);
    border-radius: 5px;
    background-color: var(--input-bg);
    color: var(--post-text);
    transition: background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}

input:focus {
    outline: none;
    border-color: var(--input-border);
}

button[type="submit"] {
    width: 100%;
    padding: 12px;
    margin-top: 20px;
    background-color: var(--input-bg);
    color: var(--post-text);
    border: 1px solid var(--input-border);
    border-radius: 5px;
    cursor: pointer;
    font-family: 'Perfectly Vintages';
    font-size: 20px;
    transition: filter 0.2s ease,
    background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}

button[type="submit"]:hover:not(:disabled) {
    filter: brightness(1.1);
}

button[type="submit"]:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.toggle-text {
    display: block;
    margin-top: 20px;
    font-family: 'Perfectly Vintages';
    font-size: 20px;
    color: var(--post-text);
    cursor: pointer;
    transition: text-decoration 0.2s;
}

.toggle-text:hover {
    text-decoration: underline;
}

.error-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #f44336;
    color: white;
    padding: 15px 25px;
    border-radius: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    font-family: 'Perfectly Vintages', sans-serif;
    font-size: 18px;
}

.home-button {
    position: fixed;
    top: 10px;
    left: 20px;
    padding: 10px 15px;
    background-color: var(--input-bg);
    color: var(--post-text);
    border: 1px solid var(--input-border);
    border-radius: 5px;
    cursor: pointer;
    font-family: 'Perfectly Vintages';
    font-size: 20px;
    transition: filter 0.2s ease,
    background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
    z-index: 1000;
}

.home-button:hover {
    filter: brightness(1.1);
}

.error-text {
    margin-left: 15px;
    color: #e74c3c;
    font-size: 14px;
}
</style>
