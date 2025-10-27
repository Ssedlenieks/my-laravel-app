<template>
  <div class="comments-section">
    <h4 class="section-title">Komentāri ({{ comments.length }})</h4>

    <div v-if="comments.length" class="comments-list">
      <div
        v-for="comment in comments"
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
            v-if="canDeleteComment(comment)"
            class="comments-delete"
          >
            <span @click="$emit('delete-comment', comment.id)" class="delete">×</span>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="no-comments">
      <p>No comments yet</p>
    </div>

    <div class="add-comment" v-if="isLoggedIn">
      <textarea
        v-model="newCommentContent"
        placeholder="Pievienot komentāru..."
        @keydown.enter.prevent="handleAddComment"
      ></textarea>
      <button @click="handleAddComment">Publicēt komentāru</button>
    </div>
    <p v-else class="login-note">Pierakstieties lai pievienotu komentāru</p>
  </div>
</template>

<script>
export default {
  name: "CommentsSection",
  props: {
    comments: {
      type: Array,
      default: () => []
    },
    isLoggedIn: {
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
  data() {
    return {
      newCommentContent: ""
    }
  },
  methods: {
    canDeleteComment(comment) {
      return this.isLoggedIn && (
        this.currentUserId === comment.user.id ||
        (this.isAdmin && !comment.user.is_admin)
      );
    },

    handleAddComment() {
      if (!this.newCommentContent.trim()) return;
      this.$emit('add-comment', this.newCommentContent);
      this.newCommentContent = "";
    }
  }
}
</script>

<style scoped>
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
  color: var(--post-text);
  opacity: 0.6;
  padding: 2px 6px;
  border-radius: 3px;
  transition: all 0.2s ease;
}

.comments-delete .delete:hover {
  color: #e74c3c;
  background-color: rgba(231, 76, 60, 0.1);
  opacity: 1;
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
</style>
