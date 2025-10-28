<template>
    <div class="profile">
        <h1>Jūsu profils</h1>

        <div class="avatar">
            <img :src="user.profile_photo_url" alt="Avatar" />
        </div>

        <div class="profile-info">
            <div class="info-item">
                <label>Lietotājvārds:</label>
                <span>{{ user.username }}</span>
            </div>
            <div class="info-item">
                <label>E-pasts:</label>
                <span>{{ user.email }}</span>
            </div>
            <div class="info-item">
                <label>Apraksts:</label>
                <span>{{ user.bio || 'Nav pievienots' }}</span>
            </div>
            <div class="info-item">
                <label>Visi ieraksti:</label>
                <span>{{ postCount }}</span>
            </div>
            <div class="info-item">
                <label>Kopējais reakciju skaits:</label>
                <span>{{ totalReactions }}</span>
            </div>
        </div>

        <button @click="editProfile">Rediģēt profilu</button>
        <button @click="goHome">Uz sākumlapu</button>
        <button @click="logout">Iziet</button>

        <transition name="fade">
            <div v-if="isEditing" class="edit-form">
                <h2>Rediģēt profilu</h2>

                <div class="input-group">
                    <label for="username">Lietotājvārds</label>
                    <input v-model="form.username" type="text" id="username" />
                    <p v-if="usernameError" class="error-text">{{ usernameError }}</p>
                </div>

                <div class="input-group">
                    <label for="email">E-pasts</label>
                    <input v-model="form.email" type="email" id="email" />
                    <p v-if="emailError" class="error-text">{{ emailError }}</p>
                </div>

                <div class="input-group">
                    <label for="description">Apraksts</label>
                    <textarea v-model="form.bio" id="description" rows="4"></textarea>
                </div>

                <div class="input-group">
                    <label for="photo">Profila attēls</label>
                    <input @change="onFileChange" type="file" id="photo" accept="image/*" />
                </div>
                <div v-if="preview" class="input-group">
                    <label>Priekšskatījums</label>
                    <img :src="preview" class="preview-img" />
                </div>

                <button @click="saveProfile" :disabled="!formIsValid">Saglabāt</button>
                <button @click="cancelEdit">Atcelt</button>
            </div>
        </transition>

        <transition name="fade">
            <div v-if="visible" class="error-notification">
                {{ message }}
            </div>
        </transition>

        <button v-if="isAdmin" @click="toggleUserPanel" class="admin-toggle">
            {{ showUsers ? 'Paslēpt administratora paneli' : 'Rādīt administratora paneli' }}
        </button>

        <transition name="fade">
            <div v-if="showUsers" class="admin-panel-box">
                <h2>Administratora panelis</h2>
                
                <div class="add-user-section">
                    <h3>Pievienot jaunu lietotāju</h3>
                    <div class="input-group">
                        <label for="new-username">Lietotājvārds</label>
                        <input 
                            v-model="newUser.username" 
                            type="text" 
                            id="new-username" 
                            placeholder="7 rakstzīmes"
                        />
                        <p v-if="newUserUsernameError" class="error-text">{{ newUserUsernameError }}</p>
                    </div>
                    <div class="input-group">
                        <label for="new-email">E-pasts</label>
                        <input 
                            v-model="newUser.email" 
                            type="email" 
                            id="new-email" 
                            placeholder="user@example.com"
                        />
                        <p v-if="newUserEmailError" class="error-text">{{ newUserEmailError }}</p>
                    </div>
                    <div class="input-group">
                        <label for="new-password">Parole</label>
                        <input 
                            v-model="newUser.password" 
                            type="password" 
                            id="new-password" 
                            placeholder="8 rakstzīmes (cipars, spec. simbols)"
                        />
                        <p v-if="newUserPasswordError" class="error-text">{{ newUserPasswordError }}</p>
                    </div>
                    <div class="input-group checkbox-group">
                        <label>
                            <input 
                                v-model="newUser.is_admin" 
                                type="checkbox"
                            />
                            Padarīt šo lietotāju par administratoru
                        </label>
                    </div>
                    <button 
                        @click="addNewUser" 
                        :disabled="!newUserFormIsValid"
                        class="btn-add"
                    >
                        Pievienot lietotāju
                    </button>
                </div>

                <hr class="divider" />

                <div class="users-list-section">
                    <h3>Esošie lietotāji</h3>
                    <ul>
                        <li v-for="u in users" :key="u.id" class="user-item">
                            <div class="user-info">
                                <span class="user-details">
                                    <strong>{{ u.username }}</strong> ({{ u.email }})
                                </span>
                                <span v-if="u.is_admin" class="badge-admin">ADMINISTRATORS</span>
                                <span v-else class="badge-user">LIETOTĀJS</span>
                            </div>
                            <div v-if="u.id !== user.id" class="user-actions">
                                <button
                                    v-if="!u.is_admin"
                                    @click="promoteToAdmin(u.id)"
                                    class="btn-promote"
                                >
                                    Padarīt par administratoru
                                </button>
                                <button
                                    v-if="u.is_admin"
                                    @click="demoteFromAdmin(u.id)"
                                    class="btn-demote"
                                >
                                    Noņemt administratora lomu
                                </button>
                                <button
                                    @click="deleteUser(u.id)"
                                    class="btn-delete"
                                >
                                    Dzēst
                                </button>
                            </div>
                            <div v-else class="current-user-label">
                                Jūs
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const usernameRegex = /^[A-Za-z0-9_]{7,}$/;
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const passwordRegex = /^.{8,}$/;

const user = ref({ id: null, username: '', email: '', bio: '', profile_photo_url: '' });
const form = ref({ username: '', email: '', bio: '' });
const isEditing = ref(false);
const file = ref(null);
const preview = ref(null);
const message = ref('');
const visible = ref(false);
const postCount = ref(0);
const totalReactions = ref(0);

const users = ref([]);
const isAdmin = ref(false);
const currentUserId = ref(null);
const showUsers = ref(false);

const newUser = ref({
    username: '',
    email: '',
    password: '',
    is_admin: false
});

const usernameError = computed(() => {
    if (!isEditing.value) return '';
    if (!form.value.username) return 'Lietotājvārds ir obligāts';
    if (!usernameRegex.test(form.value.username)) return 'Lietotājvārds vismaz 7 rakstzīmes';
    return '';
});

const emailError = computed(() => {
    if (!isEditing.value) return '';
    if (!form.value.email) return 'E-pasts ir obligāts';
    if (!emailRegex.test(form.value.email)) return 'Nederīgs e-pasta formāts';
    return '';
});

const formIsValid = computed(() => {
    return !usernameError.value && !emailError.value;
});

const newUserUsernameError = computed(() => {
    if (!newUser.value.username) return '';
    if (!usernameRegex.test(newUser.value.username)) return 'Lietotājvārds vismaz 7 rakstzīmes (burti, cipari, speciālais simbols)';
    return '';
});

const newUserEmailError = computed(() => {
    if (!newUser.value.email) return '';
    if (!emailRegex.test(newUser.value.email)) return 'Nederīgs e-pasta formāts';
    return '';
});

const newUserPasswordError = computed(() => {
    if (!newUser.value.password) return '';
    if (!passwordRegex.test(newUser.value.password)) return 'Parolei jābūt vismaz 8 rakstzīmēm';
    return '';
});

const newUserFormIsValid = computed(() => {
    return newUser.value.username && 
           newUser.value.email && 
           newUser.value.password &&
           !newUserUsernameError.value && 
           !newUserEmailError.value && 
           !newUserPasswordError.value;
});

function onFileChange(e) {
    const [f] = e.target.files;
    if (!f) return;
    file.value = f;
    preview.value = URL.createObjectURL(f);
}

async function fetchUser() {
    try {
        const { data } = await axios.get('/user');
        user.value = {
            id: data.id,
            username: data.username,
            email: data.email,
            bio: data.bio || '',
            profile_photo_url: data.profile_photo ? `/storage/${data.profile_photo}` : ''
        };
        form.value.username = data.username;
        form.value.email = data.email;
        form.value.bio = data.bio || '';
    } catch {
        router.visit('/login');
    }
}

async function fetchStats() {
    if (!user.value.id) return;
    try {
        const { data: posts } = await axios.get('/posts', {
            params: { user_id: user.value.id }
        });
        postCount.value = Array.isArray(posts) ? posts.length : 0;
        let reactionsSum = 0;
        for (const post of posts) {
            if (post.reactionCounts && (typeof post.reactionCounts.like === 'number' || typeof post.reactionCounts.dislike === 'number' || typeof post.reactionCounts.heart === 'number')) {
                const like = Number(post.reactionCounts.like || 0);
                const dislike = Number(post.reactionCounts.dislike || 0);
                const heart = Number(post.reactionCounts.heart || 0);
                reactionsSum += like + dislike + heart;
            } else {
                try {
                    const { data: reactions } = await axios.get(`/posts/${post.id}/reactions`);
                    const like = Number(reactions.like || 0);
                    const dislike = Number(reactions.dislike || 0);
                    const heart = Number(reactions.heart || 0);
                    reactionsSum += like + dislike + heart;
                } catch (err) {
                    console.error(`Failed to fetch reactions for post ${post.id}:`, err);
                }
            }
        }
        totalReactions.value = reactionsSum;
    } catch (e) {
        console.error('Error fetching stats:', e);
    }
}

function editProfile() {
    isEditing.value = true;
    preview.value = null;
    file.value = null;
}

function cancelEdit() {
    isEditing.value = false;
    preview.value = null;
    file.value = null;
}

function showMessage(msg) {
    message.value = msg;
    visible.value = true;
    setTimeout(() => (visible.value = false), 3000);
}

async function saveProfile() {
    if (!formIsValid.value) {
        showMessage('Datu formāts nav pareizs');
        return;
    }
    try {
        const formData = new FormData();
        formData.append('username', form.value.username);
        formData.append('email', form.value.email);
        formData.append('bio', form.value.bio);
        if (file.value) formData.append('profile_photo', file.value);

        const { data } = await axios.post('/update-profile', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        user.value.username = data.user.username;
        user.value.email = data.user.email;
        user.value.bio = data.user.bio;
        user.value.profile_photo_url = data.user.profile_photo_url;

        isEditing.value = false;
        preview.value = null;
        file.value = null;
        await fetchStats();
    } catch (e) {
        console.error('Error saving profile:', e);
        showMessage('Ķļūda saglabājot profilu');
    }
}

async function fetchUsers() {
    try {
        const { data } = await axios.get('/users');
        users.value = data;
    } catch (e) {
        console.error("Failed to fetch users", e);
    }
}

async function checkAdminStatus() {
    try {
        const { data } = await axios.get('/user');
        isAdmin.value = !!data.is_admin;
        currentUserId.value = data.id;
        if (isAdmin.value) {
            await fetchUsers();
        }
    } catch {}
}

function toggleUserPanel() {
    showUsers.value = !showUsers.value;
}

async function addNewUser() {
    if (!newUserFormIsValid.value) {
        showMessage('Lūdzu, aizpildiet visus laukus pareizi');
        return;
    }
    
    try {
        await axios.post('/admin/users', {
            username: newUser.value.username,
            email: newUser.value.email,
            password: newUser.value.password,
            is_admin: newUser.value.is_admin
        });
        
        showMessage('Lietotājs veiksmīgi pievienots');

        newUser.value = {
            username: '',
            email: '',
            password: '',
            is_admin: false
        };
        
        await fetchUsers();
    } catch (e) {
        console.error("Failed to add user:", e);
        showMessage(e.response?.data?.message || 'Kļūda, pievienojot lietotāju');
    }
}

async function promoteToAdmin(userId) {
    if (!confirm("Padarīt šo lietotāju par administratoru?")) return;
    
    try {
        await axios.patch(`/admin/users/${userId}/role`, {
            is_admin: true
        });
        
        showMessage('User promoted to admin');
        await fetchUsers();
    } catch (e) {
        console.error("Failed to promote user:", e);
        showMessage('Kļūda, paaugstinot lietotāja lomu');
    }
}

async function demoteFromAdmin(userId) {
    if (!confirm("Atņemt administratora privilēģijas šim lietotājam?")) return;

    try {
        await axios.patch(`/admin/users/${userId}/role`, {
            is_admin: false
        });

        showMessage('Administratora loma atņemta');
        await fetchUsers();
    } catch (e) {
        console.error("Failed to demote user:", e);
        showMessage('Kļūda, atņemot administratora lomu');
    }
}

async function deleteUser(id) {
    if (!confirm("Dzēst šo lietotāju? Šo darbību nevar atcelt.")) return;
    try {
        await axios.delete(`/users/${id}`);
        showMessage('Lietotājs veiksmīgi dzēsts');
        await fetchUsers();
    } catch (e) {
        console.error("Failed to delete user:", e);
        showMessage('Kļūda, dzēšot lietotāju');
    }
}

const logout = async () => {
    await axios.post('/logout');
    router.visit('/');
};

const goHome = () => router.visit('/');

onMounted(async () => {
    await fetchUser();
    await fetchStats();
    await checkAdminStatus();
});
</script>

<style scoped>
.profile {
    max-width: 700px;
    margin: 50px auto;
    padding: 20px;
    background-color: var(--post-bg);
    border: 1px solid var(--post-border);
    border-radius: 5px;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
    font-size: 16px;
    display: flex;
    flex-direction: column;
    color: var(--post-text);
    transition: all 0.3s ease;
}

.profile h1 {
    font-family: "Aileron", serif;
    font-size: 48px;
    margin: 0 0 20px 0;
    color: var(--post-text);
}

.avatar {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.avatar img {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 50%;
    background-color: var(--post-bg);
    border: 1px solid var(--post-border);
}

.profile-info {
    font-size: 16px;
    text-align: left;
    margin-bottom: 20px;
    padding: 16px;
    background-color: var(--input-bg);
    border-radius: 4px;
}

.info-item {
    margin-bottom: 12px;
}

.info-item label {
    font-weight: bold;
    margin-right: 10px;
    color: var(--post-text);
}

.info-item span {
    color: var(--post-text);
}

button {
    background-color: transparent;
    border: 1px solid var(--input-border);
    color: var(--post-text);
    border-radius: 4px;
    padding: 8px 14px;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin: 8px 6px 0 0;
}

button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.error-text {
    color: #e74c3c;
    font-size: 14px;
    margin-top: 6px;
}

.error-notification {
    margin-top: 16px;
    padding: 12px;
    background-color: var(--input-bg);
    color: var(--post-text);
    border: 1px solid var(--input-border);
    border-radius: 4px;
    font-size: 14px;
}

.edit-form {
    background-color: var(--post-bg);
    border: 1px solid var(--post-border);
    border-radius: 5px;
    padding: 20px;
    margin-top: 20px;
    text-align: left;
    color: var(--post-text);
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
}

.edit-form h2 {
    font-family: "Aileron", serif;
    font-size: 32px;
    margin: 0 0 20px 0;
    color: var(--post-text);
}

.input-group {
    margin-bottom: 16px;
}

.input-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: var(--post-text);
    font-size: 15px;
}

.input-group input,
.input-group textarea {
    width: 100%;
    padding: 8px;
    background-color: var(--input-bg);
    color: var(--post-text);
    border: 1px solid var(--input-border);
    border-radius: 4px;
    font-size: 15px;
    box-sizing: border-box;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    font-weight: normal;
    font-size: 15px;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
}

.preview-img {
    max-width: 100px;
    max-height: 100px;
    border-radius: 4px;
    display: block;
    margin-top: 6px;
    border: 1px solid var(--post-border);
    background-color: var(--post-bg);
}

.admin-toggle {
    width: auto;
    margin: 20px auto;
    padding: 8px 14px;
    background-color: #333;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 15px;
    display: block;
}

.admin-panel-box {
    max-width: 100%;
    margin: 20px 0;
    padding: 20px;
    background-color: var(--post-bg);
    border: 1px solid var(--post-border);
    border-radius: 5px;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
    color: var(--post-text);
    font-size: 16px;
    text-align: left;
}

.admin-panel-box h2 {
    font-family: "Aileron", serif;
    font-size: 32px;
    margin: 0 0 20px 0;
    text-align: center;
    color: var(--post-text);
}

.admin-panel-box h3 {
    font-family: "Aileron", serif;
    font-size: 24px;
    margin: 0 0 16px 0;
    color: var(--post-text);
}

.add-user-section {
    background-color: var(--input-bg);
    padding: 16px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.divider {
    border: none;
    border-top: 1px solid var(--input-border);
    margin: 20px 0;
}

.users-list-section ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.user-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    margin-bottom: 12px;
    background-color: var(--input-bg);
    border: 1px solid var(--input-border);
    border-radius: 4px;
    font-size: 15px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.user-details {
    font-size: 15px;
}

.badge-admin,
.badge-user {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
}

.badge-admin {
    background-color: #3498db;
    color: white;
}

.badge-user {
    background-color: #95a5a6;
    color: white;
}

.user-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.current-user-label {
    font-style: italic;
    color: var(--post-text);
    opacity: 0.7;
    font-size: 14px;
}

.btn-add {
    width: 100%;
    margin-top: 12px;
    padding: 8px 14px;
    background-color: #333;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 15px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-add:hover:not(:disabled) {
    background-color: #555;
}

.btn-promote {
    padding: 6px 10px;
    background-color: transparent;
    color: #3498db;
    border: 1px solid #3498db;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    width: auto;
    margin: 0;
    transition: all 0.2s ease;
}

.btn-promote:hover {
    background-color: rgba(52, 152, 219, 0.1);
}

.btn-demote {
    padding: 6px 10px;
    background-color: transparent;
    color: #f39c12;
    border: 1px solid #f39c12;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    width: auto;
    margin: 0;
    transition: all 0.2s ease;
}

.btn-demote:hover {
    background-color: rgba(243, 156, 18, 0.1);
}

.btn-delete {
    padding: 6px 10px;
    background-color: transparent;
    color: #e74c3c;
    border: 1px solid #e74c3c;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    width: auto;
    margin: 0;
    transition: all 0.2s ease;
}

.btn-delete:hover {
    background-color: rgba(231, 76, 60, 0.1);
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
