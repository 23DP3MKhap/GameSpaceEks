<script setup>
    import { auth } from './plugins/userinfo'
    import { onMounted, ref } from 'vue'
    import Header from './components/Header.vue'
    import Footer from './components/Footer.vue'
    import axios from './plugins/axios'
    import Catalogheader from './components/Catalogheader.vue'  
    const searchBar = ref("")   

    function searchUpdate(search){
        searchBar.value = search
    }

    onMounted(async () => {
    try {
        const response = await axios.get('/api/user')
        auth.user = response.data
    } catch (error) {
        auth.user = null
        return console.log("Not logged in")
    }
})

</script>
<template>
  <v-app>
    <Header v-if="!$route.meta.hideHeader"></Header>
    <Catalogheader v-if="$route.meta.hideHeader" @update-search="searchUpdate" :search-value="searchBar"></Catalogheader>
    <v-main>

      <RouterView v-if="!$route.meta.hideHeader"></RouterView>

      <RouterView v-if="$route.meta.hideHeader" v-slot="{Component}">
          <component :search-value="searchBar" :is="Component"></component>
      </RouterView>

    </v-main>
    <Footer></Footer>
  </v-app>
</template>

<style scoped>


:global(body) {
  background-color: #0d0d0d;
}
</style>

