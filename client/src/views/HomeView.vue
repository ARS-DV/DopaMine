<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

const userStore = useUserStore();
const router = useRouter();

//variables reactivas globales
const tasksList = ref([]);
const habitsList = ref([]);
const routinesList = ref([]);
const isLoading = ref(true);
const errorMessage = ref("");

//nivel de energia del usuario (low, medium, high)
const energyLevel = ref(userStore.user.energy || "medium");

//funcion principal que carga tareas, habitos y rutinas en cadena
function loadAllData() {
  isLoading.value = true;
  errorMessage.value = "";

  //la url de tareas cambia segun el nivel de energia
  let tasksUrl = rutaApi + "?entity=tasks&user_id=" + userStore.user.id;
  if (energyLevel.value == "high") {
    tasksUrl = tasksUrl + "&week=1"; //energia alta: tareas de toda la semana
  } else {
    tasksUrl = tasksUrl + "&today=1"; //resto: solo tareas de hoy
  }

  //cargamos tareas, luego habitos, luego rutinas en cadena
  fetch(tasksUrl)
    .then(function (res) {
      return res.json();
    })
    .then(function (dataTasks) {
      tasksList.value = dataTasks;
      return fetch(
        rutaApi + "?entity=habits&user_id=" + userStore.user.id + "&today=1",
      );
    })
    .then(function (res) {
      return res.json();
    })
    .then(function (dataHabits) {
      habitsList.value = dataHabits;
      return fetch(
        rutaApi + "?entity=routines&user_id=" + userStore.user.id + "&today=1",
      );
    })
    .then(function (res) {
      return res.json();
    })
    .then(function (dataRoutines) {
      routinesList.value = dataRoutines;
      isLoading.value = false;
    })
    .catch(function () {
      errorMessage.value = "Error loading data";
      isLoading.value = false;
    });
}

//funcion para cambiar el nivel de energia y recargar datos
function updateEnergy(newLevel) {
  energyLevel.value = newLevel;

  fetch(rutaApi + "?entity=users&id=" + userStore.user.id, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ energy: newLevel }),
  }).then(function () {
    userStore.user.energy = newLevel;
    loadAllData();
  });
}

//funcion para marcar una tarea como hecha o no hecha
function checkTask(task) {
  let status = 0;
  if (task.done == false || task.done == 0) {
    status = 1;
  }

  fetch(rutaApi + "?entity=tasks&id=" + task.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ done: status }),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        if (status === 1) {
          task.done = true;
        } else {
          task.done = false;
        }
      }
    });
}

//funcion para ciclar el estado de un habito (0->1->2->0)
function updateHabitState(habit) {
  let current = habit.done_today;
  if (current == null) {
    current = 0;
  }
  let next = (parseInt(current) + 1) % 3;

  fetch(rutaApi + "?entity=habits&id=" + habit.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ done: next }),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        habit.done_today = next;
      }
    });
}

//funcion para marcar o desmarcar un paso de una rutina
function toggleRoutineStep(routine, step) {
  //alternamos entre hecho y no hecho
  let newStatus = 1;
  if (step.done == true || step.done == 1) {
    newStatus = 0;
  }

  fetch(rutaApi + "?entity=routine_checklist&id=" + step.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ done: newStatus }),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        //actualizamos el estado del paso localmente
        if (newStatus == 1) {
          step.done = true;
        } else {
          step.done = false;
        }
        //recalculamos el progreso de la rutina
        updateRoutineProgress(routine);
      }
    });
}

//funcion para recalcular y guardar el estado de la rutina segun los pasos
function updateRoutineProgress(routine) {
  let steps = routine.checklist || [];
  let total = steps.length;
  if (total == 0) {
    return;
  }

  //contamos los pasos completados
  let done = 0;
  for (let i = 0; i < steps.length; i++) {
    if (steps[i].done == true) {
      done++;
    }
  }

  fetch(rutaApi + "?entity=routines&id=" + routine.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ done_steps: done, total_steps: total }),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        routine.done_today = data.done;
        routine.done_steps = done;
      }
    });
}

//funcion auxiliar para el texto del estado del habito
function getHabitText(val) {
  let v = parseInt(val);
  if (v == 2) {
    return "Done";
  } else if (v == 1) {
    return "Tried";
  } else {
    return "Pending";
  }
}

//funcion auxiliar para el icono del estado del habito
function getHabitIcon(val) {
  let v = parseInt(val);
  if (v == 2) {
    return "bi-check-circle-fill";
  } else if (v == 1) {
    return "bi-dash-circle";
  } else {
    return "bi-circle";
  }
}

//saludo segun la hora del dia
const welcomeGreeting = computed(function () {
  let hour = new Date().getHours();
  if (hour < 12) {
    return "Good morning";
  } else if (hour < 18) {
    return "Good afternoon";
  } else {
    return "Good evening";
  }
});

//filtra las tareas pendientes para mostrar en home
const homeFilteredTasks = computed(function () {
  return tasksList.value.filter((t) => t.done == false);
});

//contador de tareas hechas hoy
const tasksDoneCount = computed(function () {
  return tasksList.value.filter((t) => t.done).length;
});

//contador de habitos completados hoy
const habitsDoneCount = computed(function () {
  return habitsList.value.filter((h) => parseInt(h.done_today) == 2).length;
});

//cuando carga la vista, se dispara la carga de datos
onMounted(function () {
  loadAllData();
});
</script>

<template>
  <div class="home-container">
    <!-- saludo con fecha de hoy -->
    <div class="mb-3 fade-up">
      <p class="greeting-sub mb-1">
        {{
          new Date().toLocaleDateString("en-GB", {
            weekday: "long",
            day: "numeric",
            month: "long",
          })
        }}
      </p>
      <h1 class="page-title">
        {{ welcomeGreeting }},
        {{ userStore.user.nickName }}
      </h1>
    </div>

    <!-- contadores de tareas y habitos de hoy -->
    <div class="row g-3 mb-4 fade-up delay-1">
      <div class="col-6">
        <div class="stat-strip strip-info">
          <div>
            <div class="stat-num">{{ tasksDoneCount }}</div>
            <div class="stat-label">Tasks done</div>
          </div>
          <i class="bi bi-check2-square stat-icon ms-3"></i>
        </div>
      </div>
      <div class="col-6">
        <div class="stat-strip strip-ok">
          <div>
            <div class="stat-num">
              {{ habitsDoneCount }}/{{ habitsList.length }}
            </div>
            <div class="stat-label">Habits done</div>
          </div>
          <i class="bi bi-arrow-repeat stat-icon ms-3"></i>
        </div>
      </div>
    </div>

    <!-- selector de nivel de energia -->
    <div class="energy-card mb-4 fade-up delay-2">
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-lightning-charge energy-icon"></i>
        <span class="energy-label">Energy level</span>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-4">
          <button
            class="energy-btn w-100"
            :class="energyLevel === 'low' ? 'energy-active-low' : ''"
            @click="updateEnergy('low')"
          >
            <i class="bi bi-battery me-1"></i> Low
          </button>
        </div>
        <div class="col-4">
          <button
            class="energy-btn w-100"
            :class="energyLevel === 'medium' ? 'energy-active-medium' : ''"
            @click="updateEnergy('medium')"
          >
            <i class="bi bi-battery-half me-1"></i> Medium
          </button>
        </div>
        <div class="col-4">
          <button
            class="energy-btn w-100"
            :class="energyLevel === 'high' ? 'energy-active-high' : ''"
            @click="updateEnergy('high')"
          >
            <i class="bi bi-battery-full me-1"></i> High
          </button>
        </div>
      </div>

      <!-- descripcion del nivel de energia activo -->
      <p class="energy-desc mb-0">
        <i class="bi bi-info-circle me-1"></i>
        <span v-if="energyLevel === 'low'"
          >Showing urgent tasks and today's habits only</span
        >
        <span v-else-if="energyLevel === 'medium'"
          >Showing today's tasks, habits and routines</span
        >
        <span v-else>Showing this week's tasks, habits and routines</span>
      </p>
    </div>

    <!-- mensaje de error -->
    <div v-if="errorMessage" class="error-text mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ errorMessage }}
    </div>

    <!-- spinner de carga -->
    <div v-if="isLoading" class="loading-text">
      <div class="spinner-border spinner-border-sm me-2" role="status"></div>
      Loading...
    </div>

    <template v-else>
      <!-- seccion de tareas del dia -->
      <section class="mb-5 fade-up delay-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="section-title mb-0">
            <i class="bi bi-check2-square me-2"></i>Tasks
          </h2>
          <button
            class="btn-dopamine btn-dopamine-ghost"
            @click="router.push('/tasks')"
          >
            See all <i class="bi bi-arrow-right ms-1"></i>
          </button>
        </div>

        <!-- estado vacio de tareas -->
        <div v-if="homeFilteredTasks.length === 0" class="empty-state">
          <i class="bi bi-check-all empty-icon"></i>
          <p class="empty-title">All tasks done!</p>
          <button
            class="btn-dopamine btn-dopamine-primary mt-2"
            @click="router.push('/tasks/new')"
          >
            <i class="bi bi-plus me-1"></i> New task
          </button>
        </div>

        <!-- lista de tareas pendientes -->
        <div v-else class="d-flex flex-column gap-2">
          <div
            v-for="task in homeFilteredTasks"
            :key="task.id"
            class="home-card"
            :class="
              task.difficulty === 'hard'
                ? 'card-hard'
                : task.difficulty === 'easy'
                  ? 'card-easy'
                  : 'card-medium'
            "
          >
            <div class="d-flex align-items-center gap-3">
              <!-- checkbox para marcar la tarea como hecha -->
              <button
                class="task-check-btn"
                :class="task.done ? 'task-check-done' : ''"
                :title="task.done ? 'Uncheck' : 'Mark as done'"
                @click="checkTask(task)"
              >
                <i v-if="task.done" class="bi bi-check"></i>
              </button>
              <span
                class="task-title-text flex-grow-1"
                :class="task.done ? 'text-decoration-line-through' : ''"
              >
                {{ task.title }}
              </span>
              <span class="bdg" :class="'bdg-' + task.difficulty">{{
                task.difficulty
              }}</span>
            </div>
          </div>
        </div>
      </section>

      <!-- seccion de habitos del dia -->
      <section v-if="habitsList.length > 0" class="mb-5 fade-up delay-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="section-title mb-0">
            <i class="bi bi-arrow-repeat me-2"></i>Habits
          </h2>
          <button
            class="btn-dopamine btn-dopamine-ghost"
            @click="router.push('/habits')"
          >
            See all <i class="bi bi-arrow-right ms-1"></i>
          </button>
        </div>

        <div class="d-flex flex-column gap-2">
          <div v-for="habit in habitsList" :key="habit.id" class="home-card">
            <div class="d-flex align-items-center gap-3">
              <!-- boton de estado del habito -->
              <button
                class="habit-state-btn"
                :class="'state-' + parseInt(habit.done_today || 0)"
                :title="'State: ' + getHabitText(habit.done_today)"
                @click="updateHabitState(habit)"
              >
                <i class="bi" :class="getHabitIcon(habit.done_today)"></i>
              </button>
              <span class="task-title-text flex-grow-1">
                <span v-if="habit.icon">{{ habit.icon }} </span
                >{{ habit.title }}
              </span>
              <span
                class="bdg"
                :class="
                  parseInt(habit.done_today) == 2
                    ? 'bdg-done'
                    : parseInt(habit.done_today) == 1
                      ? 'bdg-tried'
                      : 'bdg-daily'
                "
              >
                {{ getHabitText(habit.done_today) }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- estado vacio de habitos -->
      <section v-else class="mb-5 fade-up delay-4">
        <h2 class="section-title">
          <i class="bi bi-arrow-repeat me-2"></i>Habits
        </h2>
        <div class="empty-state">
          <i class="bi bi-plus-circle empty-icon"></i>
          <p class="empty-title">No habits yet</p>
          <button
            class="btn-dopamine btn-dopamine-primary mt-2"
            @click="router.push('/habits/new')"
          >
            <i class="bi bi-plus me-1"></i> Create your first habit
          </button>
        </div>
      </section>

      <!-- seccion de rutinas (solo si energia no es baja) -->
      <section v-if="energyLevel !== 'low'" class="mb-5 fade-up delay-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="section-title mb-0">
            <i class="bi bi-list-check me-2"></i>Routines
          </h2>
          <button
            class="btn-dopamine btn-dopamine-ghost"
            @click="router.push('/routines')"
          >
            See all <i class="bi bi-arrow-right ms-1"></i>
          </button>
        </div>

        <!-- estado vacio de rutinas -->
        <div v-if="routinesList.length === 0" class="empty-state">
          <i class="bi bi-calendar-x empty-icon"></i>
          <p class="empty-title">No routines for today</p>
          <button
            class="btn-dopamine btn-dopamine-primary mt-2"
            @click="router.push('/routines/new')"
          >
            <i class="bi bi-plus me-1"></i> Create your first routine
          </button>
        </div>

        <!-- lista de rutinas de hoy -->
        <div v-else class="d-flex flex-column gap-3">
          <div
            v-for="routine in routinesList"
            :key="routine.id"
            class="home-card routine-card-expanded"
            :class="
              parseInt(routine.done_today) == 2
                ? 'card-easy'
                : parseInt(routine.done_today) == 1
                  ? 'card-medium'
                  : ''
            "
          >
            <!-- cabecera de la rutina con icono de estado y progreso -->
            <div class="d-flex align-items-center gap-3 mb-2">
              <i
                class="bi routine-state-icon"
                :class="
                  parseInt(routine.done_today) == 2
                    ? 'bi-check-circle-fill text-success'
                    : parseInt(routine.done_today) == 1
                      ? 'bi-dash-circle text-warning'
                      : 'bi-circle'
                "
              ></i>
              <div class="flex-grow-1">
                <div class="task-title-text">
                  <span v-if="routine.icon">{{ routine.icon }} </span
                  >{{ routine.title }}
                </div>
                <!-- barra de progreso de pasos -->
                <div class="progress mt-1" style="height: 5px">
                  <div
                    class="progress-bar"
                    :style="{
                      width:
                        routine.total_steps > 0
                          ? ((routine.done_steps || 0) / routine.total_steps) *
                              100 +
                            '%'
                          : '0%',
                      backgroundColor: 'var(--state-ok)',
                    }"
                  ></div>
                </div>
                <small class="progress-text">
                  {{ routine.done_steps || 0 }}/{{
                    routine.total_steps || 0
                  }}
                  steps
                </small>
              </div>
            </div>

            <!-- pasos de la rutina: se muestran si tiene checklist -->
            <div
              v-if="routine.checklist && routine.checklist.length > 0"
              class="routine-steps"
            >
              <div
                v-for="step in routine.checklist"
                :key="step.id"
                class="routine-step-item"
                :class="step.done ? 'step-done' : ''"
              >
                <!-- boton para marcar el paso -->
                <button
                  class="step-check-btn"
                  :class="step.done ? 'step-check-done' : ''"
                  :title="step.done ? 'Uncheck step' : 'Check step'"
                  @click="toggleRoutineStep(routine, step)"
                >
                  <i v-if="step.done" class="bi bi-check"></i>
                </button>
                <span
                  class="step-text"
                  :class="step.done ? 'text-decoration-line-through' : ''"
                >
                  {{ step.title }}
                </span>
              </div>
            </div>

            <!-- mensaje si la rutina no tiene pasos -->
            <p v-else class="no-steps-text">
              <i class="bi bi-info-circle me-1"></i>No steps added yet
            </p>
          </div>
        </div>
      </section>

      <!-- boton de ver estadisticas mensuales -->
      <section class="fade-up delay-5">
        <button
          class="btn-dopamine btn-dopamine-primary w-100 stats-btn"
          @click="router.push('/progress')"
        >
          <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center gap-3">
              <i class="bi bi-bar-chart-line stats-btn-icon"></i>
              <div class="text-start">
                <div class="stats-btn-title">View Monthly Report</div>
                <div class="stats-btn-sub">
                  {{
                    new Date().toLocaleString("en-GB", {
                      month: "long",
                      year: "numeric",
                    })
                  }}
                </div>
              </div>
            </div>
            <i class="bi bi-arrow-right"></i>
          </div>
        </button>
      </section>
    </template>
  </div>
</template>

<style scoped>
/* contenedor general */
.home-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: "Atkinson Hyperlegible", sans-serif;
}

@media (max-width: 768px) {
  .home-container {
    padding: 1.5rem 1rem 5rem;
  }
}

/* texto de la fecha encima del saludo */
.greeting-sub {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1.5px;
}

/* titulos de seccion */
.section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
}

/* tarjeta del selector de energia */
.energy-card {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 12px;
  padding: 1.3rem 1.5rem;
  box-shadow: 0 2px 8px rgba(92, 51, 23, 0.07);
}

.energy-icon {
  color: var(--cinnamon-soft);
  font-size: 1.1rem;
}

.energy-label {
  font-size: 0.8rem;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1.5px;
  font-weight: 600;
}

/* botones de nivel de energia */
.energy-btn {
  border: 1.5px solid var(--vanilla-mid);
  color: var(--cinnamon-mid);
  background: var(--bg-subtle);
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.6rem 0.5rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
  min-height: 44px;
}
.energy-btn:hover {
  background: var(--vanilla-light);
  color: var(--cinnamon-dark);
}

/* colores del nivel activo */
.energy-active-low {
  background: var(--state-error-bg) !important;
  border-color: var(--state-error) !important;
  color: #7a2020 !important;
  font-weight: 700 !important;
}
.energy-active-medium {
  background: var(--state-warn-bg) !important;
  border-color: var(--state-warn) !important;
  color: #7a5a00 !important;
  font-weight: 700 !important;
}
.energy-active-high {
  background: var(--state-ok-bg) !important;
  border-color: var(--state-ok) !important;
  color: #3a6e3e !important;
  font-weight: 700 !important;
}

.energy-desc {
  font-size: 0.82rem;
  color: var(--cinnamon-soft);
}

/* tarjeta base para tareas, habitos y rutinas */
.home-card {
  background: var(--bg-base);
  border: 1.5px solid var(--vanilla-mid);
  border-left: 4px solid var(--vanilla-mid);
  border-radius: 10px;
  padding: 0.85rem 1.1rem;
  box-shadow: 0 1px 6px rgba(92, 51, 23, 0.06);
  transition: box-shadow 0.15s;
}
.home-card:hover {
  box-shadow: 0 3px 12px rgba(92, 51, 23, 0.1);
}

/* colores del borde segun dificultad o estado */
.card-hard {
  border-left-color: var(--state-error);
}
.card-medium {
  border-left-color: var(--state-warn);
}
.card-easy {
  border-left-color: var(--state-ok);
}

/* titulo dentro de la card */
.task-title-text {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
}

/* checkbox de tarea */
.task-check-btn {
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
  font-size: 0.85rem;
  flex-shrink: 0;
}
.task-check-btn:hover {
  border-color: var(--cinnamon-mid);
}
.task-check-done {
  background: var(--state-ok);
  border-color: var(--state-ok);
  color: #fff;
}

/* boton de estado del habito */
.habit-state-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1.5px solid var(--vanilla-mid);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.habit-state-btn:hover {
  border-color: var(--cinnamon-mid);
}
.habit-state-btn.state-0 {
  color: var(--vanilla-mid);
}
.habit-state-btn.state-1 {
  color: var(--state-warn);
  border-color: var(--state-warn);
  background: var(--state-warn-bg);
}
.habit-state-btn.state-2 {
  color: var(--state-ok);
  border-color: var(--state-ok);
  background: var(--state-ok-bg);
}

/* icono de estado de rutina */
.routine-state-icon {
  font-size: 1.3rem;
  flex-shrink: 0;
  color: var(--vanilla-mid);
}

.progress-text {
  font-size: 0.72rem;
  color: var(--cinnamon-soft);
}

/* rutina expandida con pasos visibles */
.routine-card-expanded {
  padding-bottom: 0.6rem;
}

/* lista de pasos de la rutina */
.routine-steps {
  margin-top: 0.6rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  padding-top: 0.6rem;
  border-top: 1px solid var(--vanilla-light);
}

/* fila de cada paso */
.routine-step-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.4rem 0.5rem;
  border-radius: 6px;
  transition: background 0.1s;
}
.routine-step-item:hover {
  background: var(--bg-subtle);
}
.routine-step-item.step-done {
  opacity: 0.6;
}

/* checkbox del paso */
.step-check-btn {
  width: 22px;
  height: 22px;
  border-radius: 4px;
  border: 2px solid var(--vanilla-mid);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 0.7rem;
  flex-shrink: 0;
}
.step-check-btn:hover {
  border-color: var(--cinnamon-mid);
}
.step-check-done {
  background: var(--state-ok);
  border-color: var(--state-ok);
  color: #fff;
}

/* texto del paso */
.step-text {
  font-size: 0.88rem;
  font-weight: 500;
  color: var(--cinnamon-dark);
}

/* texto cuando no hay pasos */
.no-steps-text {
  font-size: 0.82rem;
  color: var(--cinnamon-soft);
  margin: 0.5rem 0 0;
}

/* boton grande de estadisticas */
.stats-btn {
  padding: 1.2rem 1.5rem;
  border-radius: 12px;
}
.stats-btn-icon {
  font-size: 1.4rem;
  background: rgba(255, 255, 255, 0.15);
  padding: 0.5rem;
  border-radius: 8px;
}
.stats-btn-title {
  font-size: 0.95rem;
  font-weight: 700;
}
.stats-btn-sub {
  font-size: 0.75rem;
  opacity: 0.8;
}

@media (max-width: 576px) {
  .energy-btn {
    font-size: 0.78rem;
    padding: 0.5rem 0.3rem;
  }
}
</style>
