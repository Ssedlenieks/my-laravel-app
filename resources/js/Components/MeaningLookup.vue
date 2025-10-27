<template>
  <div class="meaning-lookup">
    <!-- Dictionary toggle button -->
    <button @click="toggleMeaningMode" class="btn-meaning">
      {{ internalMeaningMode ? 'Izslēgt vārdnīcu' : 'Ieslēgt vārdnīcu' }}
    </button>

    <!-- Clickable content when meaning mode is active -->
    <div v-if="internalMeaningMode" class="clickable-content">
      <p class="meaning-mode-text">
        <template v-for="(token, index) in tokenizeContent(content)" :key="index">
          <span
            v-if="isWord(token)"
            class="clickable-word"
            @click="selectWordForMeaning(token, postId)"
            :class="{ 'active-word': selectedWord === cleanWord(token) && currentLookupPostId === postId }"
          >
            {{ token }}
          </span>
          <span v-else class="non-clickable">{{ token }}</span>
        </template>
      </p>
    </div>

    <!-- Regular content when meaning mode is off -->
    <div v-else class="regular-content">
      <p>{{ content }}</p>
    </div>

    <!-- Dictionary section with selected word meaning -->
    <div v-if="internalMeaningMode && selectedWord" class="meaning-section">
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
</template>

<script>
import axios from "axios";

export default {
  name: "MeaningLookup",
  emits: ['mode-changed', 'word-selected'],
  props: {
    content: {
      type: String,
      required: true
    },
    postId: {
      type: Number,
      required: true
    },
    meaningMode: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      internalMeaningMode: false,
      selectedWord: "",
      wordMeaningData: "",
      isLoadingMeaning: false,
      meaningExpanded: false,
      currentLookupPostId: null,
    }
  },
  methods: {
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

    toggleMeaningMode() {
      console.log('MeaningLookup: toggleMeaningMode called, current mode:', this.internalMeaningMode);
      this.internalMeaningMode = !this.internalMeaningMode;
      console.log('MeaningLookup: new meaning mode:', this.internalMeaningMode);
      this.selectedWord = "";
      this.wordMeaningData = "";
      this.isLoadingMeaning = false;
      this.meaningExpanded = false;
      this.currentLookupPostId = null;
      this.$emit('mode-changed', { mode: this.internalMeaningMode, postId: this.postId });
    },

    async selectWordForMeaning(word, postId) {
      console.log('MeaningLookup: selectWordForMeaning called', { word, postId, meaningMode: this.internalMeaningMode });

      if (!this.internalMeaningMode) return;

      const clean = this.cleanWord(word);
      if (!clean) return;

      console.log('MeaningLookup: Looking up word:', clean);

      this.selectedWord = clean;
      this.currentLookupPostId = postId;
      this.isLoadingMeaning = true;
      this.wordMeaningData = "";
      this.meaningExpanded = true;

      // Emit the word selection event to parent
      this.$emit('word-selected', { word: clean, postId: postId });

      try {
        console.log('MeaningLookup: Making API request to /lingvanex/lookup');
        const res = await axios.get(`/lingvanex/lookup?word=${encodeURIComponent(clean)}`);
        console.log('MeaningLookup: API response:', res.data);
        const def = res.data?.definition;
        this.wordMeaningData = def && def.trim().length ? def : 'Definīcija nav atrasta.';
      } catch (e) {
        console.error('MeaningLookup: API error:', e);
        this.wordMeaningData = 'Kļūda meklējot definīciju.';
      } finally {
        this.isLoadingMeaning = false;
      }
    },

    toggleMeaningExpanded() {
      this.meaningExpanded = !this.meaningExpanded;
    },

    renderContent() {
      if (!this.meaningMode) {
        return this.content;
      }

      // Return tokenized content for click handling
      return this.tokenizeContent(this.content);
    }
  }
}
</script>

<style scoped>
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

/* Clickable word styles */
.clickable-content {
  margin: 15px 0;
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

.regular-content {
  margin: 15px 0;
  line-height: 1.6;
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
</style>
