<template>
    <nav :class="{ dark: isDarkMode }">
        <div class="left">
            <h1 @click="goHome">Labas Ziņas</h1>
        </div>
        <!-- Kategoriju izvēle -->
        <div class="center">
            <a href="/tech" class="desktop-only">Tehnoloģijas</a>
            <a href="/sports" class="desktop-only">Sports</a>
            <a href="/studies" class="desktop-only">Pētījumi</a>
            <a href="/kultura" class="desktop-only">Kultūra</a>
            <a href="/ekonomika" class="desktop-only">Ekonomika</a>
            <a href="/politika" class="desktop-only">Politika</a>
        </div>
        <!-- Tumšā režīma pārslēgs un lietotāja profila poga -->
        <div class="right">
            <i :class="isDarkMode ? 'fas fa-sun' : 'fas fa-moon'" @click="toggleDarkMode"></i>
            <a @click.prevent="goToProfile">
                <i class="fas fa-user"></i>
                <span v-if="user" class="username">{{ user.name }}</span>
            </a>
            <i class="fas fa-bars mobile-only" @click="toggleMenu"></i>
        </div>
        <!-- Kategoriju izvēle responsivāi versijai -->
        <div v-if="isMenuOpen" class="mobile-menu">
            <a href="/tech">Tehnoloģijas</a>
            <a href="/sports">Sports</a>
            <a href="/studies">Pētījumi</a>
            <a href="/kultura">Kultūra</a>
            <a href="/ekonomika">Ekonomika</a>
            <a href="/politika">Politika</a>
        </div>
    </nav>
</template>

<script>
import axios from 'axios';
export default {
    name: 'Navbar',
    data() {
        return {
            isDarkMode: false,
            isMenuOpen: false,
            user: null
        };
    },
    methods: {
        goHome() {
            this.$inertia.visit('/');
        },
        toggleDarkMode() {
            if (window.darkModeManager) {
                window.darkModeManager.toggle();
                this.isDarkMode = window.darkModeManager.getCurrentMode();
            }
        },
        toggleMenu() {
            this.isMenuOpen = !this.isMenuOpen;
        },
        async fetchUser() {
            try {
                const response = await axios.get('/user');
                this.user = response.data;
            } catch (e) {
                this.user = null;
            }
        },
        goToProfile() {
            if (this.user) {
                this.$inertia.visit('/profile');
            } else {
                this.$inertia.visit('/login');
            }
        },
    },
    mounted() {
        if (window.darkModeManager) {
            this.isDarkMode = window.darkModeManager.getCurrentMode();
        }
        this.fetchUser();
    }
};
</script>

<style scoped>
nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    height: 100px;
    background-color: whitesmoke;
    box-shadow: lightblue 1px 1px 10px;
    font-family: "Aileron";
    position: relative;
}

body.dark nav {
    background-color: #222;
    box-shadow: rgb(0, 0, 0) 1px 1px 10px;
}

.left {
    display: flex;
    align-items: center;
}

.center {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 30px;
}

.right {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 30px;
}

.left h1 {
    font-family: "AbrilFatface";
    position: static;
    transform: none;
    font-size: 40px;
    cursor: pointer;
    margin-right: 30px;
}

h1:hover {
    color: rgb(255, 0, 0);
}

a {
    font-size: 20px;
    text-decoration: none;
    color: black;
    font-weight: normal;
    transition: color 0.3s ease;
}

a:hover {
    color: rgb(255, 0, 0);
}

body.dark nav a {
    color: whitesmoke;
}

body.dark nav a:hover {
    color: rgb(255, 0, 0);
}

body.dark nav i {
    color: whitesmoke;
}

body.dark nav i:hover {
    color: rgb(255, 0, 0);
}

.mobile-menu {
    position: absolute;
    top: 100px;
    left: 0;
    width: 100%;
    background: whitesmoke;
    display: flex;
    flex-direction: column;
    text-align: center;
    padding: 10px;
    box-shadow: lightblue 1px 1px 5px;
    transition: all 0.3s ease;
    z-index: 10;
}

body.dark .mobile-menu {
    background: #333;
}

.mobile-menu a {
    padding: 10px;
    font-size: 18px;
}

@media (max-width: 900px) {
    .center {
        display: none;
    }
    .mobile-only {
        display: block;
    }
    .left h1 {
        font-size: 24px;
    }
}

@media (min-width: 901px) {
    .mobile-only {
        display: none;
    }
}

nav i {
    font-size: 20px;
}

nav .fa-sun,
nav .fa-moon,
nav .fa-search,
nav .fa-bars {
    font-size: 20px;
}

body.dark nav .fa-sun,
body.dark nav .fa-moon,
body.dark nav .fa-search,
body.dark nav .fa-bars {
    color: whitesmoke;
}

body.dark nav .fa-sun:hover,
body.dark nav .fa-moon:hover,
body.dark nav .fa-bars:hover {
    color: rgb(255, 0, 0);
}

body nav i:hover {
    color: rgb(255, 0, 0);
}

.username {
    margin-left: 8px;
    font-size: 16px;
    font-weight: bold;
}

</style>
