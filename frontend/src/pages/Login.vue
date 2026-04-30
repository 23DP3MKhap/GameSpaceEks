<script setup>
  import { ref } from 'vue'
  import axios from '../plugins/axios'
  import { auth } from '../plugins/userinfo'
  import router from '@/router';
  
  const email = ref("")
  const password = ref("")
  const valid = ref(false)
  const dialog = ref(false)
  const dialogerror = ref(false)

  async function login(){
    try{
    await axios.get('/sanctum/csrf-cookie')
    await axios.post("/login", {email: email.value, password: password.value})
    auth.user = (await axios.get("/api/user")).data
    email.value = ""
    password.value = ""
    dialog.value = true
    router.push("/")
  }catch (error) {
    dialogerror.value = true
    return console.log("Error")
  }}
</script>

<script>
  export default {
    data: () => ({
      valid: false,
      password: '',
      passwordRules: [
        value => {
          if (value) return true

          return 'Parole ir obligāta.'
        },
      ],
      email: '',
      emailRules: [
        value => {
          if (value) return true

          return 'E-pasts ir obligāts.'
        },
        value => {
          if (/.+@.+\..+/.test(value)) return true

          return 'E-pastam jābūt derīgam.'
        },
      ],
    }),
  }
</script>

<template>
  <div class="page">
    <div class="background"><img alt="Fons" src="/backgrounds/login-background.png"></div>
    <div class="page-wrapper">
        <div class="login-form">
            <v-form v-model="valid" @submit.prevent="login()">
                <v-container>
                    <h1>Pieteikties</h1>

                    <v-col class="login-row" cols="12" >
                      <v-text-field
                        v-model="email"
                        :rules="emailRules"
                        label="E-pasts"
                        required
                      ></v-text-field>
                    </v-col>                   
                   
                    <v-col class="login-row" cols="12" >
                      <v-text-field
                        type="password"
                        v-model="password"
                        :rules="passwordRules"
                        label="Parole"
                        required
                      ></v-text-field>
                    </v-col>

                    <v-btn :disabled="!valid" type = "submit" class="login-button">PIETEIKTIES</v-btn>
                </v-container>
            </v-form>
        </div>
    </div>

    <v-dialog max-width="500" v-model="dialog">
        <v-card class="v-card" color="black">
          <v-card-title class="v-card-title">Pieteikšanās</v-card-title>
          <v-card-text class="v-card-text">
            Veiksmīgi pieteicies!
          </v-card-text>

          <v-card-actions>
            <v-spacer></v-spacer>

            <v-btn class="v-dialog-button" @click="dialog = false" >
              <p class="v-btn-text">Aizvērt</p>
            </v-btn>
          </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog max-width="500" v-model="dialogerror">
        <v-card class="v-card" color="black">
          <v-card-title class="v-card-title">Pieteikšanās kļūda</v-card-title>
          <v-card-text class="v-card-text">
            Lūdzu pārbaudi e-pastu un paroli un mēģini vēlreiz.
          </v-card-text>

          <v-card-actions>
            <v-spacer></v-spacer>

            <v-btn class="v-dialog-button" @click="dialogerror = false">
              <p class="v-btn-text">Aizvērt</p>
            </v-btn>
          </v-card-actions>
        </v-card>
    </v-dialog>
    </div>  
</template>

<style scoped>
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

      .v-card{
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.587);
      }

    h1{
        color:white;
        width: 100%;
        text-align: center;
    }

    .background {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
    }

    .login-form {
        width: 100%;
        max-width: 420px;
        min-height: 500px;
        display: flex;
        flex-direction: column;
        gap: 20px;

        padding: 40px;

        background: rgba(0, 0, 0, 0.452);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        backdrop-filter: blur(8px);

    }

    .login-row{
        color: white;
    }

    .page-wrapper {
        min-height: 70vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    

    .background::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.5);
    }

    .login-button {
        background: rgba(0, 0, 0, 0.452);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        color:rgb(255, 255, 255);
        width: 100%;
        margin-top: 30px;

    }

    .login-button:disabled{
        background: rgb(0, 0, 0);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        color:rgba(255, 255, 255, 0.228)
    }
</style>
