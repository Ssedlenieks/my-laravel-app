<template>
    <div class="post-form">
        <div class="content-wrapper">
            <div v-if="categories.length" class="filter-bar">
                <div class="filter-left">
                    <div class="filter-item">
                        <div class="search-box">
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Search posts..."
                            />
                            <button class="search-btn" @click="searchPosts">Search</button>
                        </div>
                    </div>
                </div>
                <div class="filter-center">
                    <div class="filter-item category-box" v-if="!hideCategories">
                        <label>Select Categories:</label>
                        <div class="categories-checkboxes">
                            <label
                                v-for="c in categories"
                                :key="c.id"
                                class="checkbox-item"
                            >
                                <span class="category-label">{{ c.name }}</span>
                                <input
                                    type="checkbox"
                                    :value="c.id"
                                    v-model="selectedCategories"
                                    @change="fetchPosts"
                                    class="category-checkbox"
                                />
                            </label>
                        </div>
                        <p class="found-count">Found {{ posts.length }} posts</p>
                    </div>
                </div>
                <div class="filter-right">
                    <div class="filter-item sort-box">
                        <label for="sortBy">Sort By:</label>
                        <select v-model="sortBy" @change="fetchPosts">
                            <option value="reactions">Reactions</option>
                            <option value="date">Date</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="post-container">
                <div v-if="posts.length" class="post-row">
                    <div
                        v-for="post in posts"
                        :key="post.id"
                        class="post"
                    >
                        <h3 v-html="highlight(post.title)"></h3>
                        <h4>{{ post.categories.map(c => c.name).join(', ') }}</h4>
                        <img
                            v-if="post.image_url"
                            :src="post.image_url"
                            class="post-uploaded-image"
                        />
                        <div class="user-name">
                            <div class="author-info">
                                <img
                                    v-if="post.user?.profile_photo_url"
                                    :src="post.user.profile_photo_url"
                                    alt="Author photo"
                                    class="profile-photo-post"
                                />
                                <p class="author-text">
                                    <strong>{{ post.user?.username || 'Unknown' }}</strong>
                                </p>
                            </div>
                            <p class="post-date">{{ post.created_date }}</p>
                        </div>
                        <p v-html="highlight(truncateContent(post.content))"></p>
                        <div class="reaction-icons">
                            <button @click="addReaction('like', post.id)">👍</button>
                            <span>{{ post.reactionCounts?.like }}</span>
                            <button @click="addReaction('dislike', post.id)">👎</button>
                            <span>{{ post.reactionCounts?.dislike }}</span>
                            <button @click="addReaction('heart', post.id)">❤️</button>
                            <span>{{ post.reactionCounts?.heart }}</span>
                        </div>
                        <div class="post-buttons">
                            <button
                                v-if="(post.user && post.user.id === currentUserId) || (isAdmin && !post.user.is_admin)"
                                @click="deletePost(post.id)"
                                class="btn-delete"
                            >
                                Delete
                            </button>

                            <button
                                v-if="(post.user && post.user.id === currentUserId) || (isAdmin && !post.user.is_admin)"
                                @click="startEdit(post)"
                                class="btn-edit"
                            >
                                Edit
                            </button>
                            <button
                                class="open-post-btn"
                                @click="openModal(post)"
                            >
                                Open Post
                            </button>
                        </div>
                        <div v-if="post.id === selectedPost?.id">
                            <div
                                v-for="comment in selectedPost.comments"
                                :key="comment.id"
                                class="comment"
                            >
                                <p>
                                    <strong>{{ comment.user.username }}:</strong>
                                    {{ comment.content }}
                                </p>
                            </div>
                            <div v-if="isLoggedIn">
                <textarea
                    v-model="newCommentContent"
                    placeholder="Add a comment..."
                ></textarea>
                                <button @click="addComment(post.id)">Post Comment</button>
                            </div>
                            <p v-else>Log in to comment</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isLoggedIn" class="create-post-wrapper">
            <div class="create-post-container">
                <h2 class="create-title">Write Your Thoughts</h2>
                <form @submit.prevent="submitPost" enctype="multipart/form-data">
                    <div>
                        <label>Post Title:</label>
                        <input type="text" v-model="post.title" required />
                    </div>

                    <div class="category-box create-category-box">
                        <label>Select Categories:</label>
                        <div class="categories-checkboxes create-categories-checkboxes">
                            <label
                                v-for="c in categories"
                                :key="c.id"
                                class="checkbox-item"
                            >
                                <input
                                    type="checkbox"
                                    :value="c.id"
                                    v-model="post.category_ids"
                                    class="category-checkbox"
                                />
                                <span class="category-label">{{ c.name }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label>Content:</label>
                        <textarea v-model="post.content" required></textarea>
                        <label>Photo:</label>
                        <input
                            type="file"
                            accept="image/*"
                            @change="handleImageUpload"
                            ref="fileInput"
                        />
                        <button type="submit">Publish Post</button>
                    </div>
                </form>
                <p v-if="message" class="success-message">{{ message }}</p>
            </div>
        </div>

        <div v-if="isModalOpen" class="modal" @click="closeModal">
            <div class="modal-content full-post-container" @click.stop>
                <div class="post-full">
                    <div class="post-full-header">
                        <h2>{{ selectedPost.user.username }}'s Post</h2>
                        <button class="close-btn" @click="closeModal">×</button>
                    </div>
                    <h3>{{ selectedPost.title }}</h3>
                    <div class="post-full-image-wrapper" v-if="selectedPost.image_url">
                        <img :src="selectedPost.image_url" alt="Post Image" class="post-full-image" />
                    </div>
                    <p class="post-full-content" v-html="selectedPost.content"></p>
                </div>
                <div class="comments-sidebar">
                    <h3>Comments</h3>
                    <div v-if="selectedPost.comments?.length" class="comments-list">
                        <div
                            v-for="cm in selectedPost.comments"
                            :key="cm.id"
                            class="comment-item"
                        >
                            <div class="comment-header">
                                <strong>{{ cm.user.username }}</strong>
                                <span class="comment-date">{{ cm.created_date }}</span>
                            </div>
                            <div
                                v-if="isLoggedIn && (
                                    currentUserId === cm.user.id ||
                                    (isAdmin && !cm.user.is_admin)
                                )"
                                class="comments-delete">
                                <span @click="deleteComment(cm.id, selectedPost.id)" class="delete">x</span>
                            </div>
                            <p class="comment-content">{{ cm.content }}</p>
                            <hr />
                        </div>
                    </div>
                    <div v-else class="no-comments">
                        <p>No comments yet</p>
                    </div>
                    <div class="add-comment" v-if="isLoggedIn">
                    <textarea
                        v-model="newCommentContent"
                        placeholder="Add a comment"
                    ></textarea>
                        <button @click="addComment(selectedPost.id)">Send</button>
                    </div>
                    <p v-else class="login-note">Log in to add a comment</p>
                </div>
            </div>
        </div>

        <div v-if="editingPost" class="modal" @click="cancelEdit">
            <div class="modal-content full-post-container" @click.stop>
                <div class="edit-form-col">
                    <h3>Edit Post</h3>
                    <label>Post Title:</label>
                    <input v-model="editingPost.title" placeholder="Title" />
                    <label>Content:</label>
                    <textarea v-model="editingPost.content" placeholder="Content"></textarea>
                    <label>Select Categories:</label>
                    <div class="categories-checkboxes">
                        <label
                            v-for="c in categories"
                            :key="c.id"
                            class="checkbox-item"
                        >
                            <span class="category-label">{{ c.name }}</span>
                            <input
                                type="checkbox"
                                :value="c.id"
                                v-model="editingPost.category_ids"
                                class="category-checkbox"
                            />
                        </label>
                    </div>
                    <label>Change Photo:</label>
                    <input type="file" accept="image/*" @change="handleImageUpload" />
                    <div class="buttons-row">
                        <button @click="updatePost">Save</button>
                        <button @click="cancelEdit">Cancel</button>
                    </div>
                </div>
                <div class="edit-preview-col">
                    <h3>Preview</h3>
                    <h4>{{ editingPost.title }}</h4>
                    <p>{{ editingPost.content }}</p>
                    <img
                        v-if="editingPost.imagePreviewUrl"
                        :src="editingPost.imagePreviewUrl"
                        alt="Preview"
                        style="max-width:100%; margin-top:10px;"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "PostForm",
    props: {
        hideCategories: Boolean,
        filterCategoryName: {
            type: String,
            default: ""
        }
    },
    data() {
        return {
            post: { title: "", content: "", category_ids: [] },
            selectedCategories: [],
            searchQuery: "",
            sortBy: "",
            message: "",
            posts: [],
            categories: [],
            isLoggedIn: false,
            isAdmin: false,
            currentUserId: null,
            isModalOpen: false,
            selectedPost: null,
            newCommentContent: "",
            imageFile: null,
            editingPost: null,
            searchActive: false
        };
    },
    methods: {
        highlight(text) {
            if (!this.searchActive || !this.searchQuery) return text;
            const esc = this.searchQuery.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
            return text.replace(new RegExp(`(${esc})`, "gi"), "<mark>$1</mark>");
        },
        truncateContent(text) {
            if (!text) return "";
            if (text.length <= 100) return text;
            let segment = text.slice(0, 100);
            const lastSpace = segment.lastIndexOf(" ");
            if (lastSpace > 0) segment = segment.slice(0, lastSpace);
            return segment + "...";
        },
        handleImageUpload(e) {
            this.imageFile = e.target.files[0];
            if (this.editingPost && this.imageFile) {
                const reader = new FileReader();
                reader.onload = () => {
                    this.$set(this.editingPost, "imagePreviewUrl", reader.result);
                };
                reader.readAsDataURL(this.imageFile);
            }
        },
        async submitPost() {
            const fd = new FormData();
            fd.append("title", this.post.title);
            fd.append("content", this.post.content);
            this.post.category_ids.forEach(id => fd.append("category_ids[]", id));
            if (this.imageFile) fd.append("image", this.imageFile);
            const res = await axios.post("/posts", fd, {
                headers: { "Content-Type": "multipart/form-data" }
            });
            this.message = res.data.message;
            this.resetSearch();
            await this.fetchPosts();
            this.post.title = "";
            this.post.content = "";
            this.post.category_ids = [];
            this.imageFile = null;
            this.$refs.fileInput.value = "";
        },
        async fetchPosts() {
            try {
                let url = "/posts";
                const params = [];
                if (this.selectedCategories.length) {
                    this.selectedCategories.forEach(id => params.push(`category_ids[]=${id}`));
                }
                if (this.sortBy === "date") params.push(`sort=date`);
                if (params.length) url += "?" + params.join("&");
                const response = await axios.get(url);
                let fetched = response.data;
                for (let post of fetched) {
                    const reactionRes = await axios.get(`/posts/${post.id}/reactions`);
                    post.reactionCounts = reactionRes.data || { like: 0, dislike: 0, heart: 0 };
                }
                if (this.sortBy === "reactions") {
                    fetched.sort((a, b) => {
                        const sum = p => (p.reactionCounts.like || 0) + (p.reactionCounts.dislike || 0) + (p.reactionCounts.heart || 0);
                        return sum(b) - sum(a);
                    });
                }
                if (this.searchActive && this.searchQuery.trim()) {
                    const q = this.searchQuery.trim().toLowerCase();
                    fetched = fetched.filter(p => p.title.toLowerCase().includes(q) || p.content.toLowerCase().includes(q));
                }
                this.posts = fetched;
            } catch (e) {
                console.error(e);
            }
        },
        async checkLoginStatus() {
            try {
                const res = await axios.get("/user");
                this.isLoggedIn = !!res.data;
                if (this.isLoggedIn) {
                    this.currentUserId = res.data.id;
                    this.isAdmin = res.data.is_admin;
                }
            } catch {}
        },
        async deletePost(postId) {
            try {
                await axios.delete(`/posts/${postId}`);
                this.resetSearch();
                await this.fetchPosts();
            } catch (e) {
                console.error(e);
            }
        },
        openModal(post) {
            this.selectedPost = post;
            this.isModalOpen = true;
            this.fetchComments(post.id);
        },
        closeModal() {
            this.isModalOpen = false;
            this.selectedPost = null;
        },
        async fetchComments(postId) {
            try {
                const res = await axios.get(`/posts/${postId}/comments`);
                this.selectedPost.comments = res.data;
            } catch {}
        },
        async addComment(postId) {
            if (!this.newCommentContent) return;
            try {
                await axios.post(`/posts/${postId}/comments`, { content: this.newCommentContent });
                this.newCommentContent = "";
                this.fetchComments(postId);
            } catch {}
        },
        async deleteComment(commentId, postId) {
            try {
                await axios.delete(`/comments/${commentId}`);
                await this.fetchComments(postId);
            } catch (e) {
                console.error("Failed to delete comment:", e);
                alert("Error deleting comment.");
            }
        },
        async addReaction(type, postId) {
            if (!this.isLoggedIn) {
                alert("Log in to add reaction");
                return;
            }
            try {
                await axios.post(`/posts/${postId}/reactions`, { type });
                this.resetSearch();
                await this.fetchPosts();
            } catch (e) {
                console.error(e);
            }
        },
        async fetchCategories() {
            try {
                const res = await axios.get("/categories");
                this.categories = res.data;
            } catch {}
        },
        startEdit(post) {
            this.editingPost = {
                id: post.id,
                title: post.title,
                content: post.content,
                category_ids: post.categories.map(c => c.id),
                imagePreviewUrl: post.image_url || null
            };
            this.imageFile = null;
        },
        cancelEdit() {
            this.editingPost = null;
            this.imageFile = null;
        },
        async updatePost() {
            try {
                const fd = new FormData();
                fd.append("title", this.editingPost.title);
                fd.append("content", this.editingPost.content);
                this.editingPost.category_ids.forEach(id => fd.append("category_ids[]", id));
                if (this.imageFile) fd.append("image", this.imageFile);
                // override method for multipart PUT
                fd.append("_method", "PUT");

                await axios.post(
                    `/posts/${this.editingPost.id}`,
                    fd,
                    { headers: { "Content-Type": "multipart/form-data" } }
                );

                this.cancelEdit();
                this.resetSearch();
                await this.fetchPosts();
            } catch (e) {
                console.error("Update failed:", e.response || e);
                alert("Sorry, something went wrong while saving your changes.");
            }
        },
        searchPosts() {
            this.searchActive = true;
            this.fetchPosts();
        },
        resetSearch() {
            this.searchActive = false;
        }
    },
    async mounted() {
        await this.checkLoginStatus();
        await this.fetchCategories();

        if (this.filterCategoryName) {
            const name = this.filterCategoryName.toLowerCase();
            const match = this.categories.find(c => c.name.toLowerCase() === name);
            if (match) {
                this.selectedCategories = [match.id];
            }
        }

        await this.fetchPosts();
    }
};
</script>

<style scoped>
.content-wrapper {
    max-width: 100%;
    margin: 0 auto;
    padding: 0 16px;
    box-sizing: border-box;
}

.filter-bar {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.filter-left,
.filter-center,
.filter-right {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.filter-left {
    justify-content: flex-start;
}
.filter-center {
    align-items: center;
}
.filter-right {
    align-items: flex-end;
}
.filter-item {
    background-color: var(--post-bg);
    border: 1px solid var(--post-border);
    border-radius: 5px;
    padding: 12px;
    box-shadow: none;
    display: flex;
    flex-direction: column;
    color: var(--post-text);
    transition: background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}

.search-box input {
    padding: 8px;
    border: none;
    border-bottom: 2px solid var(--input-border);
    background-color: var(--input-bg);
    color: var(--post-text);
    font-size: 14px;
    width: 200px;
    transition: background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}
.search-box input:focus {
    outline: none;
    border-bottom-color: var(--input-border);
}
.search-btn {
    padding: 4px 8px;
    border: none;
    background-color: var(--input-bg);
    color: var(--post-text);
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    margin-top: 8px;
    transition: filter 0.2s ease;
}
.search-btn:hover {
    filter: brightness(1.1);
}

.category-box {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.category-box > label {
    font-family: "Perfectly Vintages";
    font-size: 16px;
    margin-bottom: 6px;
    color: var(--post-text);
}
.categories-checkboxes {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    font-family: "Perfectly Vintages";
    font-size: 14px;
}
.checkbox-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 2px;
    background-color: transparent;
}
.category-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
}
.category-label {
    color: var(--post-text);
    font-size: 14px;
}
.found-count {
    margin-top: 8px;
    font-size: 14px;
    color: var(--post-text);
}

.sort-box {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}
.sort-box label {
    font-size: 14px;
    margin-bottom: 4px;
    color: var(--post-text);
}
.sort-box select {
    padding: 6px;
    border: none;
    border-bottom: 2px solid var(--input-border);
    background-color: var(--input-bg);
    color: var(--post-text);
    font-size: 14px;
    border-radius: 4px;
    width: 150px;
    transition: background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}
.sort-box select:focus {
    outline: none;
    border-bottom-color: var(--input-border);
}

.post-container {
    max-width: 100%;
    margin: 0 auto;
    box-sizing: border-box;
}
.post-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    justify-content: center;
}
@media (max-width: 960px) {
    .post-row {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 600px) {
    .post-row {
        grid-template-columns: 1fr;
    }
}

.post {
    padding: 16px;
    background-color: var(--post-bg);
    border: 1px solid var(--post-border);
    border-radius: 5px;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
    font-size: 14px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    color: var(--post-text);
    transition: background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}
.post h3 {
    font-family: "Akira Expanded";
    font-size: 18px;
    margin-bottom: 8px;
    line-height: 1.2;
    color: var(--post-text);
}
.post h4 {
    font-size: 12px;
    margin-bottom: 8px;
    color: var(--post-text);
}
.post-uploaded-image {
    width: 100%;
    max-height: 180px;
    object-fit: cover;
    margin: 8px 0;
    border-radius: 4px;
}
.user-name {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    gap: 12px;
}
.author-info {
    display: flex;
    align-items: center;
    gap: 6px;
}
.profile-photo-post {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
}
.author-text {
    font-size: 12px;
    color: var(--post-text);
}
.post-date {
    font-size: 11px;
    color: var(--post-text);
}
.post p {
    font-size: 13px;
    line-height: 1.4;
    margin: 8px 0;
    flex-grow: 1;
    color: var(--post-text);
}

.reaction-icons {
    display: flex;
    gap: 8px;
    margin: 8px 0;
}
.reaction-icons button {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: var(--post-text);
}
.post-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.btn-delete {
    background-color: transparent;
    border: 1px solid #e74c3c;
    color: #e74c3c;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
}
.btn-delete:hover {
    background-color: rgba(231, 76, 60, 0.1);
}
.btn-edit {
    background-color: transparent;
    border: 1px solid #3498db;
    color: #3498db;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
}
.btn-edit:hover {
    background-color: rgba(52, 152, 219, 0.1);
}
.open-post-btn {
    background-color: #333;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 6px 12px;
    font-size: 13px;
    cursor: pointer;
    transition: background-color 0.2s;
}
.open-post-btn:hover {
    background-color: #555;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}
.modal-content.full-post-container {
    background-color: var(--post-bg);
    color: var(--post-text);
    width: 100vw;
    height: 100vh;
    display: flex;
    flex-direction: row;
    overflow: hidden;
    position: relative;
    border-radius: 0;
    padding: 0;
    box-sizing: border-box;
}
.post-full {
    flex: 2;
    padding: 20px;
    overflow-y: auto;
    box-sizing: border-box;
}
.post-full-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.post-full-header h2 {
    font-size: 24px;
    margin: 0;
    color: var(--post-text);
}
.close-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    line-height: 1;
    cursor: pointer;
    padding: 0;
    color: var(--post-text);
}
.post-full h3 {
    font-size: 20px;
    margin-bottom: 12px;
    color: var(--post-text);
}
.post-full-image-wrapper {
    width: 100%;
    max-height: none;
}
.post-full-image {
    width: 100%;
    height: auto;
    object-fit: contain;
    filter: none;
}
.post-full-content {
    font-size: 16px;
    line-height: 1.5;
    white-space: pre-wrap;
    margin-top: 12px;
    color: var(--post-text);
}

.comments-sidebar {
    flex: 1;
    border-left: 1px solid var(--post-border);
    background-color: var(--post-bg);
    color: var(--post-text);
    padding: 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}
.comments-sidebar h3 {
    font-size: 18px;
    margin-bottom: 12px;
    text-align: center;
    color: var(--post-text);
}
.comments-list {
    flex: 1;
    overflow-y: auto;
}
.comment-item {
    margin-bottom: 12px;
}
.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 6px;
}
.comment-header strong {
    font-size: 14px;
    color: var(--post-text);
}
.comment-date {
    font-size: 12px;
    color: var(--post-text);
}
.comment-content {
    font-size: 13px;
    line-height: 1.4;
    margin: 0;
    color: var(--post-text);
}
.no-comments {
    font-size: 13px;
    color: var(--post-text);
    text-align: center;
    margin-top: 20px;
}
.add-comment {
    margin-top: 20px;
}
.add-comment textarea {
    width: 100%;
    height: 80px;
    resize: vertical;
    padding: 8px;
    background-color: var(--input-bg);
    color: var(--post-text);
    border: 1px solid var(--input-border);
    border-radius: 4px;
    font-size: 13px;
    transition: background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}
.add-comment button {
    margin-top: 10px;
    width: 100%;
    padding: 8px;
    background-color: var(--input-bg);
    color: var(--post-text);
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: filter 0.2s ease;
}
.add-comment button:hover {
    filter: brightness(1.1);
}
.login-note {
    font-size: 13px;
    color: var(--post-text);
    margin-top: 20px;
    text-align: center;
}

.create-post-wrapper {
    margin-top: 40px;
    margin-bottom: 40px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
    padding: 20px;
    background: transparent;
    box-shadow: none;
    border-radius: 8px;
}
.create-post-container {
    font-family: "Perfectly Vintages";
}
.create-post-container input,
.create-post-container textarea {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid var(--input-border);
    border-radius: 4px;
    font-size: 14px;
    font-family: "Perfectly Vintages";
    background-color: var(--input-bg);
    color: var(--post-text);
    transition: background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}
.create-post-container button {
    width: 100%;
    padding: 12px;
    border: none;
    background-color: var(--input-bg);
    color: var(--post-text);
    border-radius: 5px;
    margin-top: 20px;
    cursor: pointer;
    font-size: 16px;
    font-family: "Perfectly Vintages";
    transition: filter 0.2s ease;
}
.create-post-container button:hover {
    filter: brightness(1.1);
}
.create-post-container h2 {
    font-family: "Perfectly Vintages";
    font-size: 35px;
    text-align: center;
    margin-bottom: 20px;
    color: var(--post-text);
}
.success-message {
    color: green;
    margin-top: 10px;
}

.create-category-box {
    background: transparent !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin-bottom: 12px;
}
.create-category-box > label {
    font-family: "Perfectly Vintages";
    font-size: 16px;
    margin-bottom: 4px;
    color: var(--post-text);
}
.create-categories-checkboxes {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-family: "Perfectly Vintages";
    font-size: 14px;
}
.create-categories-checkboxes .checkbox-item {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 2px;
    background-color: transparent;
}
.create-categories-checkboxes .category-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
    flex-shrink: 0;
    margin-right: 2px;
}
.create-categories-checkboxes .category-label {
    color: var(--post-text);
    font-size: 14px;
    margin-left: 0;
    white-space: nowrap;
}

.edit-form-col {
    flex: 1;
    padding: 20px;
    box-sizing: border-box;
    overflow-y: auto;
}
.edit-form-col h3 {
    margin-bottom: 12px;
    color: var(--post-text);
}
.edit-form-col label {
    display: block;
    font-weight: bold;
    margin-top: 10px;
    font-size: 14px;
    color: var(--post-text);
}
.edit-form-col input,
.edit-form-col textarea {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid var(--input-border);
    border-radius: 4px;
    font-size: 14px;
    font-family: "Perfectly Vintages";
    background-color: var(--input-bg);
    color: var(--post-text);
    transition: background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}
.buttons-row {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}
.buttons-row button {
    flex: 1;
    padding: 10px;
    border: none;
    background-color: var(--input-bg);
    color: var(--post-text);
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-family: "Perfectly Vintages";
    transition: filter 0.2s ease;
}
.buttons-row button:hover {
    filter: brightness(1.1);
}
.edit-preview-col {
    flex: 1;
    background-color: var(--input-bg);
    padding: 20px;
    box-sizing: border-box;
    overflow-y: auto;
}
.edit-preview-col h3,
.edit-preview-col h4,
.edit-preview-col p {
    color: var(--post-text);
    margin-bottom: 12px;
    font-family: "Perfectly Vintages";
}

.comments-delete {
    text-align: right;
    margin-top: -10px;
    margin-bottom: 10px;
}

.delete {
    cursor: pointer;
    font-size: 18px;
    color: grey;
    transition: color 0.2s ease;
}

.delete:hover {
    color: grey
}

</style>
