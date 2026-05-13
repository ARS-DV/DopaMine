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

// ✅ sin async — devuelven boolean directo para que funcionen en template y filtros
function isOverdue(task) {
  let today = new Date()
  let taskDate = new Date(task.expDate)
  return task.done == false && taskDate < today
}

function isDueToday(task) {
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
  <div class="tasks-container">

    <!-- CABECERA -->
    <div class="d-flex align-items-end justify-content-between mb-4 fade-up">
      <h1 class="page-title">
        <em>manage your</em>
        Tasks
      </h1>
      <button class="btn-dopamine btn-dopamine-primary" @click="router.push('/tasks/new')">
        <i class="bi bi-plus me-1"></i> New Task
      </button>
    </div>

    <!-- ERROR -->
    <div v-if="error" class="error-text mb-3">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ error }}
    </div>

    <!-- LOADING -->
    <div v-if="loading" class="loading-text">
      <div class="spinner-border spinner-border-sm me-2" role="status"></div>
      Loading...
    </div>

    <template v-else>

      <!-- STATS STRIP -->
      <div class="row g-3 mb-4 fade-up delay-1">
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-hard">
            <div>
              <div class="stat-num">{{ countByDiff('hard') }}</div>
              <div class="stat-label">Hard pending</div>
            </div>
            <i class="bi bi-fire stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-warn">
            <div>
              <div class="stat-num">{{ countByDiff('medium') }}</div>
              <div class="stat-label">Medium pending</div>
            </div>
            <i class="bi bi-dash-circle stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-ok">
            <div>
              <div class="stat-num">{{ countByDiff('easy') }}</div>
              <div class="stat-label">Easy pending</div>
            </div>
            <i class="bi bi-circle stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-neutral">
            <div>
              <div class="stat-num">{{ tasks.length }}</div>
              <div class="stat-label">Total tasks</div>
            </div>
            <i class="bi bi-list-check stat-icon ms-3"></i>
          </div>
        </div>
      </div>

      <!-- FILTROS -->
      <div class="d-flex flex-wrap gap-2 mb-4 fade-up delay-2">
        <button class="filter-tab" :class="filter === 'all'     ? 'active' : ''" @click="filter = 'all'">All ({{ tasks.length }})</button>
        <button class="filter-tab" :class="filter === 'pending' ? 'active' : ''" @click="filter = 'pending'">Pending</button>
        <button class="filter-tab" :class="filter === 'done'    ? 'active' : ''" @click="filter = 'done'">Done</button>
        <button class="filter-tab" :class="filter === 'hard'    ? 'active' : ''" @click="filter = 'hard'">Hard</button>
        <button class="filter-tab" :class="filter === 'medium'  ? 'active' : ''" @click="filter = 'medium'">Medium</button>
        <button class="filter-tab" :class="filter === 'easy'    ? 'active' : ''" @click="filter = 'easy'">Easy</button>
        <button class="filter-tab" :class="filter === 'overdue' ? 'active' : ''" @click="filter = 'overdue'">Overdue</button>
      </div>

      <!-- LISTA VACÍA -->
      <div v-if="filteredTasks.length === 0" class="empty-state fade-up">
        <i class="bi bi-check-all empty-icon"></i>
        <p class="empty-title">No tasks found</p>
        <button class="btn-dopamine btn-dopamine-primary mt-2" @click="router.push('/tasks/new')">
          <i class="bi bi-plus me-1"></i> Create your first task
        </button>
      </div>

      <!-- LISTA DE TAREAS -->
      <div v-else class="d-flex flex-column gap-3">
        <div
          v-for="(task, index) in filteredTasks"
          :key="task.id"
          class="task-card fade-up"
          :class="task.done ? 'card-done' : task.difficulty === 'hard' ? 'card-hard' : task.difficulty === 'medium' ? 'card-medium' : 'card-easy'"
          :style="{ animationDelay: (index * 0.04) + 's' }"
        >
          <div class="task-card-body">

            <!-- IZQUIERDA: checkbox + info -->
            <div class="d-flex align-items-start gap-3 flex-grow-1">

              <!-- CHECKBOX -->
              <div
                class="task-check"
                :class="task.done ? 'task-check-done' : ''"
                @click="toggleDone(task)"
              >
                <i v-if="task.done" class="bi bi-check"></i>
              </div>

              <!-- INFO -->
              <div class="flex-grow-1" style="min-width:0">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                  <span
                    class="task-title"
                    :class="task.done ? 'text-decoration-line-through task-title-done' : ''"
                  >
                    {{ task.title }}
                  </span>
                  <span class="bdg" :class="'bdg-' + task.difficulty">
                    {{ task.difficulty }}
                  </span>
                  <span v-if="task.done" class="bdg bdg-done">
                    <i class="bi bi-check me-1"></i>Done
                  </span>
                  <span v-else-if="isOverdue(task)" class="bdg bdg-overdue">
                    <i class="bi bi-exclamation-triangle me-1"></i>Overdue
                  </span>
                  <span v-else-if="isDueToday(task)" class="bdg bdg-due">
                    <i class="bi bi-clock me-1"></i>Due today
                  </span>
                </div>

                <!-- DESCRIPCIÓN -->
                <p v-if="task.descrip" class="task-descrip">{{ task.descrip }}</p>

                <!-- FECHA -->
                <div class="task-date">
                  <i class="bi bi-calendar3 me-1"></i>
                  {{ formatDate(task.expDate) }}
                </div>
              </div>
            </div>

            <!-- DERECHA: botón borrar -->
            <button
              class="btn-dopamine btn-dopamine-danger task-delete-btn"
              @click="deleteTask(task.id)"
              title="Delete task"
            >
              <i class="bi bi-trash"></i>
            </button>

          </div>
        </div>
      </div>

    </template>

    <!-- FAB -->
    <button class="fab" @click="router.push('/tasks/new')" title="New task">
      <i class="bi bi-plus"></i>
    </button>

  </div>
</template>

<style scoped>
.tasks-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
}

@media (max-width: 768px) {
  .tasks-container { padding: 1.5rem 1rem 5rem; }
}

/* TASK CARD */
.task-card {
  border-radius: 12px;
  border: 1.5px solid var(--vanilla-mid);
  border-left: 4px solid var(--vanilla-mid);
  background: var(--bg-base);
  box-shadow: 0 2px 8px rgba(92, 51, 23, 0.07);
  transition: box-shadow 0.15s;
}

.task-card:hover {
  box-shadow: 0 4px 16px rgba(92, 51, 23, 0.11);
}

.card-hard   { border-left-color: var(--state-error); }
.card-medium { border-left-color: var(--state-warn); }
.card-easy   { border-left-color: var(--state-ok); }
.card-done   {
  border-left-color: var(--state-ok);
  background: var(--state-ok-bg);
  opacity: 0.82;
}

.task-card-body {
  padding: 1.1rem 1.3rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

/* TÍTULO */
.task-title {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
}

.task-title-done { color: var(--cinnamon-soft); }

/* DESCRIPCIÓN */
.task-descrip {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.85rem;
  color: var(--cinnamon-soft);
  margin: 0.25rem 0 0;
}

/* FECHA */
.task-date {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.82rem;
  color: var(--cinnamon-soft);
  margin-top: 0.4rem;
  display: flex;
  align-items: center;
}

/* CHECKBOX */
.task-check {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 2px solid var(--vanilla-mid);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 0.9rem;
  flex-shrink: 0;
  margin-top: 2px;
}

.task-check:hover        { border-color: var(--cinnamon-mid); }
.task-check-done         { background: var(--state-ok); border-color: var(--state-ok); color: #fff; }

/* BOTÓN BORRAR */
.task-delete-btn {
  width: 44px;
  height: 44px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  flex-shrink: 0;
  border-radius: 10px;
  align-self: center;
}
</style>