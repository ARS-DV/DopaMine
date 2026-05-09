<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()

const tasks   = ref([])
const loading = ref(true)
const error   = ref('')
const filter  = ref('all')

async function fetchTasks() {
  loading.value = true
  error.value   = ''
  try {
    const res  = await fetch(`${rutaApi}?entity=tasks&user_id=${userStore.user.id}`)
    const data = await res.json()
    tasks.value = data
  } catch (e) {
    error.value = 'Error loading tasks'
  } finally {
    loading.value = false
  }
}

async function toggleDone(task) {
  const newDone = task.done ? 0 : 1
  const res  = await fetch(`${rutaApi}?entity=tasks&id=${task.id}`, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ done: newDone })
  })
  const data = await res.json()
  if (data.status === 'success') task.done = !!newDone
}

async function deleteTask(id) {
  if (!confirm('Delete this task?')) return
  const res  = await fetch(`${rutaApi}?entity=tasks&id=${id}`, { method: 'DELETE' })
  const data = await res.json()
  if (data.status === 'success') {
    tasks.value = tasks.value.filter(t => t.id !== id)
  } else {
    error.value = 'Error deleting task'
  }
}

function isOverdue(task) {
  return !task.done && new Date(task.expDate) < new Date()
}

function isDueToday(task) {
  if (task.done) return false
  const today   = new Date().toISOString().split('T')[0]
  const expDate = task.expDate?.split('T')[0] ?? task.expDate?.split(' ')[0]
  return expDate === today
}

function formatDate(dateStr) {
  if (!dateStr) return 'No deadline'
  return new Date(dateStr).toLocaleDateString('en-GB')
}

const filteredTasks = computed(() => {
  switch (filter.value) {
    case 'pending': return tasks.value.filter(t => !t.done)
    case 'done':    return tasks.value.filter(t => t.done)
    case 'easy':    return tasks.value.filter(t => t.difficulty === 'easy')
    case 'medium':  return tasks.value.filter(t => t.difficulty === 'medium')
    case 'hard':    return tasks.value.filter(t => t.difficulty === 'hard')
    case 'overdue': return tasks.value.filter(t => isOverdue(t))
    default:        return tasks.value
  }
})

const countByDiff = (diff) => tasks.value.filter(t => t.difficulty === diff && !t.done).length

onMounted(() => fetchTasks())
</script>

<template>
  <div>
    <h1>Tasks</h1>

    <p v-if="error">{{ error }}</p>
    <p v-if="loading">Loading...</p>

    <template v-else>

      <button @click="router.push('/tasks/new')">+ New Task</button>

      <!-- STRIP DIFICULTAD -->
      <div>
        <span>Hard: {{ countByDiff('hard') }}</span>
        <span>Medium: {{ countByDiff('medium') }}</span>
        <span>Easy: {{ countByDiff('easy') }}</span>
      </div>

      <!-- FILTROS -->
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