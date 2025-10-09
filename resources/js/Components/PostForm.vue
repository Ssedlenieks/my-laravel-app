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
              <button class="search-btn" @click="searchPosts">Meklēt</button>
            </div>
          </div>
        </div>
        <div class="filter-center">
          <div class="filter-item category-box" v-if="!hideCategories">
            <label>IZvēlēties kategorijas:</label>
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
            <p class="found-count">Atrasti {{ posts.length }} ieraksti</p>
          </div>
        </div>
        <div class="filter-right">
          <div class="filter-item sort-box">
            <label for="sortBy">Kārtot pēc:</label>
            <select v-model="sortBy" @change="fetchPosts">
              <option value="reactions">Reakcijas</option>
              <option value="date">Datums</option>
            </select>
          </div>
        </div>
      </div>

      <div class="post-container">
        <!-- Two column layout: posts on left, expanded post on right -->
        <div class="posts-layout">
          <!-- Posts Grid Column -->
          <div class="posts-grid-column" :class="{ 'with-expanded': expandedPostId }">
            <div v-if="posts.length" class="post-row">
              <div
                v-for="post in posts"
                :key="post.id"
                class="post"
                :class="{ 'post-active': expandedPostId === post.id }"
              >
                <div class="post-regular-content">
                  <h3 v-html="highlight(post.title)"></h3>
                  <h4>{{ post.categories.map(c => c.name).join(', ') }}</h4>

                  <!-- Better image sizing -->
                  <div v-if="post.image_url" class="post-image-container">
                    <img
                      :src="post.image_url"
                      class="post-uploaded-image"
                      @click="togglePostExpand(post.id)"
                    />
                  </div>

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

                  <div class="post-content-container">
                    <div>
                      <p v-html="highlight(truncateContent(post.content))"></p>
                    </div>
                  </div>

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
                      @click="togglePostExpand(post.id)"
                    >
                      {{ expandedPostId === post.id ? 'Close' : 'Open Post' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Expanded Post Column -->
          <div v-if="expandedPostId" class="expanded-post-column">
            <div class="expanded-post-card">
              <div class="expanded-header">
                <h3>Post Details</h3>
                <button class="close-expanded" @click="closeExpandedPost">×</button>
              </div>

              <div class="expanded-content">
                <!-- Find the expanded post -->
                <div v-for="post in posts" :key="post.id">
                  <div v-if="post.id === expandedPostId" class="expanded-post-content">
                    <!-- Full content display -->
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

                      <!-- Better image in expanded view -->
                      <div v-if="post.image_url" class="expanded-image-container">
                        <img
                          :src="post.image_url"
                          class="expanded-post-image"
                        />
                      </div>

                      <div class="post-full-content">
                        <template v-if="meaningMode">
                          <p class="meaning-mode-text">
                            <template v-for="(token, index) in tokenizeContent(post.content)" :key="index">
                              <span
                                v-if="isWord(token)"
                                class="clickable-word"
                                @click="selectWordForMeaning(token, post.id)"
                                :class="{ 'active-word': selectedWord === cleanWord(token) && currentLookupPostId === post.id }"
                              >
                                {{ token }}
                              </span>
                              <span v-else class="non-clickable">
                                {{ token }}
                              </span>
                            </template>
                          </p>
                        </template>
                        <template v-else>
                          <p>{{ post.content }}</p>
                        </template>
                      </div>

                      <!-- Meaning mode toggle -->
                      <button @click="toggleMeaningMode" class="btn-meaning">
                        {{ meaningMode ? 'Izslēgt vārdnīcu' : 'Ieslēgt vārdnīcu' }}
                      </button>

                      <!-- Inline meaning section -->
                      <div v-if="meaningMode && selectedWord" class="meaning-section">
                        <div class="meaning-header" @click="toggleMeaningExpanded">
                          <h4>📖 Vārda skaidrojums</h4>
                          <span class="expand-icon" :class="{ 'expanded': meaningExpanded }">▼</span>
                        </div>
                        <transition name="meaning-expand">
                          <div v-if="meaningExpanded" class="meaning-content">
                            <div class="word-definition">
                              <strong class="word-title">{{ selectedWord }}</strong>
                              <p class="definition-text">
                                <span v-if="isLoadingMeaning">Meklē definīciju…</span>
                                <span v-else>{{ wordMeaningData }}</span>
                              </p>
                            </div>
                          </div>
                        </transition>
                      </div>
                    </div>

                    <!-- Comments section -->
                    <div class="comments-section">
                      <h4 class="section-title">Komentāri ({{ post.comments ? post.comments.length : 0 }})</h4>

                      <div v-if="post.comments && post.comments.length" class="comments-list">
                        <div
                          v-for="comment in post.comments"
                          :key="comment.id"
                          class="comment-item"
                        >
                          <div class="comment-header">
                            <div class="comment-author">
                              <img
                                v-if="comment.user?.profile_photo_url"
                                :src="comment.user.profile_photo_url"
                                alt="Comment author"
                                class="comment-profile-photo"
                              />
                              <strong>{{ comment.user?.username || 'Unknown' }}</strong>
                            </div>
                            <span class="comment-date">{{ comment.created_date }}</span>
                          </div>
                          <div class="comment-content-wrapper">
                            <p class="comment-content">{{ comment.content }}</p>
                            <div
                              v-if="isLoggedIn && (
                                currentUserId === comment.user.id ||
                                (isAdmin && !comment.user.is_admin)
                              )"
                              class="comments-delete"
                            >
                              <span @click="deleteComment(comment.id, post.id)" class="delete">×</span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div v-else class="no-comments">
                        <p>No comments yet</p>
                      </div>

                      <!-- Add comment form -->
                      <div class="add-comment" v-if="isLoggedIn">
                        <textarea
                          v-model="newCommentContent"
                          placeholder="Pievienot komentāru..."
                          @keydown.enter.prevent="addComment(post.id)"
                        ></textarea>
                        <button @click="addComment(post.id)">Publicēt komentāru</button>
                      </div>
                      <p v-else class="login-note">Pierakstieties lai pievienotu komentāru</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Create Post Section -->
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
              <label>Saturs:</label>
              <textarea v-model="post.content" required></textarea>
              <label>Foto:</label>
              <input
                type="file"
                accept="image/*"
                @change="handleImageUpload"
                ref="fileInput"
              />
              <button type="submit">Publicēt ziņu</button>
            </div>
          </form>
          <p v-if="message" class="success-message">{{ message }}</p>
        </div>
      </div>

      <!-- Edit Modal -->
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
            <label>Mainīt :</label>
            <input type="file" accept="image/*" @change="handleImageUpload" />
            <div class="buttons-row">
              <button @click="updatePost">Saglabāt</button>
              <button @click="cancelEdit">Atcelt</button>
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
  lastFetchToken: 0,
  isLoadingPosts: false,
      searchQuery: "",
      sortBy: "",
      message: "",
      posts: [],
      categories: [],
      isLoggedIn: false,
      isAdmin: false,
      currentUserId: null,
      imageFile: null,
      editingPost: null,
      searchActive: false,

      // Expanded post state
      expandedPostId: null,

      // Meaning mode
      meaningMode: false,
      selectedWord: "",
      wordMeaningData: "",
      isLoadingMeaning: false,
      meaningExpanded: false,
      currentLookupPostId: null,
    };
  },
  methods: {
    // Toggle post expansion
    async togglePostExpand(postId) {
      if (this.expandedPostId === postId) {
        this.closeExpandedPost();
      } else {
        this.expandedPostId = postId;
        this.meaningMode = false;
        this.selectedWord = "";
        await this.fetchComments(postId);
      }
    },

    // Close expanded post
    closeExpandedPost() {
      this.expandedPostId = null;
      this.meaningMode = false;
      this.selectedWord = "";
    },

    // Fetch comments for a post
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

    // Add comment to expanded post
    async addComment(postId) {
      if (!this.newCommentContent.trim()) return;
      try {
        await axios.post(`/posts/${postId}/comments`, {
          content: this.newCommentContent
        });
        this.newCommentContent = "";
        await this.fetchComments(postId);
      } catch (error) {
        console.error("Failed to add comment:", error);
      }
    },

    // Delete comment
    async deleteComment(commentId, postId) {
      try {
        await axios.delete(`/comments/${commentId}`);
        await this.fetchComments(postId);
      } catch (error) {
        console.error("Failed to delete comment:", error);
      }
    },

    // Improved tokenization method that preserves spaces and punctuation
    tokenizeContent(text) {
      if (!text) return [];

      // This regex splits the text into words and non-words (spaces, punctuation)
      // It preserves all the original spacing and punctuation
      const tokens = [];
      const regex = /(\S+|\s+)/g;
      let match;

      while ((match = regex.exec(text)) !== null) {
        tokens.push(match[0]);
      }

      return tokens;
    },

    // Check if a token is a word (not just whitespace or punctuation)
    isWord(token) {
      // A word contains at least one letter or number
      return /\w/.test(token) && token.trim().length > 0;
    },

    // Meaning mode methods
    cleanWord(word) {
      let clean = word.replace(/[.,!?;:\"()]/g, '');
      clean = clean.replace(/[""„''']/g, '');
      return clean.trim();
    },

    toggleMeaningMode() {
      this.meaningMode = !this.meaningMode;
      this.selectedWord = "";
      this.wordMeaningData = "";
      this.isLoadingMeaning = false;
      this.meaningExpanded = false;
      this.currentLookupPostId = null;
    },

    async selectWordForMeaning(word, postId) {
      if (!this.meaningMode) return;

      const clean = this.cleanWord(word);
      if (!clean) return;

      this.selectedWord = clean;
      this.currentLookupPostId = postId;
      this.isLoadingMeaning = true;
      this.wordMeaningData = "";
      this.meaningExpanded = true;

      try {
        const res = await axios.get(`/lingvanex/lookup?word=${encodeURIComponent(clean)}`);
        const def = res.data?.definition;
        this.wordMeaningData = def && def.trim().length ? def : 'Definīcija nav atrasta.';
      } catch (e) {
        console.error(e);
        this.wordMeaningData = 'Kļūda meklējot definīciju.';
      } finally {
        this.isLoadingMeaning = false;
      }
    },

    toggleMeaningExpanded() {
      this.meaningExpanded = !this.meaningExpanded;
    },

    // Existing methods (keep all your existing logic)
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
        // create a fetch token to avoid race conditions where slower requests
        // override newer ones
        const token = ++this.lastFetchToken;
        this.isLoadingPosts = true;

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
        // If another fetch started after this one, drop this response
        if (this.lastFetchToken !== token) {
          return;
        }
        this.posts = fetched;
        this.isLoadingPosts = false;
      } catch (e) {
        this.isLoadingPosts = false;
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
        if (this.expandedPostId === postId) {
          this.closeExpandedPost();
        }
      } catch (e) {
        console.error(e);
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
/* New Layout System */
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
}

.expanded-post-card {
  background: var(--post-bg);
  border: 1px solid var(--post-border);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.expanded-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: var(--input-bg);
  border-bottom: 1px solid var(--post-border);
}

.expanded-header h3 {
  margin: 0;
  font-size: 18px;
  color: var(--post-text);
}

.close-expanded {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--post-text);
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.close-expanded:hover {
  background: rgba(0, 0, 0, 0.1);
}

.expanded-content {
  padding: 0;
}

/* Improved Post Grid */
.post-container {
  max-width: 100%;
  margin: 0 auto;
  box-sizing: border-box;
}

.post-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  justify-content: center;
}

@media (max-width: 768px) {
  .posts-layout {
    flex-direction: column;
  }

  .posts-grid-column.with-expanded,
  .expanded-post-column {
    flex: 1;
    max-width: 100%;
    position: static;
  }

  .post-row {
    grid-template-columns: 1fr;
  }
}

/* Improved Post Styling */
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
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.post-active {
  border-color: #2563eb;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
}

/* Better Image Sizing */
.post-image-container {
  margin: 8px 0;
  text-align: center;
}

.post-uploaded-image {
  max-width: 100%;
  max-height: 200px;
  width: auto;
  height: auto;
  object-fit: contain;
  border-radius: 4px;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.post-uploaded-image:hover {
  transform: scale(1.02);
}

.expanded-image-container {
  margin: 16px 0;
  text-align: center;
}

.expanded-post-image {
  max-width: 100%;
  max-height: 300px;
  width: auto;
  height: auto;
  object-fit: contain;
  border-radius: 6px;
}

/* Expanded Post Content */
.expanded-post-content {
  padding: 20px;
}

.expanded-post-title {
  font-family: "AbrilFatface";
  font-size: 24px;
  margin-bottom: 12px;
  color: var(--post-text);
  line-height: 1.3;
}

.post-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--post-border);
}

.author-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.author-name {
  font-weight: 600;
  color: var(--post-text);
}

/* FIXED: Meaning Mode Text Formatting */
.post-full-content {
  font-size: 15px;
  line-height: 1.6;
  margin-bottom: 20px;
  color: var(--post-text);
  white-space: normal;
  word-wrap: break-word;
  overflow-wrap: break-word;
  text-align: left;
}

.meaning-mode-text {
  font-family: inherit;
  font-size: 15px;
  line-height: 1.6;
  color: var(--post-text);
  white-space: normal;
  word-wrap: break-word;
  overflow-wrap: break-word;
  text-align: left;
}

.clickable-word {
  cursor: pointer;
  padding: 1px 2px;
  border-radius: 3px;
  transition: all 0.2s ease;
  border-bottom: 1px dotted transparent;
  color: var(--post-text);
  display: inline;
  white-space: normal;
}

.clickable-word:hover {
  background-color: rgba(37, 99, 235, 0.08);
  border-bottom-color: #2563eb;
}

.clickable-word.active-word {
  background-color: rgba(37, 99, 235, 0.15);
  border-bottom-color: #2563eb;
  font-weight: 500;
}

.non-clickable {
  display: inline;
  white-space: normal;
}

/* Comments section styling */
.comments-section {
  background-color: var(--input-bg);
  border-radius: 8px;
  padding: 20px;
  margin-top: 20px;
}

.section-title {
  font-family: "Aileron";
  font-size: 18px;
  margin-bottom: 16px;
  color: var(--post-text);
  border-bottom: 1px solid var(--post-border);
  padding-bottom: 8px;
}

.comments-list {
  max-height: 400px;
  overflow-y: auto;
  margin-bottom: 20px;
}

.comment-item {
  background-color: var(--post-bg);
  border-radius: 6px;
  padding: 12px;
  margin-bottom: 12px;
  border: 1px solid var(--post-border);
}

.comment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.comment-author {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* CORRECT PROFILE PHOTO SIZES */
.profile-photo-post {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
}

.comment-profile-photo {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  object-fit: cover;
}

.comment-header strong {
  font-size: 13px;
  color: var(--post-text);
}

.comment-date {
  font-size: 11px;
  color: var(--post-text);
  opacity: 0.7;
}

.comment-content-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
}

.comment-content {
  font-size: 13px;
  line-height: 1.4;
  color: var(--post-text);
  margin: 0;
  flex: 1;
}

.comments-delete .delete {
  cursor: pointer;
  font-size: 16px;
  color: #999;
  padding: 2px 6px;
  border-radius: 3px;
  transition: all 0.2s ease;
}

.comments-delete .delete:hover {
  color: #e74c3c;
  background-color: rgba(231, 76, 60, 0.1);
}

.no-comments {
  text-align: center;
  padding: 20px;
  color: var(--post-text);
  opacity: 0.7;
  font-style: italic;
}

.add-comment {
  margin-top: 20px;
}

.add-comment textarea {
  width: 100%;
  height: 80px;
  padding: 12px;
  border: 1px solid var(--post-border);
  border-radius: 6px;
  background-color: var(--post-bg);
  color: var(--post-text);
  font-family: "Aileron";
  font-size: 13px;
  resize: vertical;
  margin-bottom: 10px;
  transition: border-color 0.3s ease;
}

.add-comment textarea:focus {
  outline: none;
  border-color: #2563eb;
}

.add-comment button {
  background-color: #2563eb;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-family: "Aileron";
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.add-comment button:hover {
  background-color: #1e40af;
}

.login-note {
  text-align: center;
  padding: 20px;
  color: var(--post-text);
  opacity: 0.7;
  font-style: italic;
  border: 1px dashed var(--post-border);
  border-radius: 6px;
}

/* RESTORED BUTTON COLORS */
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
  transition: all 0.2s ease;
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
  transition: all 0.2s ease;
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

/* Meaning mode improvements */
.btn-meaning {
  background-color: transparent;
  border: 1px solid #2563eb;
  color: #2563eb;
  padding: 8px 16px;
  border-radius: 6px;
  font-family: "Aileron";
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-bottom: 16px;
}

.btn-meaning:hover {
  background-color: #2563eb;
  color: white;
}

.meaning-section {
  background-color: var(--post-bg);
  border: 1px solid var(--post-border);
  border-radius: 8px;
  overflow: hidden;
  margin-top: 16px;
}

.meaning-header {
  background-color: rgba(37, 99, 235, 0.05);
  padding: 12px 16px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: background-color 0.2s ease;
}

.meaning-header:hover {
  background-color: rgba(37, 99, 235, 0.1);
}

.meaning-header h4 {
  margin: 0;
  font-size: 14px;
  color: var(--post-text);
  border: none;
  padding: 0;
}

.expand-icon {
  transition: transform 0.3s ease;
  color: var(--post-text);
}

.expand-icon.expanded {
  transform: rotate(180deg);
}

.meaning-content {
  padding: 16px;
  background-color: var(--input-bg);
}

.word-definition {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.word-title {
  font-size: 16px;
  color: #2563eb;
  font-weight: 600;
  font-family: "Aileron";
}

.definition-text {
  font-size: 14px;
  line-height: 1.5;
  color: var(--post-text);
  margin: 0;
  padding: 12px;
  background-color: var(--post-bg);
  border-radius: 6px;
  border-left: 3px solid #2563eb;
}

.meaning-expand-enter-active,
.meaning-expand-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}
.meaning-expand-enter-from,
.meaning-expand-leave-to {
  max-height: 0;
  opacity: 0;
}
.meaning-expand-enter-to,
.meaning-expand-leave-from {
  max-height: 200px;
  opacity: 1;
}

/* Filter bar and other styles remain the same */
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
  font-family: "Aileron";
  font-size: 16px;
  margin-bottom: 6px;
  color: var(--post-text);
}
.categories-checkboxes {
  display: flex;
  flex-wrap: wrap;
  gap: 24px;
  font-family: "Aileron";
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

/* Create post and edit modal styles remain the same */
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
  font-family: "Aileron";
}
.create-post-container input,
.create-post-container textarea {
  width: 100%;
  padding: 8px;
  margin-top: 5px;
  border: 1px solid var(--input-border);
  border-radius: 4px;
  font-size: 14px;
  font-family: "Aileron";
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
  font-family: "Aileron";
  transition: filter 0.2s ease;
}
.create-post-container button:hover {
  filter: brightness(1.1);
}
.create-post-container h2 {
  font-family: "Aileron";
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
  font-family: "Aileron";
  font-size: 16px;
  margin-bottom: 4px;
  color: var(--post-text);
}
.create-categories-checkboxes {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-family: "Aileron";
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
  font-family: "Aileron";
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
  font-family: "Aileron";
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
  font-family: "Aileron";
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
  max-width: 90%;
  max-height: 90%;
  width: auto;
  height: auto;
  display: flex;
  flex-direction: row;
  border-radius: 8px;
  overflow: hidden;
  box-sizing: border-box;
}
</style>
