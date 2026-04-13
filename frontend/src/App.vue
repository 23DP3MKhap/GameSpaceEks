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
    <Catalogheader v-if="$route.meta.hideHeader" @update-search="onSearch"></Catalogheader>
    <v-main>
      <RouterView v-if="!$route.meta.hideHeader" v-slot="{ Component, route }">
        <Transition name="page" mode="out-in">
          <component :is="Component" :key="route.fullPath" />
        </Transition>
      </RouterView>

      <RouterView v-if="$route.meta.hideHeader" v-slot="{ Component, route }">
        <Transition name="page" mode="out-in">
          <component :search-query="searchBar" :is="Component" :key="route.fullPath" />
        </Transition>
      </RouterView>

    </v-main>
    <Footer></Footer>
  </v-app>
</template>

<style scoped>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.1s ease;
}

.page-enter-from,
.page-leave-to {
  opacity: 0;
}

:global(body) {
  background-color: black;
}
</style>

