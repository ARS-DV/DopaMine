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

  const res = await fetch(rutaApi + '?entity=users&login', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ email: form.value.email, pswd: form.value.pswd })
  })
  const data = await res.json()

  if (data.status == 'success') {
    userStore.login(data.user)
    router.push('/')
  } else {
    error.value = 'Wrong email or password'
  }
}
</script>

<template>
  <div>
    <h2>Sign In</h2>

    <p v-if="error">{{ error }}</p>

    <form @submit.prevent="login">
      <div>
        <label for="inputEmail">Email</label>
        <input
          id="inputEmail"
          v-model="form.email"
          type="email"
          placeholder="example@mail.com"
          required
          aria-required="true"
        >
      </div>

      <div>
        <label for="inputPswd">Password</label>
        <input
          id="inputPswd"
          v-model="form.pswd"
          type="password"
          placeholder="••••••••"
          required
          aria-required="true"
        >
      </div>

      <button type="submit">Sign In</button>
    </form>

    <p>Don't have an account? <RouterLink to="/singup">Sign Up</RouterLink></p>
  </div>
</template>