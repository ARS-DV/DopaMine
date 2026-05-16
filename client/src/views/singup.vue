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
const showPassword = ref(false)

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
  <div class="auth-wrapper">
    <div class="auth-card">

      <!-- FRANJA DECORATIVA SUPERIOR -->
      <div class="auth-top-strip" aria-hidden="true">
        <i class="bi bi-stars auth-deco-icon"></i>
      </div>

      <div class="auth-body">

        <!-- LOGO -->
        <div class="auth-logo" aria-hidden="true">
          Dopa<em>Mine</em>
        </div>

        <!-- TÍTULO -->
        <h1 class="auth-title">Sign Up</h1>
        <p class="auth-sub">
          <i class="bi bi-rocket-takeoff me-1" aria-hidden="true"></i> Start building better habits
        </p>

        <!-- ERROR -->
        <div v-if="errorMessage" class="error-text mb-3" role="alert" aria-live="assertive">
          <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
          <strong>{{ errorMessage }}</strong>
        </div>

        <!-- FORMULARIO -->
        <form @submit.prevent="registerUser" novalidate>

          <!-- NICKNAME -->
          <div class="mb-3">
            <label for="signup-nickname" class="auth-label">
              <i class="bi bi-person me-1" aria-hidden="true"></i> Nickname
            </label>
            <input
              id="signup-nickname"
              v-model="nickNameInput"
              type="text"
              class="form-control dopamine-input"
              placeholder="Your nickname"
              aria-required="true"
              autocomplete="nickname"
              aria-describedby="nickname-hint"
            >
            <p id="nickname-hint" class="field-hint mt-1">No numbers allowed</p>
          </div>

          <!-- EMAIL -->
          <div class="mb-3">
            <label for="signup-email" class="auth-label">
              <i class="bi bi-envelope me-1" aria-hidden="true"></i> Email
            </label>
            <input
              id="signup-email"
              v-model="emailInput"
              type="email"
              class="form-control dopamine-input"
              placeholder="example@mail.com"
              aria-required="true"
              autocomplete="email"
            >
          </div>

          <!-- PASSWORD -->
          <div class="mb-4">
            <label for="signup-password" class="auth-label">
              <i class="bi bi-lock me-1" aria-hidden="true"></i> Password
              <span class="auth-label-hint">min. 7 characters</span>
            </label>
            <div class="pswd-wrapper">
              <input
                id="signup-password"
                v-model="passwordInput"
                :type="showPassword ? 'text' : 'password'"
                class="form-control dopamine-input"
                placeholder="••••••••"
                aria-required="true"
                autocomplete="new-password"
              >
              <button
                type="button"
                class="pswd-toggle"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                :aria-pressed="showPassword"
                @click="showPassword = !showPassword"
              >
                <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
              </button>
            </div>
          </div>

          <button
            type="submit"
            class="btn-dopamine btn-dopamine-primary w-100 auth-btn"
          >
            <i class="bi bi-person-plus me-2" aria-hidden="true"></i> Create Account
          </button>

        </form>

        <!-- LINK LOGIN -->
        <p class="auth-footer-text">
          Already have an account?
          <RouterLink to="/login">Sign In</RouterLink>
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
  display: flex;
  align-items: center;
  gap: 0.3rem;
  margin-bottom: 0.45rem;
}

.auth-label-hint {
  font-size: 0.75rem;
  font-weight: 400;
  color: var(--cinnamon-soft);
  margin-left: auto;
}

.field-hint {
  font-size: 0.78rem;
  color: var(--cinnamon-soft);
  margin: 0;
}

/* CONTRASEÑA CON OJO */
.pswd-wrapper { position: relative; }

.pswd-wrapper .dopamine-input { padding-right: 3rem !important; }

.pswd-toggle {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--cinnamon-soft);
  font-size: 1.1rem;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: color 0.15s;
}

.pswd-toggle:hover { color: var(--cinnamon-dark); }

.pswd-toggle:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 2px;
}

.auth-btn {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  padding: 0.75rem 1rem;
  min-height: 48px;
}

.auth-btn:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 3px;
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

.auth-footer-text a:hover { color: var(--cinnamon-mid); }
</style>