<template>
  <div class="search-filters">
    <div class="filter-bar">
      <div class="filter-left">
        <div class="filter-item">
          <div class="search-box">
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Search posts..."
            />
            <button class="search-btn" @click="handleSearch">Meklēt</button>
          </div>
        </div>
      </div>

      <div class="filter-center">
        <div class="filter-item category-box" v-if="!hideCategories">
          <label>Izvēlēties kategorijas:</label>
          <div class="categories-checkboxes">
            <label
              v-for="category in categories"
              :key="category.id"
              class="checkbox-item"
            >
              <span class="category-label">{{ category.name }}</span>
              <input
                type="checkbox"
                :value="category.id"
                v-model="selectedCategories"
                @change="$emit('categories-changed', selectedCategories)"
                class="category-checkbox"
              />
            </label>
          </div>
          <p class="found-count">Atrasti {{ foundCount }} ieraksti</p>
        </div>
      </div>

      <div class="filter-right">
        <div class="filter-item sort-box">
          <label for="sortBy">Kārtot pēc:</label>
          <select v-model="sortBy" @change="$emit('sort-changed', sortBy)">
            <option value="reactions">Reakcijas</option>
            <option value="date">Datums</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SearchFilters",
  props: {
    categories: {
      type: Array,
      default: () => []
    },
    hideCategories: {
      type: Boolean,
      default: false
    },
    foundCount: {
      type: Number,
      default: 0
    },
    initialSelectedCategories: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      searchQuery: "",
      sortBy: "reactions",
      selectedCategories: [...this.initialSelectedCategories]
    }
  },
  methods: {
    handleSearch() {
      this.$emit('search', this.searchQuery);
    }
  },
  watch: {
    initialSelectedCategories(newVal) {
      this.selectedCategories = [...newVal];
    }
  }
}
</script>

<style scoped>
/* CSS Variables for Light and Dark Mode */
.search-filters {
  --post-bg: #ffffff;
  --post-text: #2c3e50;
  --post-border: #e0e0e0;
  --input-bg: #ffffff;
  --input-border: #d1d5db;
  --button-bg: #2196f3;
  --button-text: #ffffff;
  --button-hover-bg: #1976d2;
}

/* Dark mode support - matches body.dark from navbar toggle */
body.dark .search-filters,
body.dark-mode .search-filters {
  --post-bg: #1f2937;
  --post-text: #d1d5db;
  --post-border: #374151;
  --input-bg: #273449;
  --input-border: #4b5563;
  --button-bg: #1976d2;
  --button-text: #ffffff;
  --button-hover-bg: #1565c0;
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
  align-items: flex-start;
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
  transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
}

.search-box input {
  padding: 8px;
  border: none;
  border-bottom: 2px solid var(--input-border);
  background-color: var(--input-bg);
  color: var(--post-text);
  font-size: 14px;
  width: 200px;
  transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
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
  transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
}

.sort-box select:focus {
  outline: none;
  border-bottom-color: var(--input-border);
}

/* Mobile Responsive Design */
@media (max-width: 768px) {
  .search-filters {
    margin: 0;
    padding: 10px;
    width: 100%;
    box-sizing: border-box;
  }

  .filter-bar {
    flex-direction: column;
    gap: 10px;
    padding: 0;
    margin: 0;
    width: 100%;
  }

  .filter-left,
  .filter-center,
  .filter-right {
    width: 100%;
    margin: 0;
    padding: 0;
  }

  .filter-item {
    padding: 10px;
    border-radius: 8px;
  }

  .search-box {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .search-box input {
    width: 100%;
    padding: 10px 12px;
    font-size: 15px;
    border-radius: 6px;
    border: 1px solid var(--input-border);
    box-sizing: border-box;
  }

  .search-btn {
    width: 100%;
    padding: 10px;
    font-size: 15px;
    border-radius: 6px;
    background: var(--input-bg);
    color: var(--post-text);
    border: 1px solid var(--input-border);
    cursor: pointer;
    font-weight: 500;
    margin-top: 4px;
  }

  .search-btn:hover {
    filter: brightness(1.15);
  }

  .category-box {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid var(--post-border);
    margin: 0;
    width: 100%;
    box-sizing: border-box;
  }

  .category-box > label:first-child {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    display: block;
    color: var(--post-text);
  }

  .categories-checkboxes {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 6px;
    margin-bottom: 8px;
    width: 100%;
  }

  .checkbox-item {
    padding: 8px 6px;
    background: var(--input-bg);
    border-radius: 6px;
    font-size: 13px;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 4px;
    box-sizing: border-box;
  }

  .category-checkbox {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
  }

  .category-label {
    font-size: 13px;
    font-weight: 400;
    flex: 1;
    text-align: left;
  }

  .found-count {
    text-align: center;
    background: var(--input-bg);
    padding: 6px 8px;
    border-radius: 6px;
    margin-top: 6px;
    font-weight: 500;
    font-size: 13px;
  }

  .sort-box {
    align-items: stretch;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid var(--post-border);
    margin: 0;
    width: 100%;
    box-sizing: border-box;
  }

  .sort-box label {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 6px;
    text-align: left;
    display: block;
  }

  .sort-box select {
    width: 100%;
    padding: 10px;
    font-size: 14px;
    border: 1px solid var(--input-border);
    border-radius: 6px;
    box-sizing: border-box;
  }
}
</style>

