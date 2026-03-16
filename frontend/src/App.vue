<script setup>
  import { auth } from './plugins/userinfo'
  import { onMounted } from 'vue'
  import Header from './components/Header.vue'
  import Footer from './components/Footer.vue'
  import axios from './plugins/axios'

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
    <Header></Header>
    <v-main>
      <RouterView v-slot="{ Component, route }">
        <Transition name="page" mode="out-in">
          <component :is="Component" :key="route.fullPath" />
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

