<script setup>
// imports necesarios para Vue, router y API
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore' 
import { rutaApi } from '@/config.js'

const router = useRouter()
const userStore = useUserStore()

// variables reactivas para el login
const emailInput = ref('')
const passwordInput = ref('')
const errorMessage = ref('')

//funcion principal
function loginUser() {
  errorMessage.value = ''

  //validaciones
  if (emailInput.value == '' || passwordInput.value == '') {
    errorMessage.value = 'Please fill in all fields'
    return
  }
  if (emailInput.value.includes('@') === false) {
    errorMessage.value = 'Enter a valid email address'
    return
  }

  //variable para guardar los datos a pasar al backend
  let loginData = {
    email: emailInput.value,
    pswd: passwordInput.value
  }

  //peticion fetch a la API con POST
  fetch(rutaApi + '?entity=users&login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(loginData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.status == 'success') {
      userStore.login(data.user)
      router.push('/')
    } else {
      errorMessage.value = 'Wrong email or password'
    }
  })
  .catch(error => {
    console.error('Error:', error)
    errorMessage.value = 'Connection error'
  })
}
</script>

<template>
  <div>
    <div>

      <h1>Sign In</h1>
      <p>Welcome back</p>

      <div v-if="errorMessage">
        <b style="color: red;">{{ errorMessage }}</b>
      </div>

      <form @submit.prevent="loginUser">

        <div>
          <label>Email</label><br>
          <input
            v-model="emailInput"
            type="email"
            placeholder="example@mail.com"
          >
        </div>

        <br>

        <div>
          <label>Password</label><br>
          <input
            v-model="passwordInput"
            type="password"
            placeholder="••••••••"
          >
        </div>

        <br>

        <button type="submit">
          Sign In
        </button>

      </form>

      <br>

      <p>
        Don't have an account?
        <RouterLink to="/singup">Sign Up</RouterLink>
      </p>

    </div>
  </div>
</template>