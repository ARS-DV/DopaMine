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

//panel de detalle
const expandedTaskId = ref(null);
const taskDetail = ref(null);
const loadingDetail = ref(false);

//funcion principal para cargar solo las tareas completadas
function fetchArchivedTasks() {
  loading.value = true;
  error.value = "";

  fetch(rutaApi + "?entity=tasks&user_id=" + userStore.user.id)
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      //filtramos solo las que esten hechas
      tasks.value = data.filter((t) => t.done == true);
      loading.value = false;
    })
    .catch(function () {
      error.value = "Error loading archive";
      loading.value = false;
    });
}

//funcion para abrir o cerrar el panel de detalle
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
    .catch(function () {
      loadingDetail.value = false;
    });
}

//funcion para borrar una tarea del archivo
function deleteTask(id) {
  let check = confirm("Delete this task permanently?");
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

//funcion para formatear fecha
function formatDate(dateStr) {
  if (!dateStr) { return "No deadline"; }
  return new Date(dateStr).toLocaleDateString("en-GB");
}

//total de tareas archivadas
const totalDone = computed(function () {
  return tasks.value.length;
});

//cuantas fueron completadas a tiempo (onTime = 1)
const doneOnTime = computed(function () {
  return tasks.value.filter((t) => t.onTime == 1).length;
});

//cuantas fueron completadas tarde (onTime = 0 y tienen registro)
const doneLate = computed(function () {
  return tasks.value.filter((t) => t.onTime == 0).length;
});

//mejor racha: mayor numero consecutivo de tareas completadas a tiempo, ordenadas por fecha de vencimiento
const bestStreak = computed(function () {
  let sorted = [...tasks.value].sort(function (a, b) {
    return new Date(a.expDate) - new Date(b.expDate);
  });

  let best = 0;
  let current = 0;
  for (let i = 0; i < sorted.length; i++) {
    if (sorted[i].onTime == 1) {
      current++;
      if (current > best) {
        best = current;
      }
    } else {
      current = 0;
    }
  }
  return best;
});

//cuando carga la vista se dispara la busqueda
onMounted(function () {
  fetchArchivedTasks();
});
</script>

<template>
  <div class="archive-container">

    <!-- miga de pan -->
    <nav class="breadcrumb-dopamine breadcrumb-visible mb-4 fade-up">
      <RouterLink to="/home"><i class="bi bi-house me-1"></i>Home</RouterLink>
      <span class="separator"><i class="bi bi-chevron-right"></i></span>
      <RouterLink to="/tasks">Tasks</RouterLink>
      <span class="separator"><i class="bi bi-chevron-right"></i></span>
      <span class="current">Archive</span>
    </nav>

    <!-- cabecera -->
    <div class="mb-4 fade-up delay-1">
      <h1 class="page-title">
        <em>completed</em> Tasks
      </h1>
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

      <!-- estadisticas del archivo -->
      <div class="row g-3 mb-5 fade-up delay-2">
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-neutral">
            <div>
              <div class="stat-num">{{ totalDone }}</div>
              <div class="stat-label">Total done</div>
            </div>
            <i class="bi bi-check-all stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-ok">
            <div>
              <div class="stat-num">{{ doneOnTime }}</div>
              <div class="stat-label">On time</div>
            </div>
            <i class="bi bi-check-circle stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-warn">
            <div>
              <div class="stat-num">{{ doneLate }}</div>
              <div class="stat-label">Completed late</div>
            </div>
            <i class="bi bi-clock-history stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-info">
            <div>
              <div class="stat-num">{{ bestStreak }}</div>
              <div class="stat-label">Best streak</div>
            </div>
            <i class="bi bi-fire stat-icon ms-3"></i>
          </div>
        </div>
      </div>

      <!-- lista vacia -->
      <div v-if="tasks.length === 0" class="empty-state fade-up">
        <i class="bi bi-archive empty-icon"></i>
        <p class="empty-title">No completed tasks yet</p>
        <p class="empty-sub">Tasks you mark as done will appear here</p>
      </div>

      <!-- lista de tareas archivadas -->
      <div v-else class="d-flex flex-column gap-3">
        <article
          v-for="(task, index) in tasks"
          :key="task.id"
          class="task-card task-card-archived fade-up"
          :class="task.onTime == 1 ? 'card-ontime' : task.onTime == 0 ? 'card-late' : 'card-done'"
          :style="{ animationDelay: index * 0.04 + 's' }"
        >
          <div class="task-card-body">

            <!-- icono de estado: hecha a tiempo o tarde -->
            <div class="archive-status-icon">
              <i
                class="bi"
                :class="task.onTime == 1 ? 'bi-check-circle-fill text-success' : task.onTime == 0 ? 'bi-clock-history text-warning' : 'bi-check-circle text-secondary'"
              ></i>
            </div>

            <!-- info de la tarea -->
            <div class="flex-grow-1" style="min-width: 0; cursor: pointer;" @click="toggleDetail(task.id)">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="task-title task-title-done">
                  <span v-if="task.icon">{{ task.icon }} </span>{{ task.title }}
                </span>
                <span class="bdg" :class="'bdg-' + task.difficulty">{{ task.difficulty }}</span>
                <span v-if="task.onTime == 1" class="bdg bdg-done">
                  <i class="bi bi-check-circle me-1"></i>On time
                </span>
                <span v-else-if="task.onTime == 0" class="bdg bdg-tried">
                  <i class="bi bi-clock me-1"></i>Late
                </span>
              </div>
              <div v-if="task.descrip" class="task-descrip">{{ task.descrip }}</div>
              <div class="task-date">
                <i class="bi bi-calendar-x me-1"></i> Due {{ formatDate(task.expDate) }}
              </div>
            </div>

            <!-- botones de borrar y expandir -->
            <div class="d-flex align-items-center gap-1 flex-shrink-0">
              <button
                class="btn-dopamine btn-dopamine-danger task-icon-btn"
                title="Delete from archive"
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

          <!-- panel de detalle -->
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

              <!-- links -->
              <div v-if="taskDetail.url || taskDetail.url2 || taskDetail.url3" class="mb-3">
                <div class="detail-info-item">
                  <span class="detail-info-label"><i class="bi bi-link-45deg me-1"></i>Links</span>
                  <a v-if="taskDetail.url"  :href="taskDetail.url"  target="_blank" rel="noopener noreferrer" class="detail-link d-block">{{ taskDetail.url }}  <i class="bi bi-box-arrow-up-right ms-1"></i></a>
                  <a v-if="taskDetail.url2" :href="taskDetail.url2" target="_blank" rel="noopener noreferrer" class="detail-link d-block">{{ taskDetail.url2 }} <i class="bi bi-box-arrow-up-right ms-1"></i></a>
                  <a v-if="taskDetail.url3" :href="taskDetail.url3" target="_blank" rel="noopener noreferrer" class="detail-link d-block">{{ taskDetail.url3 }} <i class="bi bi-box-arrow-up-right ms-1"></i></a>
                </div>
              </div>

              <!-- pasos completados (solo lectura) -->
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
                    <!-- solo lectura, sin boton de marcar -->
                    <div class="checklist-check-static" :class="item.done ? 'checked' : ''">
                      <i v-if="item.done" class="bi bi-check"></i>
                    </div>
                    <span class="checklist-item-text" :class="item.done ? 'text-decoration-line-through' : ''">
                      {{ item.title }}
                    </span>
                  </div>
                </div>
              </div>
            </template>
          </div>

        </article>
      </div>

    </template>
  </div>
</template>

<style scoped>
/* contenedor general */
.archive-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: "Atkinson Hyperlegible", sans-serif;
}

@media (max-width: 768px) {
  .archive-container { padding: 1.5rem 1rem 4rem; }
}

/* miga de pan */
.breadcrumb-visible {
  font-size: 1rem !important; font-weight: 600 !important;
  background: var(--bg-card); border: 1.5px solid var(--vanilla-mid);
  border-radius: 10px; padding: 0.7rem 1rem !important;
}
.breadcrumb-visible a        { color: var(--cinnamon-mid) !important; font-weight: 700 !important; }
.breadcrumb-visible .current { color: var(--cinnamon-dark) !important; font-weight: 700 !important; }
.breadcrumb-visible .separator { color: var(--vanilla-mid) !important; }

/* tarjeta de tarea archivada */
.task-card {
  border-radius: 12px;
  border: 1.5px solid var(--vanilla-mid);
  border-left: 4px solid var(--vanilla-mid);
  background: var(--bg-base);
  box-shadow: 0 2px 8px rgba(92, 51, 23, 0.07);
  transition: box-shadow 0.15s;
  overflow: hidden;
}
.task-card:hover { box-shadow: 0 4px 16px rgba(92, 51, 23, 0.1); }

/* colores segun resultado */
.card-ontime { border-left-color: var(--state-ok); background: var(--state-ok-bg); }
.card-late   { border-left-color: var(--state-warn); }
.card-done   { border-left-color: var(--vanilla-mid); opacity: 0.85; }

.task-card-body {
  padding: 1.1rem 1.3rem;
  display: flex; align-items: flex-start; gap: 1rem;
}

/* icono de estado de la tarea archivada */
.archive-status-icon {
  font-size: 1.3rem; flex-shrink: 0; margin-top: 2px; width: 28px;
  display: flex; align-items: center; justify-content: center;
}

.task-title      { font-size: 1.05rem; font-weight: 800; color: var(--cinnamon-dark); }
.task-title-done { color: var(--cinnamon-soft); }
.task-descrip    { font-size: 0.88rem; font-weight: 500; color: var(--cinnamon-soft); margin: 0.25rem 0 0; }
.task-date       { font-size: 0.85rem; font-weight: 600; color: var(--cinnamon-soft); margin-top: 0.4rem; display: flex; align-items: center; }

/* botones de accion */
.task-icon-btn {
  width: 44px; height: 44px; padding: 0;
  display: flex; align-items: center; justify-content: center; border-radius: 10px;
}

/* panel de detalle */
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

/* pasos en modo solo lectura */
.checklist-item-detail {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.55rem 0.85rem; border-radius: 8px;
  border: 1px solid var(--vanilla-light); background: var(--bg-base);
}
.checklist-item-done { background: var(--state-ok-bg); border-color: #c8e4ca; }

.checklist-check-static {
  width: 24px; height: 24px; border-radius: 4px;
  border: 2px solid var(--vanilla-mid); background: transparent;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.checklist-check-static.checked { background: var(--state-ok); border-color: var(--state-ok); color: #fff; }
.checklist-item-text { font-size: 0.88rem; font-weight: 600; color: var(--cinnamon-dark); }

.empty-sub { font-size: 0.88rem; color: var(--cinnamon-soft); margin: 0; }
</style>
