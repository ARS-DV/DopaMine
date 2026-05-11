<script setup>
// imports para Vue, router y API
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router = useRouter()

// variables reactivas
const tasks = ref([])
const loading = ref(true)
const error = ref('')
const filter = ref('all')

//funcion principal
async function fetchTasks() {
  loading.value = true
  error.value = ''
  
  let url = rutaApi + "?entity=tasks&user_id=" + userStore.user.id
  
  fetch(url)
    .then(res => res.json())
    .then(data => {
      tasks.value = data
      loading.value = false
    })
    .catch(err => {
      error.value = 'Error loading tasks'
      loading.value = false
    })
}

//para marcar o desmarcar la tarea
async function toggleDone(task) {
  // asignacion numerica para saber si esta hecha o no
  let newStatus = 0
  if (task.done == false || task.done == 0) {
    newStatus = 1
  }

  fetch(rutaApi + "?entity=tasks&id=" + task.id, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ done: newStatus })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      //actualizacion automatica ante tarea hecha
      if (newStatus == 1) {
        task.done = true
      } else {
        task.done = false
      }
    }
  })
}

// Borrar una tarea
async function deleteTask(id) {
  let check = confirm('Delete this task?')
  if (check === false) {
    return
  }

  fetch(rutaApi + "?entity=tasks&id=" + id, { method: 'DELETE' })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        tasks.value = tasks.value.filter(t => t.id !== id)
      } else {
        error.value = 'Error deleting task'
      }
    })
}

//funciones para comprobar fechas
async function isOverdue(task) {
  let today = new Date()
  let taskDate = new Date(task.expDate)
  return task.done == false && taskDate < today
}

async function isDueToday(task) {
  if (task.done === true) return false
  
  let today = new Date().toISOString().split('T')[0]
  let expDate = ""
  
  if (task.expDate) {
    expDate = task.expDate.split(' ')[0]
  }
  
  return expDate == today
}

// Formatear la fecha para que se vea bien
function formatDate(dateStr) {
  if (!dateStr) {
    return 'No deadline'
  }
  let date = new Date(dateStr)
  return date.toLocaleDateString('en-GB')
}

//filtros
const filteredTasks = computed(() => {
  if (filter.value === 'pending') {
    return tasks.value.filter(t => t.done == false)
  } else if (filter.value === 'done') {
    return tasks.value.filter(t => t.done == true)
  } else if (filter.value === 'hard') {
    return tasks.value.filter(t => t.difficulty == 'hard')
  } else if (filter.value === 'medium') {
    return tasks.value.filter(t => t.difficulty == 'medium')
  } else if (filter.value === 'easy') {
    return tasks.value.filter(t => t.difficulty == 'easy')
  } else if (filter.value === 'overdue') {
    return tasks.value.filter(t => isOverdue(t))
  } else {
    return tasks.value
  }
})

//contador de tareas por dificultad
function countByDiff(diff) {
  let lista = tasks.value.filter(t => t.difficulty === diff && t.done == false)
  return lista.length
}

onMounted(() => {
  fetchTasks()
})
</script>

<template>
  <div>
    <h1>Tasks</h1>

    <p v-if="error">{{ error }}</p>
    <p v-if="loading">Loading...</p>

    <template v-else>

      <button @click="router.push('/tasks/new')">+ New Task</button>

      <div>
        <span>Hard: {{ countByDiff('hard') }}</span>
        <span>Medium: {{ countByDiff('medium') }}</span>
        <span>Easy: {{ countByDiff('easy') }}</span>
      </div>

      <div>
        <button @click="filter = 'all'">All ({{ tasks.length }})</button>
        <button @click="filter = 'pending'">Pending</button>
        <button @click="filter = 'done'">Done</button>
        <button @click="filter = 'hard'">Hard</button>
        <button @click="filter = 'medium'">Medium</button>
        <button @click="filter = 'easy'">Easy</button>
        <button @click="filter = 'overdue'">Overdue</button>
      </div>

      <p v-if="filteredTasks.length === 0">No tasks found</p>

      <ul v-else>
        <li v-for="task in filteredTasks" :key="task.id">

          <input
            type="checkbox"
            :checked="task.done"
            @change="toggleDone(task)"
          >

          <span>{{ task.title }}</span>
          <span>[{{ task.difficulty }}]</span>
          <span>Due: {{ formatDate(task.expDate) }}</span>

          <span v-if="task.done">Done</span>
          <span v-else-if="isOverdue(task)">Overdue</span>
          <span v-else-if="isDueToday(task)">Due today</span>

          <span v-if="task.descrip"> — {{ task.descrip }}</span>

          <button @click="deleteTask(task.id)">Delete</button>

        </li>
      </ul>

    </template>
  </div>
</template>