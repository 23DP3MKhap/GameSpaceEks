<script setup>

  import Header from './components/Header.vue'
  import Home from './pages/Home.vue'
  import Footer from './components/Footer.vue'
</script>
<template>
  <v-app>
    <Header></Header>
    <v-main>
      <Home></Home>
    </v-main>
    <Footer></Footer>
  </v-app>
</template>
<style scoped></style>
