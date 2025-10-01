<template>
    <div class="history">
        <navbar/>
        <h1>History of Fashion</h1>
        <post-form :hide-categories="true" filter-category-name="History" />
        <AppFooter/>
    </div>
</template>

<script>
import navbar from "@/Components/navbar.vue";
import AppFooter from "@/Components/footer.vue";

export default {
    name: 'HistoryView',
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
