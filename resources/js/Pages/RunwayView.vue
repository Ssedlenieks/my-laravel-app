<template>
    <div class="runway">
        <navbar/>
        <h1>Explore the runway</h1>
        <post-form :hide-categories="true" filter-category-name="Runway" />
        <AppFooter/>
    </div>
</template>

<script>
import navbar from "@/Components/navbar.vue";
import AppFooter from "@/Components/footer.vue";

export default {
    name: 'RunwayView',
    components: { navbar, AppFooter },
};
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
