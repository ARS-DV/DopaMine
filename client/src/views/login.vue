<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const router    = useRouter()
const userStore = useUserStore()

const form  = ref({ email: '', pswd: '' })
const error = ref('')

async function login() {
  error.value = ''

  if (!form.value.email || !form.value.pswd) {
    error.value = 'Please fill in all fields'
    return
  }

  if (!form.value.email.includes('@')) {
    error.value = 'Enter a valid email address'
    return
  }

  const res  = await fetch(rutaApi + '?entity=users&login', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ email: form.value.email, pswd: form.value.pswd })
  })
  const data = await res.json()

  if (data.status === 'success') {
    userStore.login(data.user)
    router.push('/')
  } else {
    error.value = 'Wrong email or password'
  }
}
</script>

<template>
  <div class="login-wrapper">
    <div class="login-card">

      <!-- LOGO -->
      <div class="login-logo">
        dopamine<em>·app</em>
      </div>

      <h1 class="login-title">Sign In</h1>
      <p class="login-sub">Welcome back 👋</p>

      <!-- ERROR -->
      <div v-if="error" class="error-text">
        {{ error }}
      </div>

      <!-- FORMULARIO -->
      <form @submit.prevent="login">

        <div class="mb-3">
          <label class="form-label-dopamine">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="form-control dopamine-input"
            placeholder="example@mail.com"
            required
          >
        </div>

        <div class="mb-4">
          <label class="form-label-dopamine">Password</label>
          <input
            v-model="form.pswd"
            type="password"
            class="form-control dopamine-input"
            placeholder="••••••••"
            required
          >
        </div>

        <button
          type="submit"
          class="btn-dopamine btn-dopamine-primary w-100"
        >
          Sign In
        </button>

      </form>

      <!-- LINK REGISTRO -->
      <p class="login-footer-text">
        Don't have an account?
        <RouterLink to="/singup">Sign Up</RouterLink>
      </p>

    </div>
  </div>
</template>

<style scoped>
.login-wrapper {
  min-height: 100vh;
  background-color: var(--bg-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.login-card {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 16px;
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 4px 24px rgba(92, 51, 23, 0.10);
}

.login-logo {
  font-family: var(--font-serif);
  font-size: 1.4rem;
  color: var(--cinnamon-dark);
  text-align: center;
  margin-bottom: 1.5rem;
}

.login-logo em {
  font-style: italic;
  font-weight: 300;
  color: var(--cinnamon-soft);
}

.login-title {
  font-family: var(--font-serif);
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  margin-bottom: 0.25rem;
}

.login-sub {
  font-size: 0.78rem;
  color: var(--cinnamon-soft);
  margin-bottom: 1.5rem;
}

.login-footer-text {
  text-align: center;
  font-size: 0.72rem;
  color: var(--cinnamon-soft);
  margin-top: 1.2rem;
  margin-bottom: 0;
}

.login-footer-text a {
  color: var(--cinnamon-dark);
  font-weight: 500;
  text-decoration: none;
}

.login-footer-text a:hover {
  text-decoration: underline;
}
</style>