import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUserStore = defineStore('user', () => {
  const user = ref(JSON.parse(localStorage.getItem('session')) || null)

  function login(userData) {
    user.value = userData
    localStorage.setItem('session', JSON.stringify(userData))
  }

  function logout() {
    user.value = null
    localStorage.removeItem('session')
  }

  function updateUser(newData) {
    user.value = { ...user.value, ...newData }
    localStorage.setItem('session', JSON.stringify(user.value))
  }

  const isLogged = () => !!user.value
  const isAdmin  = () => user.value?.role === 'admin'

  return { user, login, logout, isLogged, isAdmin, updateUser }
})







