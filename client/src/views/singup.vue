<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { rutaApi } from '@/config.js'

const router = useRouter()

const form  = ref({ nickName: '', email: '', pswd: '' })
const error = ref('')

async function register() {
  error.value = ''

  if (!form.value.nickName || !form.value.email || !form.value.pswd) {
    error.value = 'Please fill in all fields'
    return
  }

  if (/\d/.test(form.value.nickName)) {
    error.value = 'Nickname cannot contain numbers'
    return
  }

  if (!form.value.email.includes('@')) {
    error.value = 'Enter a valid email address'
    return
  }

  if (form.value.pswd.length < 7) {
    error.value = 'Password must be at least 7 characters'
    return
  }

  const res  = await fetch(rutaApi + '?entity=users', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({
      nickName: form.value.nickName,
      email:    form.value.email,
      pswd:     form.value.pswd
    })
  })
  const data = await res.json()

  if (data.status === 'success') {
    router.push('/login')
  } else {
    error.value = 'Registration failed. Email may already be in use.'
  }
}
</script>

<template>
  <div class="singup-wrapper">
    <div class="singup-card">

      <!-- LOGO -->
      <div class="singup-logo">
        dopamine<em>·app</em>
      </div>

      <h1 class="singup-title">Sign Up</h1>
      <p class="singup-sub">Start building better habits 🌱</p>

      <!-- ERROR -->
      <div v-if="error" class="error-text">
        {{ error }}
      </div>

      <!-- FORMULARIO -->
      <form @submit.prevent="register">

        <div class="mb-3">
          <label class="form-label-dopamine">Nickname</label>
          <input
            v-model="form.nickName"
            type="text"
            class="form-control dopamine-input"
            placeholder="Your nickname"
            required
          >
        </div>

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
            placeholder="Min. 7 characters"
            required
          >
        </div>

        <button
          type="submit"
          class="btn-dopamine btn-dopamine-primary w-100"
        >
          Create Account
        </button>

      </form>

      <!-- LINK LOGIN -->
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