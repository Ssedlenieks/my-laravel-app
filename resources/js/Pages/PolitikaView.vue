<template>
    <div class="politika">
        <navbar/>
    <h1>Politika</h1>
        <post-form :hide-categories="true" filter-category-name="Politika" />
        <AppFooter/>
    </div>
</template>

<script>
import navbar from "@/Components/navbar.vue";
import AppFooter from "@/Components/footer.vue";

export default {
    name: 'PolitikaView',
    components: { navbar, AppFooter },
};
</script>

<style scoped>
h1 {
    text-align: center;
    color: black;
    font-size: 45px;
    font-family: 'Aileron';
    font-weight: bold;
    padding: 30px;
}

</style>
