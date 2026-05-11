<script setup>
// imports para Vue, router y API
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router = useRouter()

const error = ref('')

//variables reactivas
const titleInput = ref('')
const descripInput = ref('')
const difficultyInput = ref('medium')
const startDateInput = ref('')
const startTimeInput = ref('')
const expDateInput = ref('')
const expTimeInput = ref('')

//variables reactivas para las subtareas
const checklistItems = ref([])
const newItemTitle = ref('')

//funcion para añadir pasos
async function addItem() {
  if (newItemTitle.value.trim() == "") {
    return
  }
  //se añade el objeto al array
  let item = { title: newItemTitle.value.trim() }
  checklistItems.value.push(item)
  newItemTitle.value = ''
}

//quitar un paso de la lista
async function removeItem(index) {
  checklistItems.value.splice(index, 1)
}

//funcion principal
async function createNewTask() {
  error.value = ''

  //validaciones
  if (titleInput.value.trim() == "") {
    error.value = 'Title is required'
    return
  }

  if (expDateInput.value == "") {
    error.value = 'Due date is required'
    return
  }

  //se juntan fecha y hora como está puesto en la API
  let fullStartDate = null
  if (startDateInput.value !== "") {
    let timeStart = startTimeInput.value
    if (timeStart == "") {
      timeStart = "00:00:00"
    }
    fullStartDate = startDateInput.value + " " + timeStart
  }

  let timeExp = expTimeInput.value
  if (timeExp == "") {
    timeExp = "23:59:00"
  }
  let fullExpDate = expDateInput.value + " " + timeExp

  //objetos con datos para el backend
  let taskData = {
    user_id: userStore.user.id,
    title: titleInput.value,
    descrip: descripInput.value || null,
    difficulty: difficultyInput.value,
    startDate: fullStartDate,
    expDate: fullExpDate,
  }

  //fetch para guardar tareas con metodo POST
  fetch(rutaApi + "?entity=tasks", {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(taskData)
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      //si se guarda bien, ahora guardamos sus checklist
      let taskId = data.id
      
      for (let i = 0; i < checklistItems.value.length; i++) {
        let item = checklistItems.value[i]
        
        fetch(rutaApi + "?entity=task_checklist", {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            task_id: taskId,
            title: item.title
          })
        })
      }
      router.push('/tasks')
    } else {
      error.value = 'Error creating task'
    }
  })
  .catch(err => {
    console.error(err)
    error.value = 'Connection error'
  })
}
</script>

<template>
  <div>

    <nav>
      <RouterLink to="/">Home</RouterLink>
      <span> > </span>
      <RouterLink to="/tasks">Tasks</RouterLink>
      <span> > </span>
      <span>New Task</span>
    </nav>

    <h1>New Task</h1>

    <p v-if="error" style="color: red;">{{ error }}</p>

    <form @submit.prevent="createNewTask">

      <div>
        <label>Title *</label><br>
        <input v-model="titleInput" type="text" placeholder="Task name">
      </div>

      <br>

      <div>
        <label>Description</label><br>
        <input v-model="descripInput" type="text" placeholder="Optional description">
      </div>

      <br>

      <div>
        <label>Difficulty *</label><br>
        <label>
          <input type="radio" v-model="difficultyInput" value="easy"> Easy
        </label>
        <label>
          <input type="radio" v-model="difficultyInput" value="medium"> Medium
        </label>
        <label>
          <input type="radio" v-model="difficultyInput" value="hard"> Hard
        </label>
      </div>

      <br>

      <div>
        <label>Start date </label><br>
        <input v-model="startDateInput" type="date">
        <input v-model="startTimeInput" type="time">
      </div>

      <br>

      <div>
        <label>Due date *</label><br>
        <input v-model="expDateInput" type="date">
        <input v-model="expTimeInput" type="time">
      </div>

      <br>

      <div>
        <label>Checklist </label><br>

        <div>
          <input
            v-model="newItemTitle"
            type="text"
            placeholder="Add a step..."
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

      <br>

      <button type="submit">Create Task</button>
      <button type="button" @click="router.push('/tasks')">Cancel</button>

    </form>

  </div>
</template>