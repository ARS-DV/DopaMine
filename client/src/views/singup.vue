<script setup>
// imports necesarios de Vue y el router
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { rutaApi } from '@/config.js'

const router = useRouter()

// variables reactivas del formulario
const nickNameInput = ref('')
const emailInput = ref('')
const passwordInput = ref('')
const errorMessage = ref('')

//funcion principal
async function registerUser() {
  errorMessage.value = ''

  //validaciones
  if (nickNameInput.value == '' || emailInput.value == '' || passwordInput.value == '') {
    errorMessage.value = 'Please fill in all fields'
    return
  }
  let hasNumber = false;
  let numbers = "0123456789";
  for (let i = 0; i < nickNameInput.value.length; i++) {
    if (numbers.includes(nickNameInput.value[i])) {
      hasNumber = true;
    }
  }
  
  if (hasNumber == true) {
    errorMessage.value = 'Nickname cannot contain numbers'
    return
  }

  //validacion email
  if (emailInput.value.includes('@') == false) {
    errorMessage.value = "Enter a valid email address"
    return
  }

  //validacion contraseña
  //TODO: requerir una contraseña más compleja
  if (passwordInput.value.length < 7) {
    errorMessage.value = 'Password must be at least 7 characters'
    return
  }

  //variable objeto para el backend
  let userData = {
    nickName: nickNameInput.value,
    email: emailInput.value,
    pswd: passwordInput.value
  }

  // peticion fetch a la API con Post
  fetch(rutaApi + '?entity=users', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(userData)
  })
  .then(response => response.json())
  .then(data => {
    //si todo correcto, nos devuelve al login
    if (data.status === 'success') {
      router.push('/login')
    } else {
      errorMessage.value = 'Registration failed. Email may already be in use.'
    }
  })
  .catch(error => {
    console.error('Error:', error)
    errorMessage.value = 'Connection error'
  })
}
</script>

<template>
  <div class="singup-wrapper">
    <div class="singup-card">

      <div class="singup-logo">
        dopamine<em>·app</em>
      </div>

      <h1 class="singup-title">Sign Up</h1>
      <p class="singup-sub">Start building better habits 🌱</p>

      <div v-if="errorMessage" class="error-text">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="registerUser">

        <div class="mb-3">
          <label class="form-label-dopamine">Nickname</label>
          <input
            v-model="nickNameInput"
            type="text"
            class="form-control dopamine-input"
            placeholder="Your nickname"
          >
        </div>

        <div class="mb-3">
          <label class="form-label-dopamine">Email</label>
          <input
            v-model="emailInput"
            type="email"
            class="form-control dopamine-input"
            placeholder="example@mail.com"
          >
        </div>

        <div class="mb-4">
          <label class="form-label-dopamine">Password</label>
          <input
            v-model="passwordInput"
            type="password"
            class="form-control dopamine-input"
            placeholder="Min. 7 characters"
          >
        </div>

        <button
          type="submit"
          class="btn-dopamine btn-dopamine-primary w-100"
        >
          Create Account
        </button>

      </form>

      <p class="singup-footer-text">
        Already have an account?
        <RouterLink to="/login">Sign In</RouterLink>
      </p>

    </div>
  </div>
</template>

<style scoped>
.singup-wrapper {
  min-height: 100vh;
  background-color: var(--bg-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.singup-card {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 16px;
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 4px 24px rgba(92, 51, 23, 0.10);
}

.singup-logo {
  font-family: var(--font-serif);
  font-size: 1.4rem;
  color: var(--cinnamon-dark);
  text-align: center;
  margin-bottom: 1.5rem;
}

.singup-logo em {
  font-style: italic;
  font-weight: 300;
  color: var(--cinnamon-soft);
}

.singup-title {
  font-family: var(--font-serif);
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  margin-bottom: 0.25rem;
}

.singup-sub {
  font-size: 0.78rem;
  color: var(--cinnamon-soft);
  margin-bottom: 1.5rem;
}

.singup-footer-text {
  text-align: center;
  font-size: 0.72rem;
  color: var(--cinnamon-soft);
  margin-top: 1.2rem;
  margin-bottom: 0;
}

.singup-footer-text a {
  color: var(--cinnamon-dark);
  font-weight: 500;
  text-decoration: none;
}

.singup-footer-text a:hover {
  text-decoration: underline;
}
</style>