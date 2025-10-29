<template>
  <div class="post-form">
    <div class="content-wrapper">
      <!-- Meklēšana un filtri -->
      <SearchFilters
        v-if="categories.length"
        :categories="categories"
        :hideCategories="hideCategories"
        :foundCount="posts.length"
        :initialSelectedCategories="selectedCategories"
        @search="handleSearch"
        @categories-changed="handleCategoriesChanged"
        @sort-changed="handleSortChanged"
      />

      <!-- Publikācijas izkārtojums -->
      <div class="post-container">
        <div class="posts-layout">
          <div class="posts-grid-column" :class="{ 'with-expanded': expandedPostId }">
            <div v-if="posts.length" class="post-row">
              <PostCard
                v-for="post in posts"
                :key="post.id"
                :post="post"
                :isActive="expandedPostId === post.id"
                :searchQuery="searchQuery"
                :searchActive="searchActive"
                :currentUserId="currentUserId"
                :isAdmin="isAdmin"
                @expand-post="togglePostExpand"
                @add-reaction="addReaction"
                @delete-post="deletePost"
                @edit-post="startEdit"
              />
            </div>
          </div>

          <!-- Pilnīgi atvērtas publikācija -->
          <div v-if="expandedPostId" class="expanded-post-column">
            <div class="expanded-post-card">
              <div class="expanded-header">
                <h3>Publikācijas priekšskatījums</h3>
                <button class="close-expanded" @click="closeExpandedPost">×</button>
              </div>

              <div class="expanded-content">
                <div v-for="post in posts" :key="post.id">
                  <div v-if="post.id === expandedPostId" class="expanded-post-content">
                    <div class="full-content-section">
                      <h3 class="expanded-post-title">{{ post.title }}</h3>
                      <div class="post-meta">
                        <div class="author-info">
                          <img
                            v-if="post.user?.profile_photo_url"
                            :src="post.user.profile_photo_url"
                            alt="Author photo"
                            class="profile-photo-post"
                          />
                          <span class="author-name">{{ post.user?.username || 'Unknown' }}</span>
                        </div>
                        <span class="post-date">{{ post.created_date }}</span>
                      </div>

                      <div v-if="post.image_url" class="expanded-image-container">
                        <img :src="post.image_url" class="expanded-post-image" />
                      </div>

                      <!-- Vārdnīcas komponenta pievienošana -->
                      <MeaningLookup
                        :content="post.content"
                        :postId="post.id"
                        @mode-changed="handleMeaningModeChanged"
                        @word-selected="handleWordSelected"
                      />
                    </div>

                    <!-- Komentāru komponenta pievienošana -->
                    <CommentsSection
                      :comments="post.comments || []"
                      :isLoggedIn="isLoggedIn"
                      :currentUserId="currentUserId"
                      :isAdmin="isAdmin"
                      @add-comment="handleAddComment(post.id, $event)"
                      @delete-comment="handleDeleteComment($event, post.id)"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Publikācijas izveides sadaļa -->
      <div v-if="isLoggedIn" class="create-post-wrapper">
        <div class="create-post-container">
          <h2 class="create-title">Rakstiet savas domas</h2>
          <form @submit.prevent="submitPost" enctype="multipart/form-data">
            <div>
              <label>Ziņas nosaukums:</label>
              <input type="text" v-model="post.title" required />
            </div>

            <div class="category-box create-category-box">
              <label>Izvēlēties kategorijas:</label>
              <div class="categories-checkboxes create-categories-checkboxes">
                <label v-for="c in categories" :key="c.id" class="checkbox-item">
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
              <label>Saturs:</label>
              <textarea v-model="post.content" required></textarea>
              <label>Attēls:</label>
              <input type="file" accept="image/*" @change="handleImageUpload" ref="fileInput" />
              <button type="submit">Publicēt ziņu</button>
            </div>
          </form>
          <p v-if="message" class="success-message">{{ message }}</p>
        </div>
      </div>

      <!-- Rediģēšanas modālais logs -->
      <div v-if="editingPost" class="modal" @click="cancelEdit">
        <div class="modal-content full-post-container" @click.stop>
          <div class="edit-form-col">
            <h3>Rediģēt ziņu</h3>
            <label>Ziņas nosaukums:</label>
            <input v-model="editingPost.title" placeholder="Nosaukums" />
            <label>Saturs:</label>
            <textarea v-model="editingPost.content" placeholder="Saturs"></textarea>
            <label>Izvēlēties kategorijas:</label>
            <div class="categories-checkboxes">
              <label v-for="c in categories" :key="c.id" class="checkbox-item">
                <span class="category-label">{{ c.name }}</span>
                <input
                  type="checkbox"
                  :value="c.id"
                  v-model="editingPost.category_ids"
                  class="category-checkbox"
                />
              </label>
            </div>
            <label>Mainīt:</label>
            <input type="file" accept="image/*" @change="handleImageUpload" />
            <div class="buttons-row">
              <button @click="updatePost">Saglabāt</button>
              <button @click="cancelEdit">Atcelt</button>
            </div>
          </div>
          <div class="edit-preview-col">
            <h3>Priekšskatījums</h3>
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
  </div>
</template>

<script>
import axios from "axios";
import PostCard from "./PostCard.vue";
import SearchFilters from "./SearchFilters.vue";
import CommentsSection from "./CommentsSection.vue";
import MeaningLookup from "./MeaningLookup.vue";

export default {
  name: "PostForm",
  components: {
    PostCard,
    SearchFilters,
    CommentsSection,
    MeaningLookup
  },
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
      lastFetchToken: 0,
      isLoadingPosts: false,
      searchQuery: "",
      sortBy: "reactions",
      message: "",
      posts: [],
      categories: [],
      isLoggedIn: false,
      isAdmin: false,
      currentUserId: null,
      imageFile: null,
      editingPost: null,
      searchActive: false,
      expandedPostId: null,
      postMeaningModes: {},
      selectedWord: "",
      currentLookupPostId: null,
    };
  },
  methods: {
// Metodes meklēšanai un filtrēšanai
    handleSearch(query) {
      this.searchQuery = query;
      this.searchActive = true;
      this.fetchPosts();
    },

    handleCategoriesChanged(categories) {
      this.selectedCategories = categories;
      this.fetchPosts();
    },

    handleSortChanged(sortBy) {
      console.log('PostForm: Sort changed to:', sortBy);
      this.sortBy = sortBy;
      this.fetchPosts();
    },

    resetSearch() {
      this.searchActive = false;
    },

    highlight(text) {
      if (!this.searchActive || !this.searchQuery) return text;
      const esc = this.searchQuery.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
      return text.replace(new RegExp(`(${esc})`, "gi"), "<mark>$1</mark>");
    },

    // Publikāciju paplašināšanas metodes
    async togglePostExpand(postId) {
      if (this.expandedPostId === postId) {
        this.closeExpandedPost();
      } else {
        this.expandedPostId = postId;
        this.selectedWord = "";
        await this.fetchComments(postId);

        this.$nextTick(() => {
          if (window.innerWidth <= 768) {
            setTimeout(() => {
              const expandedEl = document.querySelector('.expanded-post-column');
              if (expandedEl) {
                const rect = expandedEl.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                const targetPosition = rect.top + scrollTop - 10;

                window.scrollTo({
                  top: targetPosition,
                  behavior: 'smooth'
                });
              }
            }, 100);
          }
        });
      }
    },

    closeExpandedPost() {
      this.expandedPostId = null;
      this.selectedWord = "";
      this.currentLookupPostId = null;
    },

    // Vārdnīcas metodes
    handleMeaningModeChanged(data) {
      if (data.postId) {
        this.postMeaningModes[data.postId] = data.mode;
      }
    },

    handleWordClick(token, postId) {
      const word = token.trim();
      if (this.isWord(word) && this.postMeaningModes[postId]) {
        this.selectedWord = word;
        this.currentLookupPostId = postId;
      }
    },

    tokenizeContent(text) {
      if (!text) return [];
      const tokens = [];
      const regex = /(\S+|\s+)/g;
      let match;
      while ((match = regex.exec(text)) !== null) {
        tokens.push(match[0]);
      }
      return tokens;
    },

    isWord(token) {
      return /\w/.test(token) && token.trim().length > 0;
    },

    cleanWord(word) {
      let clean = word.replace(/[.,!?;:\"()]/g, '');
      clean = clean.replace(/[""„''']/g, '');
      return clean.trim();
    },

    handleWordSelected(data) {
      this.selectedWord = data.word;
      this.currentLookupPostId = data.postId;
    },

    // Komentāru metodes
    async fetchComments(postId) {
      try {
        const res = await axios.get(`/posts/${postId}/comments`);
        const post = this.posts.find(p => p.id === postId);
        if (post) {
          post.comments = res.data;
        }
      } catch (error) {
        console.error("Failed to fetch comments:", error);
      }
    },

    async handleAddComment(postId, content) {
      try {
        await axios.post(`/posts/${postId}/comments`, { content });
        await this.fetchComments(postId);
      } catch (error) {
        console.error("Failed to add comment:", error);
      }
    },

    async handleDeleteComment(commentId, postId) {
      try {
        await axios.delete(`/comments/${commentId}`);
        await this.fetchComments(postId);
      } catch (error) {
        console.error("Failed to delete comment:", error);
      }
    },

    // Publikāciju metodes
    async fetchPosts() {
      try {
        const token = ++this.lastFetchToken;
        this.isLoadingPosts = true;

        let url = "/posts";
        const params = [];
        if (this.selectedCategories.length) {
          this.selectedCategories.forEach(id => params.push(`category_ids[]=${id}`));
        }
        if (this.sortBy === "date") params.push(`sort=date`);
        if (this.sortBy === "reactions") params.push(`sort=reactions`);
        if (params.length) url += "?" + params.join("&");

        console.log('PostForm: Fetching posts with URL:', url, 'sortBy:', this.sortBy);

        const response = await axios.get(url);
        let fetched = response.data;

        console.log('PostForm: Received posts:', fetched.length, 'posts');
        if (fetched.length > 0) {
          console.log('PostForm: First few posts reaction counts:',
            fetched.slice(0, 3).map(p => ({
              id: p.id,
              title: p.title.substring(0, 30),
              reactions: p.reactionCounts,
              total: (p.reactionCounts?.like || 0) + (p.reactionCounts?.dislike || 0) + (p.reactionCounts?.heart || 0)
            }))
          );
        }

        // Meklēšanas filtrs
        if (this.searchActive && this.searchQuery.trim()) {
          const q = this.searchQuery.trim().toLowerCase();
          fetched = fetched.filter(p =>
            p.title.toLowerCase().includes(q) ||
            p.content.toLowerCase().includes(q)
          );
          console.log('PostForm: Applied search filter, filtered to:', fetched.length, 'posts');
        }

        if (this.lastFetchToken !== token) return;

        this.posts = fetched;
        this.isLoadingPosts = false;
      } catch (e) {
        this.isLoadingPosts = false;
        console.error(e);
      }
    },

    async deletePost(postId) {
      if (!confirm('Vai tiešām vēlaties dzēst šo publikāciju?')) {
        return;
      }
      try {
        await axios.delete(`/posts/${postId}`);
        this.resetSearch();
        await this.fetchPosts();
        if (this.expandedPostId === postId) {
          this.closeExpandedPost();
        }
      } catch (e) {
        console.error(e);
      }
    },

    async addReaction(type, postId) {
      if (!this.isLoggedIn) {
        alert("Lai reaģētu uz publikāciju, lūdzu, pierakstieties.");
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

    // Autentifikācijas metodes
    async checkLoginStatus() {
      try {
        const res = await axios.get("/user");
        this.isLoggedIn = !!res.data;
        if (this.isLoggedIn) {
          this.currentUserId = res.data.id;
          this.isAdmin = !!res.data.is_admin; // Convert to boolean
        }
      } catch {}
    },

    // Kategoriju metodes
    async fetchCategories() {
      try {
        const res = await axios.get("/categories");
        this.categories = res.data;
      } catch {}
    },

    // Publikāciju izveides/rediģēšanas metodes
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
        fd.append("_method", "PUT");

        await axios.post(`/posts/${this.editingPost.id}`, fd, {
          headers: { "Content-Type": "multipart/form-data" }
        });

        this.cancelEdit();
        this.resetSearch();
        await this.fetchPosts();
      } catch (e) {
        console.error("Update failed:", e.response || e);
        alert("Sorry, something went wrong while saving your changes.");
      }
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
.post-form {
  --post-bg: #ffffff;
  --post-text: #2c3e50;
  --post-border: #e0e0e0;
  --input-bg: #ffffff;
  --input-border: #d1d5db;
}

body.dark .post-form,
body.dark-mode .post-form {
  --post-bg: #1f2937;
  --post-text: #d1d5db;
  --post-border: #374151;
  --input-bg: #273449;
  --input-border: #4b5563;
}

body.dark .post-form,
body.dark-mode .post-form,
.dark .post-form {
  --post-bg: #1f2937;
  --post-text: #d1d5db;
  --post-border: #374151;
  --input-bg: #273449;
  --input-border: #4b5563;
}

.posts-layout {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

.posts-grid-column {
  flex: 1;
  transition: all 0.3s ease;
}

.posts-grid-column.with-expanded {
  flex: 0 0 60%;
  max-width: 60%;
}

.expanded-post-column {
  flex: 0 0 40%;
  position: sticky;
  top: 20px;
  max-height: calc(100vh - 40px);
  overflow-y: auto;
  transition: all 0.3s ease;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.post-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  justify-content: center;
}

.expanded-post-card {
  background: var(--post-bg);
  color: var(--post-text);
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  border: 1px solid var(--post-border);
}

.expanded-header {
  background: var(--input-bg);
  padding: 15px 20px;
  border-bottom: 1px solid var(--post-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.close-expanded {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--post-text);
  opacity: 0.7;
  padding: 0;
  margin-left: auto;
  transition: opacity 0.2s ease;
}

.close-expanded:hover {
  opacity: 1;
}

.expanded-content {
  padding: 20px;
}

.expanded-post-title {
  font-size: 1.5em;
  margin: 0 0 10px 0;
  color: var(--post-text);
}

.post-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--post-border);
}

.author-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-photo-post {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  object-fit: cover;
}

.author-name {
  font-weight: 600;
  color: var(--post-text);
}

.post-date {
  color: var(--post-text);
  opacity: 0.7;
  font-size: 0.9em;
}

.expanded-image-container {
  margin: 15px 0;
}

.expanded-post-image {
  width: 100%;
  max-height: 400px;
  object-fit: cover;
  border-radius: 8px;
}

.post-full-content {
  margin: 15px 0;
  line-height: 1.6;
}

.meaning-mode-text .clickable-word {
  cursor: pointer;
  padding: 2px 4px;
  border-radius: 3px;
  transition: background-color 0.2s;
}

.meaning-mode-text .clickable-word:hover {
  background-color: #e3f2fd;
}

.meaning-mode-text .active-word {
  background-color: #2196f3;
  color: white;
}

body.dark .meaning-mode-text .clickable-word:hover,
body.dark-mode .meaning-mode-text .clickable-word:hover,
.dark .meaning-mode-text .clickable-word:hover {
  background-color: rgba(100, 181, 246, 0.2);
}

.create-post-wrapper {
  margin-top: 30px;
  padding: 30px 20px;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  border-top: 3px solid #2196f3;
}

.create-post-container {
  background: white;
  padding: 35px;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  max-width: 900px;
  margin: 0 auto;
  border: 1px solid #e3f2fd;
}

.create-title {
  color: #1565c0;
  margin-bottom: 25px;
  font-size: 1.8em;
  font-weight: 700;
  text-align: center;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.create-post-container label {
  display: block;
  margin-top: 20px;
  margin-bottom: 8px;
  font-weight: 700;
  color: #1565c0;
  font-size: 1.1em;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.create-post-container input[type="text"],
.create-post-container textarea {
  width: 100%;
  padding: 16px 20px;
  border: 3px solid #e3f2fd;
  border-radius: 12px;
  font-size: 16px;
  font-family: inherit;
  transition: all 0.3s ease;
  background: #fafafa;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
}

.create-post-container input[type="text"]:focus,
.create-post-container textarea:focus {
  outline: none;
  border-color: #2196f3;
  background: white;
  box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1), inset 0 2px 4px rgba(0, 0, 0, 0.06);
  transform: translateY(-1px);
}

.create-post-container textarea {
  min-height: 140px;
  resize: vertical;
  line-height: 1.6;
}

.create-categories-checkboxes {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 15px;
  margin: 15px 0 25px 0;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 12px;
  border: 2px solid #e3f2fd;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 8px;
  transition: all 0.2s ease;
  background: white;
  border: 1px solid #dee2e6;
}

.checkbox-item:hover {
  background: #e3f2fd;
  border-color: #2196f3;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(33, 150, 243, 0.15);
}

.category-checkbox {
  width: 20px;
  height: 20px;
  accent-color: #2196f3;
}

.category-label {
  font-size: 14px;
  font-weight: 600;
  color: #2c3e50;
}

.create-post-container button[type="submit"] {
  background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
  color: white;
  padding: 18px 36px;
  border: none;
  border-radius: 12px;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
  margin-top: 30px;
  width: 100%;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 1px;
  box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
}

.create-post-container button[type="submit"]:hover {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(33, 150, 243, 0.4);
}

.create-post-container button[type="submit"]:active {
  transform: translateY(0);
  box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
}

.create-post-container input[type="file"] {
  width: 100%;
  padding: 16px 20px;
  border: 3px dashed #2196f3;
  border-radius: 12px;
  background: #f8f9fa;
  margin: 10px 0;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 16px;
}

.create-post-container input[type="file"]:hover {
  background: #e3f2fd;
  border-color: #1976d2;
}

.success-message {
  color: #4caf50;
  font-weight: 600;
  margin-top: 20px;
  padding: 15px;
  background: #e8f5e8;
  border: 2px solid #4caf50;
  border-radius: 8px;
  text-align: center;
}

/* Alternative class-based dark mode (matches app.css body.dark) */
body.dark .create-post-wrapper,
body.dark-mode .create-post-wrapper,
.dark .create-post-wrapper {
  background: linear-gradient(135deg, #121622 0%, #1f2937 100%);
  border-top: 3px solid #64b5f6;
}

body.dark .create-post-container,
body.dark-mode .create-post-container,
.dark .create-post-container {
  background: #1f2937;
  border: 1px solid #374151;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

body.dark .create-title,
body.dark-mode .create-title,
.dark .create-title {
  color: #64b5f6;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

body.dark .create-post-container label,
body.dark-mode .create-post-container label,
.dark .create-post-container label {
  color: #64b5f6;
}

body.dark .create-post-container input[type="text"],
body.dark .create-post-container textarea,
body.dark-mode .create-post-container input[type="text"],
body.dark-mode .create-post-container textarea,
.dark .create-post-container input[type="text"],
.dark .create-post-container textarea {
  background: #273449;
  border: 3px solid #374151;
  color: #d1d5db;
}

body.dark .create-post-container input[type="text"]:focus,
body.dark .create-post-container textarea:focus,
body.dark-mode .create-post-container input[type="text"]:focus,
body.dark-mode .create-post-container textarea:focus,
.dark .create-post-container input[type="text"]:focus,
.dark .create-post-container textarea:focus {
  border-color: #64b5f6;
  background: #1f2937;
  box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.2), inset 0 2px 4px rgba(0, 0, 0, 0.2);
}

body.dark .create-categories-checkboxes,
body.dark-mode .create-categories-checkboxes,
.dark .create-categories-checkboxes {
  background: #273449;
  border: 2px solid #374151;
}

body.dark .checkbox-item,
body.dark-mode .checkbox-item,
.dark .checkbox-item {
  background: #1f2937;
  border: 1px solid #4b5563;
  color: #d1d5db;
}

body.dark .checkbox-item:hover,
body.dark-mode .checkbox-item:hover,
.dark .checkbox-item:hover {
  background: #374151;
  border-color: #64b5f6;
  box-shadow: 0 2px 8px rgba(100, 181, 246, 0.25);
}

body.dark .category-label,
body.dark-mode .category-label,
.dark .category-label {
  color: #d1d5db;
}

body.dark .create-post-container input[type="file"],
body.dark-mode .create-post-container input[type="file"],
.dark .create-post-container input[type="file"] {
  background: #273449;
  border: 3px dashed #64b5f6;
  color: #d1d5db;
}

body.dark .create-post-container input[type="file"]:hover,
body.dark-mode .create-post-container input[type="file"]:hover,
.dark .create-post-container input[type="file"]:hover {
  background: #1f2937;
  border-color: #42a5f5;
}

body.dark .success-message,
body.dark-mode .success-message,
.dark .success-message {
  background: #2e7d32;
  border-color: #4caf50;
  color: #ffffff;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: var(--post-bg);
  color: var(--post-text);
  padding: 30px;
  border-radius: 12px;
  max-width: 90%;
  max-height: 90%;
  overflow-y: auto;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  border: 1px solid var(--post-border);
}

.full-post-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  min-width: 800px;
}

.edit-form-col label {
  display: block;
  margin: 15px 0 5px 0;
  font-weight: 600;
  color: var(--post-text);
}

.edit-form-col input,
.edit-form-col textarea {
  width: 100%;
  padding: 10px;
  border: 2px solid var(--post-border);
  border-radius: 6px;
  font-size: 14px;
  background: var(--input-bg);
  color: var(--post-text);
}

.edit-form-col textarea {
  min-height: 100px;
  resize: vertical;
}

.buttons-row {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}

.buttons-row button {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.3s;
}

.buttons-row button:first-child {
  background: #4caf50;
  color: white;
}

.buttons-row button:first-child:hover {
  background: #45a049;
}

.buttons-row button:last-child {
  background: #f44336;
  color: white;
}

.buttons-row button:last-child:hover {
  background: #da190b;
}

.edit-preview-col h3 {
  margin-top: 0;
  color: var(--post-text);
}

.edit-preview-col h4 {
  color: var(--post-text);
  margin-bottom: 10px;
}

.edit-preview-col p {
  line-height: 1.6;
  color: var(--post-text);
}

/* Responsive design */
@media (max-width: 768px) {
  .posts-layout {
    flex-direction: column;
  }

  .expanded-post-column {
    order: -1;
    position: static;
    max-height: none;
    margin: 0 0 20px 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-radius: 12px;
    overflow: hidden;
  }

  .posts-grid-column {
    order: 1;
  }

  .posts-grid-column.with-expanded {
    flex: 1;
    max-width: 100%;
  }

  .expanded-post-card {
    border-radius: 12px;
    margin: 0;
  }

  .expanded-header {
    background: var(--post-bg);
    padding: 15px 20px;
    border-bottom: 1px solid var(--post-border);
    position: sticky;
    top: 0;
    z-index: 10;
  }

  .expanded-header h3 {
    margin: 0;
    font-size: 18px;
    color: var(--post-text);
  }

  .close-expanded {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: var(--button-bg);
    color: var(--button-text);
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .expanded-content {
    max-height: 70vh;
    overflow-y: auto;
    background: var(--post-bg);
  }

  .post-row {
    grid-template-columns: 1fr;
  }

  .full-post-container {
    grid-template-columns: 1fr;
    min-width: auto;
    gap: 20px;
  }

  .posts-grid-column.with-expanded .post-row {
    opacity: 0.6;
    margin-top: 20px;
  }
}
</style>
