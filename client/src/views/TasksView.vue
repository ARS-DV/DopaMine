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
const filter = ref("all"); //filtro por defectp

//variable de control de la pestaña desplegable de info
const expandedTaskId = ref(null);
const taskDetail = ref(null);
const loadingDetail = ref(false);

//funcion principal
async function fetchTasks() {
  loading.value = true;
  error.value = "";

  let url = rutaApi + "?entity=tasks&user_id=" + userStore.user.id;

  fetch(url)
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

//funcion auxiliar para abrir o cerrar el panel de info
function toggleDetail(taskId) {
  //si pincha en una ya abierta, la cierra
  if (expandedTaskId.value === taskId) {
    expandedTaskId.value = null;
    taskDetail.value = null;
    return;
  }

  expandedTaskId.value = taskId;
  loadingDetail.value = true;
  taskDetail.value = null;

  //peticion para las subtareas
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

//funcion auxiliar para el checklist de una subtarea
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
        item.done = newDone == 1; //sincronizacion con tarea
      }
    });
}

//funcion auxiliar para el tachado de tareas
async function toggleDone(task) {
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
      }
    });
}

//funcion para borrar tarea
async function deleteTask(id) {
  let check = confirm("Delete this task?");
  if (check === false) return;

  fetch(rutaApi + "?entity=tasks&id=" + id, { method: "DELETE" })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status == "success") {
        //si se borra una que estaba abierta subtareas, la cierra tambien
        if (expandedTaskId.value === id) {
          expandedTaskId.value = null;
          taskDetail.value = null;
        }
        //se borra la tarea
        tasks.value = tasks.value.filter((t) => t.id !== id);
      } else {
        error.value = "Error deleting task";
      }
    });
}

//funcion auxiliar para comrpobar si la tarea esta pasada de fecha
function isOverdue(task) {
  let today = new Date();
  let taskDate = new Date(task.expDate);
  return task.done == false && taskDate < today;
}

//funcion auxiliar para comrpobar si la tarea caduca hoy
function isDueToday(task) {
  if (task.done === true){
    return false;
  } 
  let today = new Date().toISOString().split("T")[0];
  let expDate = "";
  if (task.expDate) {
    expDate = task.expDate.split(" ")[0];
  }
  return expDate == today;
}

//funcion auxiliar para transformar la fecha
function formatDate(dateStr) {
  if (!dateStr) {
    return("No deadline");
  }
  let date = new Date(dateStr);
  return date.toLocaleDateString("en-GB");
}

//filtros para tareas
const filteredTasks = computed(function () {
  if (filter.value === "pending") {
    return tasks.value.filter((t) => t.done == false);
  } else if (filter.value === "done") {
    return tasks.value.filter((t) => t.done == true);
  } else if (filter.value === "hard") {
    return tasks.value.filter((t) => t.difficulty == "hard");
  } else if (filter.value === "medium") {
    return tasks.value.filter((t) => t.difficulty == "medium");
  } else if (filter.value === "easy") {
    return tasks.value.filter((t) => t.difficulty == "easy");
  } else if (filter.value === "overdue") {
    return tasks.value.filter((t) => isOverdue(t));
  } else {
    return tasks.value;
  }
});

//funcion auxiliar para contar las tareas disponibles segun dificultad
function countByDiff(diff) {
  let lista = tasks.value.filter(
    (t) => t.difficulty === diff && t.done == false,
  );
  return lista.length;
}

//cuando carga la vista, se dispara la busqueda
onMounted(function () {
  fetchTasks();
});
</script>

<template>
  <div class="tasks-container">
    <div class="d-flex align-items-end justify-content-between mb-4 fade-up">
      <h1 class="page-title"><em>manage your</em> Tasks</h1>
      <button
        class="btn-dopamine btn-dopamine-primary"
        @click="router.push('/tasks/new')"
      >
        <i class="bi bi-plus me-1"></i> New Task
      </button>
    </div>

    <div v-if="error" class="error-text mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ error }}
    </div>

    <div v-if="loading" class="loading-text">
      <div class="spinner-border spinner-border-sm me-2" role="status"></div>
      Loading...
    </div>

    <template v-else>
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
              <div class="stat-num">{{ tasks.length }}</div>
              <div class="stat-label">Total tasks</div>
            </div>
            <i class="bi bi-list-check stat-icon ms-3"></i>
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-4 fade-up delay-2">
        <button
          class="filter-tab"
          :class="{ active: filter === 'all' }"
          @click="filter = 'all'"
        >
          All ({{ tasks.length }})
        </button>
        <button
          class="filter-tab"
          :class="{ active: filter === 'pending' }"
          @click="filter = 'pending'"
        >
          Pending
        </button>
        <button
          class="filter-tab"
          :class="{ active: filter === 'done' }"
          @click="filter = 'done'"
        >
          Done
        </button>
        <button
          class="filter-tab"
          :class="{ active: filter === 'hard' }"
          @click="filter = 'hard'"
        >
          Hard
        </button>
        <button
          class="filter-tab"
          :class="{ active: filter === 'medium' }"
          @click="filter = 'medium'"
        >
          Medium
        </button>
        <button
          class="filter-tab"
          :class="{ active: filter === 'easy' }"
          @click="filter = 'easy'"
        >
          Easy
        </button>
        <button
          class="filter-tab"
          :class="{ active: filter === 'overdue' }"
          @click="filter = 'overdue'"
        >
          Overdue
        </button>
      </div>

      <div v-if="filteredTasks.length === 0" class="empty-state fade-up">
        <i class="bi bi-check-all empty-icon"></i>
        <p class="empty-title">No tasks found</p>
        <button
          class="btn-dopamine btn-dopamine-primary mt-2"
          @click="router.push('/tasks/new')"
        >
          <i class="bi bi-plus me-1"></i> Create your first task
        </button>
      </div>

      <div v-else class="d-flex flex-column gap-3">
        <article
          v-for="(task, index) in filteredTasks"
          :key="task.id"
          class="task-card fade-up"
          :class="
            task.done
              ? 'card-done'
              : task.difficulty === 'hard'
                ? 'card-hard'
                : task.difficulty === 'medium'
                  ? 'card-medium'
                  : 'card-easy'
          "
          :style="{ animationDelay: index * 0.04 + 's' }"
        >
          <div class="task-card-body">
            <button
              class="task-check"
              :class="task.done ? 'task-check-done' : ''"
              @click="toggleDone(task)"
            >
              <i v-if="task.done" class="bi bi-check"></i>
            </button>

            <div
              class="flex-grow-1 task-info-clickable"
              style="min-width: 0; cursor: pointer"
              @click="toggleDetail(task.id)"
            >
              <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                <span
                  class="task-title"
                  :class="
                    task.done
                      ? 'text-decoration-line-through task-title-done'
                      : ''
                  "
                >
                  {{ task.title }}
                </span>
                <span class="bdg" :class="'bdg-' + task.difficulty">{{
                  task.difficulty
                }}</span>
                <span v-if="task.done" class="bdg bdg-done"
                  ><i class="bi bi-check me-1"></i>Done</span
                >
                <span v-else-if="isOverdue(task)" class="bdg bdg-overdue"
                  ><i class="bi bi-exclamation-triangle me-1"></i>Overdue</span
                >
                <span v-else-if="isDueToday(task)" class="bdg bdg-due"
                  ><i class="bi bi-clock me-1"></i>Due today</span
                >
              </div>

              <p v-if="task.descrip" class="task-descrip">{{ task.descrip }}</p>
              <div class="task-date">
                <i class="bi bi-calendar3 me-1"></i
                >{{ formatDate(task.expDate) }}
              </div>
            </div>

            <div class="d-flex gap-2 align-items-center flex-shrink-0">
              <button
                class="btn-dopamine btn-dopamine-ghost task-icon-btn"
                @click="router.push('/tasks/edit/' + task.id)"
              >
                <i class="bi bi-pencil"></i>
              </button>
              <button
                class="btn-dopamine btn-dopamine-danger task-icon-btn"
                @click="deleteTask(task.id)"
              >
                <i class="bi bi-trash"></i>
              </button>
              <button
                class="btn-dopamine btn-dopamine-ghost task-icon-btn"
                @click="toggleDetail(task.id)"
              >
                <i
                  class="bi"
                  :class="
                    expandedTaskId === task.id
                      ? 'bi-chevron-up'
                      : 'bi-chevron-down'
                  "
                ></i>
              </button>
            </div>
          </div>

          <div
            v-if="expandedTaskId === task.id"
            :id="'task-detail-' + task.id"
            class="task-detail-panel"
          >
            <div v-if="loadingDetail" class="loading-text py-3">
              <div class="spinner-border spinner-border-sm me-2"></div>
              Loading details...
            </div>

            <template v-else-if="taskDetail">
              <div class="row g-3 mb-3">
                <div class="col-6" v-if="taskDetail.startDate">
                  <div class="detail-info-item">
                    <span class="detail-info-label"
                      ><i class="bi bi-calendar-plus me-1"></i>Start date</span
                    >
                    <span class="detail-info-value">{{
                      formatDate(taskDetail.startDate)
                    }}</span>
                  </div>
                </div>
                <div class="col-6">
                  <div class="detail-info-item">
                    <span class="detail-info-label"
                      ><i class="bi bi-calendar-x me-1"></i>Due date</span
                    >
                    <span class="detail-info-value">{{
                      formatDate(taskDetail.expDate)
                    }}</span>
                  </div>
                </div>
              </div>

              <div v-if="taskDetail.url" class="mb-3">
                <div class="detail-info-item">
                  <span class="detail-info-label"
                    ><i class="bi bi-link-45deg me-1"></i>Link</span
                  >
                  <a
                    :href="taskDetail.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="detail-link"
                  >
                    {{ taskDetail.url }}
                    <i class="bi bi-box-arrow-up-right ms-1"></i>
                  </a>
                </div>
              </div>

              <div
                v-if="taskDetail.checklist && taskDetail.checklist.length > 0"
              >
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
                    <span
                      class="checklist-item-text"
                      :class="item.done ? 'text-decoration-line-through' : ''"
                      >{{ item.title }}</span
                    >
                  </div>
                </div>
              </div>
              <p v-else class="detail-empty-text">
                <i class="bi bi-info-circle me-1"></i> No steps added.
                <button
                  class="btn-link-style ms-1"
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
  .tasks-container {
    padding: 1.5rem 1rem 5rem;
  }
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
.task-card:hover {
  box-shadow: 0 4px 16px rgba(92, 51, 23, 0.11);
}

/* laterales de los card segun dificultad */
.card-hard {
  border-left-color: var(--state-error);
}
.card-medium {
  border-left-color: var(--state-warn);
}
.card-easy {
  border-left-color: var(--state-ok);
}
.card-done {
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
.task-title {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--cinnamon-dark);
}
.task-title-done {
  color: var(--cinnamon-soft);
}
.task-descrip {
  font-size: 0.88rem;
  font-weight: 500;
  color: var(--cinnamon-soft);
  margin: 0.25rem 0 0;
}
.task-date {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--cinnamon-soft);
  margin-top: 0.4rem;
  display: flex;
  align-items: center;
}

/*boton checkbox circular */
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
  flex-shrink: 0;
  margin-top: 2px;
}
.task-check:hover {
  border-color: var(--cinnamon-mid);
  background: var(--bg-subtle);
}
.task-check-done {
  background: var(--state-ok);
  border-color: var(--state-ok);
  color: #fff;
}

/* botones de vistas */
.task-icon-btn {
  width: 44px;
  height: 44px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
}
.task-info-clickable:focus-visible {
  outline: 2px dashed var(--cinnamon-mid);
  border-radius: 6px;
  outline-offset: 4px;
}

/* despliegue interno */
.task-detail-panel {
  padding: 1rem 1.3rem 1.2rem;
  border-top: 1.5px solid #f0ebe3;
  background: var(--bg-card);
}
.detail-info-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.detail-info-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
}
.detail-info-value {
  font-size: 0.92rem;
  font-weight: 600;
  color: var(--cinnamon-dark);
}
.detail-link {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--btn-info);
  text-decoration: underline;
  word-break: break-all;
}

/* bloques del checklist interior */
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
  border-color: #c8e4ca;
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
  flex-shrink: 0;
}
.checklist-check-btn.checked {
  background: var(--state-ok);
  border-color: var(--state-ok);
  color: #fff;
}
.checklist-item-text {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--cinnamon-dark);
}
.btn-link-style {
  background: none;
  border: none;
  color: var(--cinnamon-dark);
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: underline;
  cursor: pointer;
  padding: 0;
}
</style>
