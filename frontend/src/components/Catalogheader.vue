<script setup>
    import { ref, computed } from "vue"
    import { auth } from "../plugins/userinfo"
    import axios from "../plugins/axios"

    const props = defineProps({searchValue: String});
    const emit = defineEmits(['update-search']);


    function searchUpdate(search){
        emit('update-search', search.target.value)
    }

    const dialog = ref(false)  
    const items = [
        { title: "MAIN", to: "/" },
        { title: "CATALOG", to: "/Catalog" },
        { title: "ABOUT", to: "/about" },
        { title: "LOGIN", to: "/login" },
        { title: "REGISTER", to: "/register" },
    ]





    const itemsregistered = computed(() => {
      const items = [
        { title: "MAIN", to: "/" },
        { title: "CATALOG", to: "/Catalog" },
        { title: "ABOUT", to: "/about" },
      ]
    
      if (auth.user) {
        items.unshift({
          title: auth.user.name,
          to: `/User/${auth.user.id}/Profile`
        })

        if (auth.user.role === 'admin') {
          items.push({
            title: "ADMIN",
            to: "/Admin"
          })
        }
    
        items.push({
          title: "LOG OUT",
          action: "logout"
        })
      }
  
      return items
    })

    async function logout(){
        try {
            await axios.get("/sanctum/csrf-cookie")
            await axios.post("/logout")
            auth.user = null
            dialog.value = true
            return console.log("logged out")
    } catch (error) {
        return console.log("error")
    }}

    
</script>

<template>

<header class="site-header">
    <div class="header">
        <nav>      
            <router-link to="/"><a class="logo">GAMESPACE</a></router-link>
            <router-link to="/Catalog"><a>CATALOG</a></router-link>
            <router-link to="/About"><a>ABOUT</a></router-link>
        </nav>



        <div class="header-center">
            <input :value="searchValue"  @input="searchUpdate" type="text" placeholder="Search games..." class="search-input">
        </div>

            <div v-if="auth.user">
                <nav>
                <router-link v-if="auth.user.role === 'admin'" :to = "{path: `/Admin`}"><a>Admin</a></router-link>
                <p>|</p>
                <router-link :to = "{path: `/User/${auth.user.id}/Profile`}"><a>{{auth.user.name}}</a></router-link>
                <p>|</p>
                <a @click.prevent="logout">LOG OUT</a>
                </nav>
            </div>
            <div v-if="!auth.user">
                <nav>
                <router-link to="/login"><a>LOG IN</a></router-link>
                <p>|</p>
                <router-link to="/register"><a>REGISTER</a></router-link>
                </nav>
            </div>
    </div>

   <div class="burger-menu">
        <input :value="searchValue" @input="searchUpdate" type="text" placeholder="Search games..." class="search-input">
        <v-menu>
        <template v-slot:activator="{ props }">
            <v-btn color="black" v-bind="props">☰</v-btn>
        </template>     
          <v-list>
            <div v-if="auth.user">
                <v-list-item v-for="(route) in itemsregistered" :to="route.to" link @click="route.action === 'logout' && logout()">
                <v-list-item-title>{{ route.title }}</v-list-item-title>
                </v-list-item>
            </div>      
            <div v-if="!auth.user">
                <v-list-item v-for="(route) in items" :to="route.to" link>
                    <v-list-item-title>{{ route.title }}</v-list-item-title>
                </v-list-item>
            </div>
            </v-list>
        </v-menu>
    </div>

    <v-dialog max-width="500" v-model="dialog">
        <v-card class="v-card" color="black">
        <v-card-title class="v-card-title">Logout</v-card-title>
            <v-card-text class="v-card-text">
                Successfully logged out!
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>

                <v-btn class="v-dialog-button" @click="dialog = false">
                    <p class="v-btn-text">Close</p>
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

</header>
</template>



<style scoped>

:deep(.v-list) {
    background-color: #0d0d0d !important;
    border: 1px solid #1f1f1f;
    padding: 8px 0;
}
:deep(.v-list-item) {
    color: #eeeeee;
}

:deep(.v-list-item:hover) {
    background-color: #1a1a1a;
}

:deep(.v-list-item-title) {
    font-size: 14px;
    font-weight: 500;
}

.search-input {
    min-width: 40vw;
    background: #111;
    border: 1px solid #1f1f1f;
    border-radius: 10px;
    padding: 8px 12px;
    color: white;
    font-size: 12px;
    font-weight: 300;
    outline: none;
}

.search-input::placeholder {
    color: #666;
}

.search-input:focus {
    border-color: #333;
}

    a {
        cursor: pointer;
    }

    .v-card{
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.587);
    }

    .v-card-title, .v-card-text {
        color: white;
        }

    .v-btn-text {
        color:white;
        font-size: 10px;
    }

    .dialog, .v-dialog-button{
        background-color: rgb(0, 0, 0);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.14);
    }


    .burger-menu {
        display: none;
    }

    .site-header {
        top: 0;
        width: 100%;
        background-color: #0d0d0d;;
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
