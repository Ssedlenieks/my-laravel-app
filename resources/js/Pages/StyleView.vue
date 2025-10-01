<template>
    <div class="style">
        <navbar />
        <h1>Find your style</h1>
        <post-form :hide-categories="true" filter-category-name="Style" />
        <AppFooter />
    </div>
</template>

<script>
import navbar from "@/Components/navbar.vue";
import AppFooter from "@/Components/footer.vue";

export default {
    name: 'StyleView',
    components: { navbar, AppFooter },
}
</script>

<style scoped>

h1 {
    text-align: center;
    color: black;
    font-size: 45px;
    font-family: 'Perfectly Vintages';
    font-weight: normal;
    padding: 30px;
}

</style>

