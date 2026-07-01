<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

const userStore = useUserStore();
const router = useRouter();

//variables reactivas globales
const tasks = ref([]);
const loading = ref(true);
const error = ref("");
const filter = ref("all");

//variable de control de la pestaña desplegable de info
const expandedTaskId = ref(null);
const taskDetail = ref(null);
const loadingDetail = ref(false);

//funcion principal para cargar tareas
function fetchTasks() {
  loading.value = true;
  error.value = "";

  fetch(rutaApi + "?entity=tasks&user_id=" + userStore.user.id)
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      tasks.value = data;
      loading.value = false;
    })
    .catch(function (err) {
      error.value = "Error loading tasks";
      loading.value = false;
    });
}

//funcion auxiliar para abrir o cerrar el panel de detalle
function toggleDetail(taskId) {
  if (expandedTaskId.value === taskId) {
    expandedTaskId.value = null;
    taskDetail.value = null;
    return;
  }

  expandedTaskId.value = taskId;
  loadingDetail.value = true;
  taskDetail.value = null;

  fetch(rutaApi + "?entity=tasks&id=" + taskId)
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      taskDetail.value = data;
      loadingDetail.value = false;
    })
    .catch(function (err) {
      loadingDetail.value = false;
    });
}

//funcion auxiliar para marcar un paso del checklist
function toggleChecklistItem(item) {
  let newDone = 0;
  if (item.done == false || item.done == 0) {
    newDone = 1;
  }

  fetch(rutaApi + "?entity=task_checklist&id=" + item.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ done: newDone }),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        item.done = newDone == 1;
      }
    });
}

//funcion para marcar una tarea como hecha (la mueve al archivo)
function toggleDone(task) {
  let newStatus = 0;
  if (task.done == false || task.done == 0) {
    newStatus = 1;
  }

  fetch(rutaApi + "?entity=tasks&id=" + task.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ done: newStatus }),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        task.done = newStatus == 1;
        //si se marca como hecha, se cierra el panel si estaba abierto
        if (newStatus == 1 && expandedTaskId.value === task.id) {
          expandedTaskId.value = null;
          taskDetail.value = null;
        }
      }
    });
}

//funcion para borrar tarea
function deleteTask(id) {
  let check = confirm("Delete this task?");
  if (check === false) return;

  fetch(rutaApi + "?entity=tasks&id=" + id, { method: "DELETE" })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status == "success") {
        if (expandedTaskId.value === id) {
          expandedTaskId.value = null;
          taskDetail.value = null;
        }
        tasks.value = tasks.value.filter((t) => t.id !== id);
      } else {
        error.value = "Error deleting task";
      }
    });
}

//funcion auxiliar para saber si la tarea esta vencida
function isOverdue(task) {
  let today = new Date();
  let taskDate = new Date(task.expDate);
  return task.done == false && taskDate < today;
}

//funcion auxiliar para saber si la tarea vence hoy
function isDueToday(task) {
  if (task.done === true) {
    return false;
  }
  let today = new Date().toISOString().split("T")[0];
  let expDate = "";
  if (task.expDate) {
    expDate = task.expDate.split(" ")[0];
  }
  return expDate == today;
}

//funcion auxiliar para formatear fecha
function formatDate(dateStr) {
  if (!dateStr) {
    return "No deadline";
  }
  let date = new Date(dateStr);
  return date.toLocaleDateString("en-GB");
}

//solo mostramos las tareas pendientes — las hechas van al archivo
const pendingTasks = computed(function () {
  return tasks.value.filter((t) => t.done == false);
});

//contador de tareas archivadas para mostrar en el boton
const archivedCount = computed(function () {
  return tasks.value.filter((t) => t.done == true).length;
});

//filtros aplicados solo sobre las tareas pendientes
const filteredTasks = computed(function () {
  if (filter.value === "hard") {
    return pendingTasks.value.filter((t) => t.difficulty == "hard");
  } else if (filter.value === "medium") {
    return pendingTasks.value.filter((t) => t.difficulty == "medium");
  } else if (filter.value === "easy") {
    return pendingTasks.value.filter((t) => t.difficulty == "easy");
  } else if (filter.value === "overdue") {
    return pendingTasks.value.filter((t) => isOverdue(t));
  } else {
    return pendingTasks.value;
  }
});

//contador de pendientes segun dificultad
function countByDiff(diff) {
  return pendingTasks.value.filter((t) => t.difficulty === diff).length;
}

//cuando carga la vista se dispara la busqueda
onMounted(function () {
  fetchTasks();
});
</script>

<template>
  <div class="tasks-container">

    <!-- cabecera con titulo y boton nueva tarea -->
    <div class="d-flex align-items-end justify-content-between mb-4 fade-up">
      <h1 class="page-title"><em>manage your</em> Tasks</h1>
      <button class="btn-dopamine btn-dopamine-primary" @click="router.push('/tasks/new')">
        <i class="bi bi-plus me-1"></i> New Task
      </button>
    </div>

    <!-- mensaje de error -->
    <div v-if="error" class="error-text mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ error }}
    </div>

    <!-- spinner de carga -->
    <div v-if="loading" class="loading-text">
      <div class="spinner-border spinner-border-sm me-2" role="status"></div>
      Loading...
    </div>

    <template v-else>

      <!-- estadisticas de tareas pendientes -->
      <div class="row g-3 mb-4 fade-up delay-1">
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-hard">
            <div>
              <div class="stat-num">{{ countByDiff("hard") }}</div>
              <div class="stat-label">Hard pending</div>
            </div>
            <i class="bi bi-fire stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-warn">
            <div>
              <div class="stat-num">{{ countByDiff("medium") }}</div>
              <div class="stat-label">Medium pending</div>
            </div>
            <i class="bi bi-dash-circle stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-ok">
            <div>
              <div class="stat-num">{{ countByDiff("easy") }}</div>
              <div class="stat-label">Easy pending</div>
            </div>
            <i class="bi bi-circle stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-neutral">
            <div>
              <div class="stat-num">{{ pendingTasks.length }}</div>
              <div class="stat-label">Total pending</div>
            </div>
            <i class="bi bi-list-check stat-icon ms-3"></i>
          </div>
        </div>
      </div>

      <!-- botones de filtro (solo pendientes) -->
      <div class="d-flex flex-wrap gap-2 mb-4 fade-up delay-2">
        <button class="filter-tab" :class="{ active: filter === 'all' }"    @click="filter = 'all'">
          All ({{ pendingTasks.length }})
        </button>
        <button class="filter-tab" :class="{ active: filter === 'hard' }"   @click="filter = 'hard'">Hard</button>
        <button class="filter-tab" :class="{ active: filter === 'medium' }" @click="filter = 'medium'">Medium</button>
        <button class="filter-tab" :class="{ active: filter === 'easy' }"   @click="filter = 'easy'">Easy</button>
        <button class="filter-tab" :class="{ active: filter === 'overdue' }" @click="filter = 'overdue'">Overdue</button>
      </div>

      <!-- lista vacia -->
      <div v-if="filteredTasks.length === 0" class="empty-state fade-up">
        <i class="bi bi-check-all empty-icon"></i>
        <p class="empty-title">No tasks here!</p>
        <button class="btn-dopamine btn-dopamine-primary mt-2" @click="router.push('/tasks/new')">
          <i class="bi bi-plus me-1"></i> Create a task
        </button>
      </div>

      <!-- lista de tareas pendientes -->
      <div v-else class="d-flex flex-column gap-3">
        <article
          v-for="(task, index) in filteredTasks"
          :key="task.id"
          class="task-card fade-up"
          :class="isOverdue(task) ? 'card-hard' : isDueToday(task) ? 'card-medium' : task.difficulty === 'hard' ? 'card-hard' : task.difficulty === 'easy' ? 'card-easy' : 'card-medium'"
          :style="{ animationDelay: index * 0.04 + 's' }"
        >
          <div class="task-card-body">

            <!-- checkbox para marcar como hecha (pasa al archivo) -->
            <button
              class="task-check"
              :class="task.done ? 'task-check-done' : ''"
              :title="task.done ? 'Uncheck task' : 'Mark as done'"
              @click="toggleDone(task)"
            >
              <i v-if="task.done" class="bi bi-check"></i>
            </button>

            <!-- info principal de la tarea -->
            <div class="task-info-clickable flex-grow-1" style="min-width: 0; cursor: pointer;" @click="toggleDetail(task.id)">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="task-title" :class="task.done ? 'task-title-done' : ''">
                  <span v-if="task.icon">{{ task.icon }} </span>{{ task.title }}
                </span>
                <span class="bdg" :class="'bdg-' + task.difficulty">{{ task.difficulty }}</span>
                <span v-if="isOverdue(task)" class="bdg bdg-error">
                  <i class="bi bi-exclamation-triangle me-1"></i>Overdue
                </span>
                <span v-else-if="isDueToday(task)" class="bdg bdg-warn">
                  <i class="bi bi-clock me-1"></i>Today
                </span>
              </div>
              <div v-if="task.descrip" class="task-descrip">{{ task.descrip }}</div>
              <div class="task-date">
                <i class="bi bi-calendar-x me-1"></i> {{ formatDate(task.expDate) }}
              </div>
            </div>

            <!-- botones de editar, borrar y expandir -->
            <div class="d-flex align-items-center gap-1 flex-shrink-0">
              <button
                class="btn-dopamine btn-dopamine-ghost task-icon-btn"
                title="Edit task"
                @click="router.push('/tasks/edit/' + task.id)"
              >
                <i class="bi bi-pencil"></i>
              </button>
              <button
                class="btn-dopamine btn-dopamine-danger task-icon-btn"
                title="Delete task"
                @click="deleteTask(task.id)"
              >
                <i class="bi bi-trash"></i>
              </button>
              <button
                class="btn-dopamine btn-dopamine-ghost task-icon-btn"
                :title="expandedTaskId === task.id ? 'Close details' : 'View details'"
                @click="toggleDetail(task.id)"
              >
                <i class="bi" :class="expandedTaskId === task.id ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
              </button>
            </div>
          </div>

          <!-- panel de detalle expandible -->
          <div v-if="expandedTaskId === task.id" class="task-detail-panel">
            <div v-if="loadingDetail" class="loading-text py-3">
              <div class="spinner-border spinner-border-sm me-2"></div>
              Loading details...
            </div>

            <template v-else-if="taskDetail">
              <div class="row g-3 mb-3">
                <div class="col-6" v-if="taskDetail.startDate">
                  <div class="detail-info-item">
                    <span class="detail-info-label"><i class="bi bi-calendar-plus me-1"></i>Start date</span>
                    <span class="detail-info-value">{{ formatDate(taskDetail.startDate) }}</span>
                  </div>
                </div>
                <div class="col-6">
                  <div class="detail-info-item">
                    <span class="detail-info-label"><i class="bi bi-calendar-x me-1"></i>Due date</span>
                    <span class="detail-info-value">{{ formatDate(taskDetail.expDate) }}</span>
                  </div>
                </div>
              </div>

              <!-- links de la tarea -->
              <div v-if="taskDetail.url || taskDetail.url2 || taskDetail.url3" class="mb-3">
                <div class="detail-info-item">
                  <span class="detail-info-label"><i class="bi bi-link-45deg me-1"></i>Links</span>
                  <a v-if="taskDetail.url"  :href="taskDetail.url"  target="_blank" rel="noopener noreferrer" class="detail-link d-block">{{ taskDetail.url }}  <i class="bi bi-box-arrow-up-right ms-1"></i></a>
                  <a v-if="taskDetail.url2" :href="taskDetail.url2" target="_blank" rel="noopener noreferrer" class="detail-link d-block">{{ taskDetail.url2 }} <i class="bi bi-box-arrow-up-right ms-1"></i></a>
                  <a v-if="taskDetail.url3" :href="taskDetail.url3" target="_blank" rel="noopener noreferrer" class="detail-link d-block">{{ taskDetail.url3 }} <i class="bi bi-box-arrow-up-right ms-1"></i></a>
                </div>
              </div>

              <!-- checklist de pasos -->
              <div v-if="taskDetail.checklist && taskDetail.checklist.length > 0">
                <p class="detail-section-label">
                  <i class="bi bi-list-check me-1"></i>Steps
                </p>
                <div class="d-flex flex-column gap-2">
                  <div
                    v-for="item in taskDetail.checklist"
                    :key="item.id"
                    class="checklist-item-detail"
                    :class="item.done ? 'checklist-item-done' : ''"
                  >
                    <button
                      class="checklist-check-btn"
                      :class="item.done ? 'checked' : ''"
                      @click="toggleChecklistItem(item)"
                    >
                      <i v-if="item.done" class="bi bi-check"></i>
                    </button>
                    <span class="checklist-item-text" :class="item.done ? 'text-decoration-line-through' : ''">
                      {{ item.title }}
                    </span>
                  </div>
                </div>
              </div>
              <p v-else class="detail-empty-text">
                <i class="bi bi-info-circle me-1"></i> No steps added.
                <button class="btn-link-style ms-1" @click="router.push('/tasks/edit/' + task.id)">
                  Edit task to add steps
                </button>
              </p>
            </template>
          </div>
        </article>
      </div>

      <!-- boton de acceso al archivo de tareas completadas -->
      <div class="archive-btn-wrap mt-4 fade-up">
        <button class="archive-btn" @click="router.push('/tasks/archive')">
          <div class="d-flex align-items-center gap-3">
            <div class="archive-btn-icon">
              <i class="bi bi-archive"></i>
            </div>
            <div class="text-start">
              <div class="archive-btn-title">Completed tasks</div>
              <div class="archive-btn-sub">{{ archivedCount }} task{{ archivedCount !== 1 ? 's' : '' }} archived</div>
            </div>
          </div>
          <i class="bi bi-chevron-right archive-btn-arrow"></i>
        </button>
      </div>

    </template>

    <!-- boton flotante nueva tarea -->
    <button class="fab" @click="router.push('/tasks/new')">
      <i class="bi bi-plus"></i>
    </button>
  </div>
</template>

<style scoped>
/* contenedor general espaciado */
.tasks-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: "Atkinson Hyperlegible", sans-serif;
}

@media (max-width: 768px) {
  .tasks-container { padding: 1.5rem 1rem 5rem; }
}

/* tarjeta base */
.task-card {
  border-radius: 12px;
  border: 1.5px solid var(--vanilla-mid);
  border-left: 4px solid var(--vanilla-mid);
  background: var(--bg-base);
  box-shadow: 0 2px 8px rgba(92, 51, 23, 0.07);
  transition: box-shadow 0.15s;
  overflow: hidden;
}
.task-card:hover { box-shadow: 0 4px 16px rgba(92, 51, 23, 0.11); }

/* colores segun dificultad o estado */
.card-hard   { border-left-color: var(--state-error); }
.card-medium { border-left-color: var(--state-warn); }
.card-easy   { border-left-color: var(--state-ok); }

.task-card-body {
  padding: 1.1rem 1.3rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.task-title      { font-size: 1.05rem; font-weight: 800; color: var(--cinnamon-dark); }
.task-title-done { color: var(--cinnamon-soft); }
.task-descrip    { font-size: 0.88rem; font-weight: 500; color: var(--cinnamon-soft); margin: 0.25rem 0 0; }
.task-date       { font-size: 0.85rem; font-weight: 600; color: var(--cinnamon-soft); margin-top: 0.4rem; display: flex; align-items: center; }

/* checkbox circular de la tarea */
.task-check {
  width: 32px; height: 32px; border-radius: 50%;
  border: 2.5px solid var(--vanilla-mid); background: transparent;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s; flex-shrink: 0; margin-top: 2px;
}
.task-check:hover { border-color: var(--cinnamon-mid); background: var(--bg-subtle); }
.task-check-done  { background: var(--state-ok); border-color: var(--state-ok); color: #fff; }

/* botones de editar y borrar */
.task-icon-btn {
  width: 44px; height: 44px; padding: 0;
  display: flex; align-items: center; justify-content: center; border-radius: 10px;
}
.task-info-clickable:focus-visible { outline: 2px dashed var(--cinnamon-mid); border-radius: 6px; outline-offset: 4px; }

/* panel de detalle desplegable */
.task-detail-panel {
  padding: 1rem 1.3rem 1.2rem;
  border-top: 1.5px solid #f0ebe3;
  background: var(--bg-card);
}
.detail-info-item  { display: flex; flex-direction: column; gap: 0.2rem; }
.detail-info-label { font-size: 0.72rem; font-weight: 700; color: var(--cinnamon-soft); text-transform: uppercase; letter-spacing: 1px; }
.detail-info-value { font-size: 0.92rem; font-weight: 600; color: var(--cinnamon-dark); }
.detail-link       { font-size: 0.88rem; font-weight: 600; color: var(--btn-info); text-decoration: underline; word-break: break-all; }
.detail-section-label { font-size: 0.78rem; font-weight: 700; color: var(--cinnamon-soft); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; }

/* items del checklist interior */
.checklist-item-detail {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.55rem 0.85rem; border-radius: 8px;
  border: 1px solid var(--vanilla-light); background: var(--bg-base);
}
.checklist-item-done  { background: var(--state-ok-bg); border-color: #c8e4ca; }
.checklist-check-btn  {
  width: 24px; height: 24px; border-radius: 4px;
  border: 2px solid var(--vanilla-mid); background: transparent;
  display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;
}
.checklist-check-btn.checked { background: var(--state-ok); border-color: var(--state-ok); color: #fff; }
.checklist-item-text  { font-size: 0.88rem; font-weight: 600; color: var(--cinnamon-dark); }
.detail-empty-text    { font-size: 0.85rem; color: var(--cinnamon-soft); margin: 0.2rem 0 0; }
.btn-link-style { background: none; border: none; color: var(--cinnamon-dark); font-size: 0.85rem; font-weight: 700; text-decoration: underline; cursor: pointer; padding: 0; }

/* boton de acceso al archivo */
.archive-btn-wrap { padding-bottom: 1rem; }

.archive-btn {
  width: 100%;
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 12px;
  padding: 1rem 1.3rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: box-shadow 0.15s, background 0.15s;
  font-family: "Atkinson Hyperlegible", sans-serif;
  box-shadow: 0 2px 8px rgba(92, 51, 23, 0.06);
}
.archive-btn:hover { background: var(--bg-subtle); box-shadow: 0 4px 14px rgba(92, 51, 23, 0.1); }

.archive-btn-icon {
  width: 44px; height: 44px; border-radius: 10px;
  background: var(--vanilla-light); color: var(--cinnamon-dark);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; flex-shrink: 0;
}

.archive-btn-title { font-size: 0.95rem; font-weight: 700; color: var(--cinnamon-dark); }
.archive-btn-sub   { font-size: 0.8rem; color: var(--cinnamon-soft); font-weight: 600; }
.archive-btn-arrow { font-size: 1rem; color: var(--cinnamon-soft); }
</style>
