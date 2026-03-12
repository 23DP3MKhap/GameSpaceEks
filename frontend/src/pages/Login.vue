<script setup>
  import { ref } from 'vue'
  import axios from '../plugins/axios'
  const email = ref("")
  const password = ref("")
  const valid = ref(false)
  async function login(){
    if (valid.value === false){
      return alert("Error")
    }
    await axios.get('/sanctum/csrf-cookie')
    const loginstatus = await axios.post("/login", {email: email.value, password: password.value})
    return console.log(loginstatus.data.message)
  }
</script>

<script>
  export default {
    data: () => ({
      valid: false,
      password: '',
      passwordRules: [
        value => {
          if (value) return true

          return 'Password is required.'
        },
      ],
      email: '',
      emailRules: [
        value => {
          if (value) return true

          return 'E-mail is required.'
        },
        value => {
          if (/.+@.+\..+/.test(value)) return true

          return 'E-mail must be valid.'
        },
      ],
    }),
  }
</script>

<template>
    <div class="background"><img src="/backgrounds/login-background.png"></div>
    <div class="page-wrapper">
        <div class="login-form">
            <v-form v-model="valid" @submit.prevent="login()">
                <v-container>
                    <h1>Log in</h1>

                    <v-col class="login-row" cols="12" >
                      <v-text-field
                        v-model="email"
                        :rules="emailRules"
                        label="E-mail"
                        required
                      ></v-text-field>
                    </v-col>                   
                   
                    <v-col class="login-row" cols="12" >
                      <v-text-field
                        v-model="password"
                        :counter="10"
                        :rules="passwordRules"
                        label="Password"
                        required
                      ></v-text-field>
                    </v-col>

                    <v-btn :disabled="!valid" type = "submit" class="login-button">LOG IN</v-btn>
                </v-container>
            </v-form>
        </div>
    </div>
</template>

<style scoped>

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
