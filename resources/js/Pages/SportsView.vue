<template>
    <div class="sports">
        <navbar />
        <h1>Sporta ziņas</h1>
        <post-form :hide-categories="true" filter-category-name="Style" />
        <AppFooter />
    </div>
</template>

<script>
import navbar from "@/Components/navbar.vue";
import AppFooter from "@/Components/footer.vue";

export default {
    name: 'SportsView',
    components: { navbar, AppFooter },
}
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

