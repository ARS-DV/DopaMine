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
  <div class="auth-wrapper">
    <div class="auth-card">

      <!-- FRANJA DECORATIVA SUPERIOR -->
      <div class="auth-top-strip">
        <i class="bi bi-lightning-charge-fill auth-deco-icon"></i>
      </div>

      <div class="auth-body">

        <!-- LOGO -->
        <div class="auth-logo">
          DopaMine
        </div>

        <!-- TÍTULO -->
        <h1 class="auth-title">Sign In</h1>
        <p class="auth-sub">
          <i class="bi bi-hand-wave me-1"></i> Welcome back :)
        </p>

        <!-- ERROR -->
        <div v-if="errorMessage" class="error-text mb-3">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <strong>{{ errorMessage }}</strong>
        </div>

        <!-- FORMULARIO -->
        <form @submit.prevent="loginUser">

          <div class="mb-3">
            <label class="auth-label">
              <i class="bi bi-envelope me-1"></i> Email
            </label>
            <input
              v-model="emailInput"
              type="email"
              class="form-control dopamine-input"
              placeholder="example@mail.com"
            >
          </div>

          <div class="mb-4">
            <label class="auth-label">
              <i class="bi bi-lock me-1"></i> Password
            </label>
            <input
              v-model="passwordInput"
              type="password"
              class="form-control dopamine-input"
              placeholder="••••••••"
            >
          </div>

          <button type="submit" class="btn-dopamine btn-dopamine-primary w-100 auth-btn">
            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
          </button>

        </form>

        <!-- LINK REGISTRO -->
        <p class="auth-footer-text">
          Don't have an account?
          <RouterLink to="/singup">Sign Up</RouterLink>
        </p>

      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-wrapper {
  min-height: 100vh;
  background-color: var(--bg-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.auth-card {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 18px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 6px 32px rgba(92, 51, 23, 0.12);
  overflow: hidden;
}

/* FRANJA SUPERIOR */
.auth-top-strip {
  background: var(--cinnamon-dark);
  height: 58px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.auth-deco-icon {
  font-size: 1.7rem;
  color: var(--vanilla-light);
}

/* CUERPO */
.auth-body {
  padding: 1.8rem 2rem 2rem;
}

.auth-logo {
  font-family: var(--font-serif);
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  text-align: center;
  margin-bottom: 1.4rem;
}

.auth-logo em {
  font-style: italic;
  font-weight: 300;
  color: var(--cinnamon-soft);
}

.auth-title {
  font-family: 'Atkinson Hyperlegible', var(--font-serif), sans-serif;
  font-size: 2rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  margin-bottom: 0.2rem;
  letter-spacing: -0.3px;
}

.auth-sub {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--cinnamon-soft);
  margin-bottom: 1.5rem;
}

.auth-label {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  display: block;
  margin-bottom: 0.45rem;
}

.auth-btn {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  padding: 0.75rem 1rem;
  min-height: 48px;
}

.auth-footer-text {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--cinnamon-soft);
  text-align: center;
  margin-top: 1.3rem;
  margin-bottom: 0;
}

.auth-footer-text a {
  color: var(--cinnamon-dark);
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 3px;
}

.auth-footer-text a:hover {
  color: var(--cinnamon-mid);
}
</style>