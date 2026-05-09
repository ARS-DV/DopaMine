<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()

const error = ref('')

const form = ref({
  title:      '',
  descrip:    '',
  difficulty: 'medium',
  startDate:  '',
  startTime:  '',
  expDate:    '',
  expTime:    '',
})

// Items del checklist opcionales
const checklistItems = ref([])
const newItem        = ref('')

function addItem() {
  if (!newItem.value.trim()) return
  checklistItems.value.push({ title: newItem.value.trim() })
  newItem.value = ''
}

function removeItem(index) {
  checklistItems.value.splice(index, 1)
}

async function createTask() {
  error.value = ''

  if (!form.value.title.trim()) {
    error.value = 'Title is required'
    return
  }

  if (!form.value.expDate) {
    error.value = 'Due date is required'
    return
  }

  // Combinar fecha y hora en un datetime
  const startDate = form.value.startDate
    ? `${form.value.startDate} ${form.value.startTime || '00:00:00'}`
    : null

  const expDate = `${form.value.expDate} ${form.value.expTime || '23:59:00'}`

  const body = {
    user_id:    userStore.user.id,
    title:      form.value.title,
    descrip:    form.value.descrip || null,
    difficulty: form.value.difficulty,
    startDate:  startDate,
    expDate:    expDate,
  }

  const res  = await fetch(`${rutaApi}?entity=tasks`, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(body)
  })
  const data = await res.json()

  if (data.status === 'success') {
    // Si hay items de checklist los creamos uno a uno
    if (checklistItems.value.length > 0) {
      for (const item of checklistItems.value) {
        await fetch(`${rutaApi}?entity=task_checklist`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({
            task_id: data.id,
            title:   item.title
          })
        })
      }
    }
    router.push('/tasks')
  } else {
    error.value = 'Error creating task'
  }
}
</script>

<template>
  <div>

    <!-- PAN DE MIGA -->
    <nav>
      <RouterLink to="/">Home</RouterLink>
      <span> > </span>
      <RouterLink to="/tasks">Tasks</RouterLink>
      <span> > </span>
      <span>New Task</span>
    </nav>

    <h1>New Task</h1>

    <p v-if="error">{{ error }}</p>

    <form @submit.prevent="createTask">

      <!-- TÍTULO -->
      <div>
        <label>Title *</label>
        <input v-model="form.title" type="text" placeholder="Task name">
      </div>

      <!-- DESCRIPCIÓN -->
      <div>
        <label>Description</label>
        <input v-model="form.descrip" type="text" placeholder="Optional description">
      </div>

      <!-- DIFICULTAD -->
      <div>
        <label>Difficulty *</label>
        <label>
          <input type="radio" v-model="form.difficulty" value="easy"> Easy
        </label>
        <label>
          <input type="radio" v-model="form.difficulty" value="medium"> Medium
        </label>
        <label>
          <input type="radio" v-model="form.difficulty" value="hard"> Hard
        </label>
      </div>

      <!-- FECHA INICIO -->
      <div>
        <label>Start date (optional)</label>
        <input v-model="form.startDate" type="date">
        <input v-model="form.startTime" type="time">
      </div>

      <!-- FECHA VENCIMIENTO -->
      <div>
        <label>Due date *</label>
        <input v-model="form.expDate" type="date">
        <input v-model="form.expTime" type="time">
      </div>

      <!-- CHECKLIST OPCIONAL -->
      <div>
        <label>Checklist (optional)</label>

        <div>
          <input
            v-model="newItem"
            type="text"
            placeholder="Add a step..."
            @keydown.enter.prevent="addItem"
          >
          <button type="button" @click="addItem">Add</button>
        </div>

        <ul v-if="checklistItems.length > 0">
          <li v-for="(item, index) in checklistItems" :key="index">
            {{ item.title }}
            <button type="button" @click="removeItem(index)">✕</button>
          </li>
        </ul>
      </div>

      <!-- ACCIONES -->
      <button type="submit">Create Task</button>
      <button type="button" @click="router.push('/tasks')">Cancel</button>

    </form>

  </div>
</template>