<template>
  <div class="home">
    <navbar />
    <p class="welcome-text">Jaunākie jaunumi platformā Labaziņa!</p>

    <div class="news-grid">
      <div v-for="(image, index) in previewImages" :key="index" class="news-card">
        <a :href="`/runway`">
          <img :src="image" alt="News preview" class="news-img" />
          <div class="news-content">
            <h3 class="headline">{{ designerNames[index] }}</h3>
            <p class="summary">Catch up on the latest developments in this story. Click to read more.</p>
          </div>
        </a>
      </div>
    </div>

    <!-- Show post form only if user is logged in -->
    <post-form v-if="user" />

    <AppFooter />
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
import navbar from "@/Components/navbar.vue";
import AppFooter from "@/Components/footer.vue";

const page = usePage()
const user = page.props.auth?.user || null

const previewImages = [
  "https://miro.medium.com/v2/resize:fit:4800/format:webp/1*njbON5z9v07DZ3Fv2QDhHQ.jpeg",
  "https://www.aiplusinfo.com/wp-content/uploads/2024/12/Hallucinatory-A.I.-Sparks-Scientific-Innovations.jpeg",
  "https://cdn.databridgemarketresearch.com/media/2025/9/GlobalSystemofInsightMarket.webp"
];

const designerNames = [
  "Global Affairs Briefing",
  "Science & Innovation",
  "World Market Insights"
];
</script>

<style scoped>
.welcome-text {
  text-align: center;
  font-size: 45px;
  font-family: "Aileron", serif;
  padding: 80px 30px 60px;
  color: var(--post-text, black);
  font-weight: bold;
}
body.dark .welcome-text {
  color: white;
}

.news-grid {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 32px;
  margin-bottom: 40px;
}

.news-card {
  background: var(--post-bg, #f5f5f5);
  border-radius: 8px;
  width: 340px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  transition: box-shadow 0.2s, background-color 0.3s, color 0.3s, border-color 0.3s;
  overflow: hidden;
  position: relative;
  border: 1px solid transparent;
  color: var(--post-text, #222);
}
.news-card:hover {
  box-shadow: 0 8px 20px rgba(0,0,0,0.14);
}
.news-img {
  display: block;
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-bottom: 1px solid var(--post-border, #e5e5e5);
}
.news-content {
  padding: 24px 18px 18px;
}
.headline {
  margin: 0 0 12px 0;
  font-size: 1.25rem;
  color: var(--post-text, #222);
  font-weight: bold;
}
.summary {
  margin: 0;
  color: var(--post-text, #444);
  font-size: 1rem;
}

body.dark .news-card {
  background: var(--post-bg, #1f2937);
  border-color: var(--post-border, #374151);
  color: var(--post-text, #d1d5db);
  box-shadow: 0 2px 8px rgba(0,0,0,0.4);
}
body.dark .news-card:hover {
  box-shadow: 0 8px 20px rgba(0,0,0,0.7);
}
body.dark .news-img {
  border-bottom-color: var(--post-border, #4b5563);
}

@media (max-width: 960px) {
  .news-grid {
    flex-direction: column;
    align-items: center;
  }
  .news-card {
    width: 90%;
  }
}
</style>
