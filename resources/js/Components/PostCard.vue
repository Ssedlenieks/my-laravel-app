<template>
  <div class="post-card" :class="{ 'post-active': isActive }">
    <div class="post-regular-content">
      <h3 v-html="highlight(post.title)"></h3>
      <h4>{{ post.categories.map(c => c.name).join(', ') }}</h4>

      <div v-if="post.image_url" class="post-image-container">
        <img
          :src="post.image_url"
          class="post-uploaded-image"
          @click="$emit('expand-post', post.id)"
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
        <button @click="$emit('add-reaction', 'like', post.id)">👍</button>
        <span>{{ post.reactionCounts?.like }}</span>
        <button @click="$emit('add-reaction', 'dislike', post.id)">👎</button>
        <span>{{ post.reactionCounts?.dislike }}</span>
        <button @click="$emit('add-reaction', 'heart', post.id)">❤️</button>
        <span>{{ post.reactionCounts?.heart }}</span>
      </div>

      <div class="post-buttons">
        <button
          v-if="canDelete"
          @click="$emit('delete-post', post.id)"
          class="btn-delete"
        >
          Delete
        </button>

        <button
          v-if="canEdit"
          @click="$emit('edit-post', post)"
          class="btn-edit"
        >
          Edit
        </button>
        <button
          class="open-post-btn"
          @click="$emit('expand-post', post.id)"
        >
          {{ isActive ? 'Close' : 'Open Post' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "PostCard",
  props: {
    post: {
      type: Object,
      required: true
    },
    isActive: {
      type: Boolean,
      default: false
    },
    searchQuery: {
      type: String,
      default: ""
    },
    searchActive: {
      type: Boolean,
      default: false
    },
    currentUserId: {
      type: Number,
      default: null
    },
    isAdmin: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    canDelete() {
      return (this.post.user && this.post.user.id === this.currentUserId) ||
             (this.isAdmin && !this.post.user.is_admin);
    },
    canEdit() {
      return (this.post.user && this.post.user.id === this.currentUserId) ||
             (this.isAdmin && !this.post.user.is_admin);
    }
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
    }
  }
}
</script>

<style scoped>
.post-card {
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

.user-name {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 8px 0;
}

.author-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-photo-post {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
}

.author-text {
  margin: 0;
  font-size: 13px;
}

.post-date {
  font-size: 11px;
  color: var(--post-text);
  opacity: 0.7;
  margin: 0;
}

.post-content-container {
  margin: 8px 0;
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
</style>
