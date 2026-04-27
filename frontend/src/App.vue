<script setup>
  import { auth } from './plugins/userinfo'
  import { onMounted, ref } from 'vue'
  import Header from './components/Header.vue'
  import Footer from './components/Footer.vue'
  import axios from './plugins/axios'
  import Catalogheader from './components/Catalogheader.vue'

  const searchBar = ref("")

  const onSearch = (value) => {searchBar.value = value}

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
    <Catalogheader v-if="$route.meta.hideHeader" @update-search="onSearch" :search-value="searchBar"></Catalogheader>
    <v-main>
      <RouterView v-if="!$route.meta.hideHeader" v-slot="{ Component, route }">
          <component :is="Component" :key="route.fullPath" />
      </RouterView>

      <RouterView v-if="$route.meta.hideHeader" v-slot="{ Component, route }">
          <component :search-query="searchBar" :is="Component" :key="route.fullPath" />
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

