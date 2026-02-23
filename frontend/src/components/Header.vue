<script setup>
import { ref } from 'vue'

  const location = ref('end')

  const items = [
    { title: 'MAIN', to: '/' },
    { title: 'CATALOG', to: '/catalog' },
    { title: 'ABOUT', to: '/about' },
    { title: 'LOGIN', to: '/login' },
    { title: 'REGISTER', to: '/register' },
  ]
</script>

<template>

<header class="site-header">
    <div class="header">
        <nav>      
            <router-link to="/"><a class="logo" href="#">GAMESPACE</a></router-link>
            <a href="#">CATALOG</a>
            <a href="#">ABOUT</a>  
        </nav>

        <nav>
            <router-link to="/login"><a href="#">LOG IN</a></router-link>
            <p >|</p>
            <router-link to="/register"><a href="#">REGISTER</a></router-link>
        </nav>
    </div>

   <div class="burger-menu">
    <v-menu :location="bottom">
      <template v-slot:activator="{ props }">
        <v-btn color="black" v-bind="props">☰</v-btn>
        </template>

      <v-list>
        <v-list-item v-for="(item, index) in items" :key="index" :to="item.to" link >
          <v-list-item-title>{{ item.title }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
    </div>

</header>
</template>



<style scoped>
    .burger-menu {
        display: none;
    }

    .site-header {
        position: sticky;
        top: 0;
        width: 100%;
        z-index: 10000;
    }

    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 24px 40px;
        margin: 0 auto;
    }

    nav {
        display:flex;
        gap: 40px;
    }

    nav a{
        font-size: 12px;
        letter-spacing: 2px;
        text-decoration: none;
        transition: color 0.2s;
        color: rgb(255, 255, 255);
        display: inline-flex; 
        align-items: center;
    }

    nav a:hover{
        color:rgba(255, 255, 255, 0.406);
    }

    p {
        font-size: 12px;
        letter-spacing: 2px;
        text-decoration: none;
        transition: color 0.2s;
        color: rgb(255, 255, 255);
        display: inline-flex; 
        align-items: center;
    }

    .logo {
    font-size: 18px;
    font-weight: 400;
    letter-spacing: 8px;
    opacity: 0.9;
    }

    @media (max-width: 768px) {
        .header {
            display: none;
        }

        .burger-menu {
            display: flex;
            position: sticky;
            top: 0;
            justify-content: center;
            z-index: 10000;
            
        }
    }
    
</style>
