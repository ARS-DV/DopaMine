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

  // nickName no puede tener números
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

  const res = await fetch(rutaApi + '?entity=users', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({
      nickName: form.value.nickName,
      email:    form.value.email,
      pswd:     form.value.pswd
    })
  })
  const data = await res.json()

  if (data.status == 'success') {
    router.push('/login') // redirige al login para que haga sign in
  } else {
    error.value = 'Registration failed. Email may already be in use.'
  }
}
</script>

<template>
  <div>
    <h2>Sign Up</h2>

    <p v-if="error">{{ error }}</p>

    <form @submit.prevent="register">
      <div>
        <label for="inputNick">Nickname</label>
        <input
          id="inputNick"
          v-model="form.nickName"
          type="text"
          placeholder="Your nickname"
          required
          aria-required="true"
        >
      </div>

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
          placeholder="Min. 7 characters"
          required
          aria-required="true"
        >
      </div>

      <button type="submit">Create Account</button>
    </form>

    <p>Already have an account? <RouterLink to="/login">Sign In</RouterLink></p>
  </div>
</template>