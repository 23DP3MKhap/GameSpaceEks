<script setup>
    import { ref, onMounted } from 'vue'
    import axios from '../plugins/axios'
    import { useRoute } from 'vue-router'
    const route = useRoute()
    const profileusername = ref("")

    onMounted(async function userusername(){
        profileusername.value = (await axios.get("/api/user/username", { params: { id: route.params.id }})).data
    })

</script>

<template>
    <div>
    <div class="background"><img src="/backgrounds/profile-background.png"></div>

        <div class="main-card-wrapper">
            <v-card color="black" :title="`${profileusername}`" class= "main-card" subtitle="Subtitle" text="..." >
            </v-card>
        </div>

    </div>
</template>
    
<style scoped>

    .main-card-wrapper {
    display: flex;
    justify-content: center; 
    align-items: center;     
    position: relative;
}
    .main-card{
        width: 80vw;
        height: 80vh;
    }

.background {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: -1;
}
</style>


