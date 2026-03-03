<script setup>
  import { ref } from 'vue'

  const email = ref("")
  const username = ref("")
  const password = ref("")
  const valid = ref(false)
  async function register() {
    if (valid.value === true){
      await fetch("http://localhost:8000/api/register", {method: "POST", headers: {"Content-Type": "application/json"}, 
      body: JSON.stringify({email: email.value, username: username.value, password: password.value})})
      console.log("Success")
    }
    else (
      alert("Error")
    )
  }
</script>

<script>
  export default {
    data: () => ({
      valid: false,
      username: '',
        usernameRules: [
            value => {
            if (value) return true
            return 'Username is required.'
            },

            value => {
            if (value?.length <= 10) return true
            return 'Username must be less than 10 characters.'
            },

            async value => {
              const response = await (await fetch("http://localhost:8000/api/usernamecheck", {method: "POST", headers: {"Content-Type": "application/json"}, body: JSON.stringify({username: value})})).json()
              if (response.exists === true){
                return "Username already in use."
              }
              return true
            }
        ],
      password: '',
      passwordRules: [
        value => {
          if (value) return true
          return 'Password is required.'
        },
        value => {
            if (value?.length >= 8) return true
            return 'Password must be at least 8 characters.'
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
        async value => {
              const response = await (await fetch("http://localhost:8000/api/emailcheck", {method: "POST", headers: {"Content-Type": "application/json"}, body: JSON.stringify({email: value})})).json()
              if (response.exists === true){
                return "Email already in use."
              }
              return true
        }
      ],
      
    }),
  }
</script>

<template>
    <div class="background"><img src="/backgrounds/register-background.png"></div>
    <div class="page-wrapper">
        <div class="register-form">
            <v-form v-model="valid" @submit.prevent="register">
                <v-container>
                    <h1>Register</h1>

                    <v-col class="register-row" cols="12" >
                      <v-text-field
                        v-model="username"
                        :rules="usernameRules"
                        :counter="10"
                        label="Username"
                        required
                      ></v-text-field>
                    </v-col>
                    

                    <v-col class="register-row" cols="12" >
                      <v-text-field
                        v-model="email"
                        :rules="emailRules"
                        label="E-mail"
                        required
                      ></v-text-field>
                    </v-col>                   
                   
                    <v-col class="register-row" cols="12" >
                      <v-text-field
                        v-model="password"
                        :rules="passwordRules"
                        label="Password"
                        required
                      ></v-text-field>
                    </v-col>

                    <v-btn type="submit" :disabled="!valid" class="register-button">CREATE ACCOUNT</v-btn>
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
        font-size: 30px;
    }

    .background {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
    }

    .register-form {
        width: 100%;
        max-width: 420px;
        min-height: 500px;
        display: flex;
        flex-direction: column;

        padding: 40px;

        background: rgba(0, 0, 0, 0.452);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        backdrop-filter: blur(8px);

    }

    .register-row {
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

    .register-button {
        background: rgba(0, 0, 0, 0.452);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        color:rgb(255, 255, 255);
        width: 100%;
        margin-top: 30px;

    }

    .register-button:disabled{
        background: rgb(0, 0, 0);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        color:rgba(255, 255, 255, 0.228)
    }
</style>
