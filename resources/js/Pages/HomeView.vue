<template>
    <div class="home">
        <navbar/>
        <p class="welcome-text">Welcome to Mireqx!</p>

        <div class="image-grid">
            <div
                class="image-container"
                v-for="(image, index) in previewImages"
                :key="index"
            >
                <a :href="`/runway`">
                    <img
                        :src="image"
                        alt="Fashion preview"
                        class="preview-img"
                    />
                    <p class="overlay-text">{{ designerNames[index] }}</p>
                </a>
            </div>
        </div>
    </div>

    <post-form></post-form>
    <AppFooter/>
</template>

<script>
import navbar from "@/Components/navbar.vue";
import AppFooter from "@/Components/footer.vue";

export default {
    name: "HomeView",
    components: { navbar, AppFooter },
    data() {
        return {
            previewImages: [
                "https://i.pinimg.com/736x/8a/41/ac/8a41ac9ec942b68052d6ff8b5e7bb015.jpg",
                "https://i.pinimg.com/736x/00/b6/ab/00b6ab393fe28cca0875cafeacda04d8.jpg",
                "https://i.pinimg.com/736x/2a/26/43/2a2643e561b29f3f6cdd34784b9ee498.jpg"
            ],
            designerNames: [
                "Alexander McQueen",
                "Rick Owens",
                "Maison Margiela"
            ]
        };
    },
    mounted() {
        this.checkLoginStatus();
    }
};
</script>

<style scoped>
.welcome-text {
    text-align: center;
    font-size: 45px;
    font-family: 'Perfectly Vintages';
    padding: 80px 30px 60px;
    color: black;
}
body.dark .welcome-text {
    color: white;
}

.image-grid {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 40px;
    margin-bottom: 40px;
}

.image-container {
    width: 30%;
    height: 600px;
    position: relative;
}

.preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
    transition: transform 0.3s ease;
}

.image-container:hover .preview-img {
    transform: scale(1.05);
}

.overlay-text {
    position: absolute;
    bottom: 15px;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    font-size: 24px;
    font-weight: bold;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.image-container:hover .overlay-text {
    opacity: 1;
}

@media (max-width: 960px) {
    .image-grid {
        flex-direction: column;
        align-items: center;
    }

    .image-container {
        width: 80%;
        height: auto;
    }
}

@media (min-width: 961px) {
    .image-grid {
        flex-direction: row;
        justify-content: center;
        flex-wrap: nowrap;
        gap: 40px;
    }

    .image-container {
        width: 30%;
    }
}
</style>
