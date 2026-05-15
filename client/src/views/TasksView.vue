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

// panel de detalle
const expandedTaskId = ref(null)
const taskDetail = ref(null)
const loadingDetail = ref(false)

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

// abrir o cerrar el panel de detalle de una tarea
function toggleDetail(taskId) {
  // si ya estaba abierta, la cerramos
  if (expandedTaskId.value === taskId) {
    expandedTaskId.value = null
    taskDetail.value = null
    return
  }

  expandedTaskId.value = taskId
  loadingDetail.value = true
  taskDetail.value = null

  // pedimos el detalle completo con checklist
  fetch(rutaApi + "?entity=tasks&id=" + taskId)
    .then(res => res.json())
    .then(data => {
      taskDetail.value = data
      loadingDetail.value = false
    })
    .catch(err => {
      loadingDetail.value = false
    })
}

// marcar item del checklist como hecho o no hecho
function toggleChecklistItem(item) {
  let newDone = 0
  if (item.done == false || item.done == 0) {
    newDone = 1
  }

  fetch(rutaApi + "?entity=task_checklist&id=" + item.id, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ done: newDone })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      if (newDone == 1) {
        item.done = true
      } else {
        item.done = false
      }
    }
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
        // si la tarea borrada estaba abierta, cerramos el panel
        if (expandedTaskId.value === id) {
          expandedTaskId.value = null
          taskDetail.value = null
        }
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
      <button
        class="btn-dopamine btn-dopamine-primary"
        aria-label="Create new task"
        @click="router.push('/tasks/new')"
      >
        <i class="bi bi-plus me-1" aria-hidden="true"></i> New Task
      </button>
    </div>

    <!-- ERROR -->
    <div v-if="error" class="error-text mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>{{ error }}
    </div>

    <!-- LOADING -->
    <div v-if="loading" class="loading-text" aria-live="polite">
      <div class="spinner-border spinner-border-sm me-2" role="status">
        <span class="visually-hidden">Loading tasks...</span>
      </div>
      Loading...
    </div>

    <template v-else>

      <!-- STATS STRIP -->
      <div class="row g-3 mb-4 fade-up delay-1" aria-label="Task statistics">
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-hard">
            <div>
              <div class="stat-num">{{ countByDiff('hard') }}</div>
              <div class="stat-label">Hard pending</div>
            </div>
            <i class="bi bi-fire stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-warn">
            <div>
              <div class="stat-num">{{ countByDiff('medium') }}</div>
              <div class="stat-label">Medium pending</div>
            </div>
            <i class="bi bi-dash-circle stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-ok">
            <div>
              <div class="stat-num">{{ countByDiff('easy') }}</div>
              <div class="stat-label">Easy pending</div>
            </div>
            <i class="bi bi-circle stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-neutral">
            <div>
              <div class="stat-num">{{ tasks.length }}</div>
              <div class="stat-label">Total tasks</div>
            </div>
            <i class="bi bi-list-check stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
      </div>

      <!-- FILTROS -->
      <div class="d-flex flex-wrap gap-2 mb-4 fade-up delay-2" role="group" aria-label="Filter tasks">
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
        <i class="bi bi-check-all empty-icon" aria-hidden="true"></i>
        <p class="empty-title">No tasks found</p>
        <button class="btn-dopamine btn-dopamine-primary mt-2" @click="router.push('/tasks/new')">
          <i class="bi bi-plus me-1" aria-hidden="true"></i> Create your first task
        </button>
      </div>

      <!-- LISTA DE TAREAS -->
      <div v-else class="d-flex flex-column gap-3" role="list">
        <article
          v-for="(task, index) in filteredTasks"
          :key="task.id"
          class="task-card fade-up"
          :class="task.done ? 'card-done' : task.difficulty === 'hard' ? 'card-hard' : task.difficulty === 'medium' ? 'card-medium' : 'card-easy'"
          :style="{ animationDelay: (index * 0.04) + 's' }"
          role="listitem"
        >
          <!-- CABECERA DE LA TAREA (siempre visible) -->
          <div class="task-card-body">

            <!-- CHECKBOX -->
            <button
              class="task-check"
              :class="task.done ? 'task-check-done' : ''"
              :aria-label="task.done ? 'Mark ' + task.title + ' as pending' : 'Mark ' + task.title + ' as done'"
              :aria-pressed="!!task.done"
              @click="toggleDone(task)"
            >
              <i v-if="task.done" class="bi bi-check" aria-hidden="true"></i>
            </button>

            <!-- INFO — click para expandir detalle -->
            <div
              class="flex-grow-1 task-info-clickable"
              style="min-width:0; cursor:pointer"
              @click="toggleDetail(task.id)"
              :aria-expanded="expandedTaskId === task.id"
              :aria-controls="'task-detail-' + task.id"
              role="button"
              :aria-label="'View details of ' + task.title"
              tabindex="0"
              @keydown.enter="toggleDetail(task.id)"
              @keydown.space.prevent="toggleDetail(task.id)"
            >
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
                  <i class="bi bi-check me-1" aria-hidden="true"></i>Done
                </span>
                <span v-else-if="isOverdue(task)" class="bdg bdg-overdue">
                  <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Overdue
                </span>
                <span v-else-if="isDueToday(task)" class="bdg bdg-due">
                  <i class="bi bi-clock me-1" aria-hidden="true"></i>Due today
                </span>
              </div>

              <p v-if="task.descrip" class="task-descrip">{{ task.descrip }}</p>

              <div class="task-date">
                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                {{ formatDate(task.expDate) }}
              </div>
            </div>

            <!-- BOTONES DERECHA -->
            <div class="d-flex gap-2 align-items-center flex-shrink-0">
              <button
                class="btn-dopamine btn-dopamine-ghost task-icon-btn"
                :aria-label="'Edit task: ' + task.title"
                @click="router.push('/tasks/edit/' + task.id)"
              >
                <i class="bi bi-pencil" aria-hidden="true"></i>
              </button>
              <button
                class="btn-dopamine btn-dopamine-danger task-icon-btn"
                :aria-label="'Delete task: ' + task.title"
                @click="deleteTask(task.id)"
              >
                <i class="bi bi-trash" aria-hidden="true"></i>
              </button>
              <button
                class="btn-dopamine btn-dopamine-ghost task-icon-btn"
                :aria-label="(expandedTaskId === task.id ? 'Close' : 'Open') + ' details of ' + task.title"
                @click="toggleDetail(task.id)"
              >
                <i
                  class="bi"
                  :class="expandedTaskId === task.id ? 'bi-chevron-up' : 'bi-chevron-down'"
                  aria-hidden="true"
                ></i>
              </button>
            </div>
          </div>

          <!-- PANEL DE DETALLE EXPANDIBLE -->
          <div
            v-if="expandedTaskId === task.id"
            :id="'task-detail-' + task.id"
            class="task-detail-panel"
          >
            <!-- LOADING DETALLE -->
            <div v-if="loadingDetail" class="loading-text py-3">
              <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Loading details...</span>
              </div>
              Loading details...
            </div>

            <template v-else-if="taskDetail">

              <!-- FECHAS -->
              <div class="row g-3 mb-3">
                <div class="col-6" v-if="taskDetail.startDate">
                  <div class="detail-info-item">
                    <span class="detail-info-label">
                      <i class="bi bi-calendar-plus me-1" aria-hidden="true"></i>Start date
                    </span>
                    <span class="detail-info-value">{{ formatDate(taskDetail.startDate) }}</span>
                  </div>
                </div>
                <div class="col-6">
                  <div class="detail-info-item">
                    <span class="detail-info-label">
                      <i class="bi bi-calendar-x me-1" aria-hidden="true"></i>Due date
                    </span>
                    <span class="detail-info-value">{{ formatDate(taskDetail.expDate) }}</span>
                  </div>
                </div>
              </div>

              <!-- ENLACE si existe -->
              <div v-if="taskDetail.url" class="mb-3">
                <div class="detail-info-item">
                  <span class="detail-info-label">
                    <i class="bi bi-link-45deg me-1" aria-hidden="true"></i>Link
                  </span>
                  <a
                    :href="taskDetail.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="detail-link"
                    :aria-label="'Open link: ' + taskDetail.url"
                  >
                    {{ taskDetail.url }}
                    <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                  </a>
                </div>
              </div>

              <!-- CHECKLIST -->
              <div v-if="taskDetail.checklist && taskDetail.checklist.length > 0">
                <p class="detail-section-label">
                  <i class="bi bi-list-check me-1" aria-hidden="true"></i>Steps
                </p>
                <div class="d-flex flex-column gap-2" role="list" aria-label="Task checklist">
                  <div
                    v-for="item in taskDetail.checklist"
                    :key="item.id"
                    class="checklist-item-detail"
                    :class="item.done ? 'checklist-item-done' : ''"
                    role="listitem"
                  >
                    <button
                      class="checklist-check-btn"
                      :class="item.done ? 'checked' : ''"
                      :aria-label="(item.done ? 'Uncheck' : 'Check') + ' step: ' + item.title"
                      :aria-pressed="!!item.done"
                      @click="toggleChecklistItem(item)"
                    >
                      <i v-if="item.done" class="bi bi-check" aria-hidden="true"></i>
                    </button>
                    <span
                      class="checklist-item-text"
                      :class="item.done ? 'text-decoration-line-through' : ''"
                    >
                      {{ item.title }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Sin checklist -->
              <p v-else class="detail-empty-text">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                No steps added for this task.
                <button
                  class="btn-link-style ms-1"
                  aria-label="Edit task to add steps"
                  @click="router.push('/tasks/edit/' + task.id)"
                >
                  Edit task to add steps
                </button>
              </p>

            </template>
          </div>

        </article>
      </div>

    </template>

    <!-- FAB -->
    <button
      class="fab"
      aria-label="Create new task"
      @click="router.push('/tasks/new')"
    >
      <i class="bi bi-plus" aria-hidden="true"></i>
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
  overflow: hidden;
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

/* TÍTULO — fuente más gruesa */
.task-title {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--cinnamon-dark);
}

.task-title-done { color: var(--cinnamon-soft); }

/* DESCRIPCIÓN */
.task-descrip {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.88rem;
  font-weight: 500;
  color: var(--cinnamon-soft);
  margin: 0.25rem 0 0;
}

/* FECHA */
.task-date {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--cinnamon-soft);
  margin-top: 0.4rem;
  display: flex;
  align-items: center;
}

/* CHECKBOX — como botón para accesibilidad */
.task-check {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2.5px solid var(--vanilla-mid);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 1rem;
  flex-shrink: 0;
  margin-top: 2px;
}

.task-check:hover        { border-color: var(--cinnamon-mid); background: var(--bg-subtle); }
.task-check:focus-visible { outline: 3px solid var(--cinnamon-mid); outline-offset: 3px; }
.task-check-done         { background: var(--state-ok); border-color: var(--state-ok); color: #fff; }

/* BOTONES ICONO */
.task-icon-btn {
  width: 44px;
  height: 44px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  border-radius: 10px;
}

.task-icon-btn:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 3px;
}

/* ÁREA CLICKABLE DE INFO */
.task-info-clickable:focus-visible {
  outline: 2px dashed var(--cinnamon-mid);
  border-radius: 6px;
  outline-offset: 4px;
}

/* PANEL DE DETALLE */
.task-detail-panel {
  padding: 1rem 1.3rem 1.2rem;
  border-top: 1.5px solid #F0EBE3;
  background: var(--bg-card);
}

.detail-info-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.detail-info-label {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.detail-info-value {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.92rem;
  font-weight: 600;
  color: var(--cinnamon-dark);
}

.detail-link {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--btn-info);
  text-decoration: underline;
  text-underline-offset: 3px;
  word-break: break-all;
}

.detail-link:hover { color: var(--btn-info-h); }

.detail-section-label {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 0.6rem;
}

/* CHECKLIST EN DETALLE */
.checklist-item-detail {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.55rem 0.85rem;
  border-radius: 8px;
  border: 1px solid var(--vanilla-light);
  background: var(--bg-base);
}

.checklist-item-done {
  background: var(--state-ok-bg);
  border-color: #C8E4CA;
}

.checklist-check-btn {
  width: 24px;
  height: 24px;
  border-radius: 4px;
  border: 2px solid var(--vanilla-mid);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.checklist-check-btn:hover    { border-color: var(--cinnamon-mid); }
.checklist-check-btn:focus-visible { outline: 3px solid var(--cinnamon-mid); outline-offset: 2px; }
.checklist-check-btn.checked  { background: var(--state-ok); border-color: var(--state-ok); color: #fff; }

.checklist-item-text {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--cinnamon-dark);
  flex-grow: 1;
}

.detail-empty-text {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.85rem;
  color: var(--cinnamon-soft);
  margin: 0;
}

/* Enlace como botón */
.btn-link-style {
  background: none;
  border: none;
  color: var(--cinnamon-dark);
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 3px;
  cursor: pointer;
  padding: 0;
}

.btn-link-style:hover { color: var(--cinnamon-mid); }
</style>