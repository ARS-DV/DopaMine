<script setup>
//imports para vue, router y api
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

const userStore = useUserStore();
const router = useRouter();

const routines = ref([]);
const loading = ref(true);
const error = ref("");
const filter = ref("all");

//control de que rutina esta abierta
const expandedRoutines = ref({});

//funcion auxiliar para abrir o cerrar una rutina
function toggleExpand(id) {
  if (expandedRoutines.value[id] == true) {
    expandedRoutines.value[id] = false;
  } else {
    expandedRoutines.value[id] = true;
  }
}

// funcion principal para buscar rutinas
async function fetchRoutines() {
  loading.value = true;
  error.value = "";

  let url = rutaApi + "?entity=routines&user_id=" + userStore.user.id;

  fetch(url)
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      routines.value = data;
      loading.value = false;
    })
    .catch(function () {
      error.value = "Error loading routines";
      loading.value = false;
    });
}

// funcion para cambiar el estado de un paso de la rutina
async function toggleRoutineStep(routine, step) {
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
        if (newStatus == 1) {
          step.done = true;
        } else {
          step.done = false;
        }
        updateRoutineProgress(routine);
      }
    });
}

// funcion para calcular el progreso de la rutina
async function updateRoutineProgress(routine) {
  let stepList = routine.checklist || [];
  let totalItems = stepList.length;
  if (totalItems == 0) return;

  let completedSteps = 0;
  for (let j = 0; j < stepList.length; j++) {
    if (stepList[j].done == true) {
      completedSteps++;
    }
  }

  let totalDone = completedSteps;
  let percentage = (totalDone / totalItems) * 100;

  let finalState = 0;
  if (percentage === 100) {
    finalState = 2;
  } else if (percentage >= 50) {
    finalState = 1;
  }

  fetch(rutaApi + "?entity=routines&id=" + routine.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      done: finalState,
      done_steps: totalDone,
      total_steps: totalItems,
    }),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        routine.done_today = finalState;
        routine.done_steps = totalDone;
        routine.total_steps = totalItems;
        if (data.current_streak !== undefined) {
          routine.streak = data.current_streak;
        }
      }
    });
}

// funcion para borrar rutina
async function deleteRoutine(id) {
  let check = confirm("Delete this routine?");
  if (check == false) return;

  fetch(rutaApi + "?entity=routines&id=" + id, { method: "DELETE" })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        if (expandedRoutines.value[id]) {
          expandedRoutines.value[id] = false;
        }
        routines.value = routines.value.filter((r) => r.id !== id);
      } else {
        error.value = "Error deleting routine";
      }
    });
}

//funcion auxiliar para el texto de estado
function getStatusLabel(val) {
  let v = parseInt(val);
  if (v == 2) {
    return "Done";
  }
  if (v == 1) {
    return "Tried";
  }
  return "Pending";
}

//funcion auxiliar para sacar el porcentaje
function calculatePercentage(routine) {
  let total = 0;
  if (routine.checklist) {
    total = routine.checklist.length;
  }
  if (total == 0) return 0;

  let doneS = 0;
  if (routine.checklist) {
    doneS = routine.checklist.filter((s) => s.done == true).length;
  }
  return Math.round((doneS / total) * 100);
}

//calcular la mejor racha de dias
const bestStreak = computed(function () {
  let max = 0;
  routines.value.forEach(function (r) {
    if ((r.streak || 0) > max) {
      max = r.streak;
    }
  });
  return max;
});

//contar cuantas rutinas se han hecho hoy
const doneTodayCount = computed(function () {
  return routines.value.filter((r) => parseInt(r.done_today) == 2).length;
});

//filtros
const filteredRoutines = computed(function () {
  if (filter.value === "daily") {
    return routines.value.filter((r) => r.frecuency == "daily");
  } else if (filter.value === "weekly") {
    return routines.value.filter((r) => r.frecuency == "weekly");
  } else if (filter.value === "monthly") {
    return routines.value.filter((r) => r.frecuency == "monthly");
  } else if (filter.value === "done") {
    return routines.value.filter((r) => parseInt(r.done_today) == 2);
  } else if (filter.value === "tried") {
    return routines.value.filter((r) => parseInt(r.done_today) == 1);
  } else {
    return routines.value;
  }
});

//cuando carga la vista se buscan las rutinas
onMounted(function () {
  fetchRoutines();
});
</script>

<template>
  <div class="routines-container">
    <div class="d-flex align-items-end justify-content-between mb-4 fade-up">
      <h1 class="page-title">
        <em>stick to your</em>
        Routines
      </h1>
      <button
        class="btn-dopamine btn-dopamine-primary"
        aria-label="Create new routine"
        @click="router.push('/routines/new')"
      >
        <i class="bi bi-plus me-1" aria-hidden="true"></i> New Routine
      </button>
    </div>

    <div class="row g-3 mb-4 fade-up delay-1" aria-label="Routine statistics">
      <div class="col-12 col-sm-4">
        <div class="stat-strip strip-warn">
          <div>
            <div class="stat-num">{{ bestStreak }}</div>
            <div class="stat-label">Best streak</div>
          </div>
          <i class="bi bi-fire stat-icon ms-3" aria-hidden="true"></i>
        </div>
      </div>
      <div class="col-12 col-sm-4">
        <div class="stat-strip strip-ok">
          <div>
            <div class="stat-num">
              {{ doneTodayCount }}/{{ routines.length }}
            </div>
            <div class="stat-label">Done today</div>
          </div>
          <i class="bi bi-check2-square stat-icon ms-3" aria-hidden="true"></i>
        </div>
      </div>
      <div class="col-12 col-sm-4">
        <div class="stat-strip strip-neutral">
          <div>
            <div class="stat-num">{{ routines.length }}</div>
            <div class="stat-label">Total routines</div>
          </div>
          <i class="bi bi-list-check stat-icon ms-3" aria-hidden="true"></i>
        </div>
      </div>
    </div>

    <div v-if="error" class="error-text mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i
      >{{ error }}
    </div>

    <div v-if="loading" class="loading-text" aria-live="polite">
      <div class="spinner-border spinner-border-sm me-2" role="status">
        <span class="visually-hidden">Loading routines...</span>
      </div>
      Loading...
    </div>

    <template v-else>
      <div
        class="d-flex flex-wrap gap-2 mb-4 fade-up delay-2"
        role="group"
        aria-label="Filter routines"
      >
        <button
          class="filter-tab"
          :class="{ active: filter === 'all' }"
          @click="filter = 'all'"
        >
          All ({{ routines.length }})
        </button>
        <button
          class="filter-tab"
          :class="{ active: filter === 'daily' }"
          @click="filter = 'daily'"
        >
          Daily
        </button>
        <button
          class="filter-tab"
          :class="{ active: filter === 'weekly' }"
          @click="filter = 'weekly'"
        >
          Weekly
        </button>
        <button
          class="filter-tab"
          :class="{ active: filter === 'monthly' }"
          @click="filter = 'monthly'"
        >
          Monthly
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
          :class="{ active: filter === 'tried' }"
          @click="filter = 'tried'"
        >
          Tried
        </button>
      </div>

      <div v-if="filteredRoutines.length === 0" class="empty-state fade-up">
        <i class="bi bi-list-check empty-icon" aria-hidden="true"></i>
        <p class="empty-title">No routines found</p>
        <button
          class="btn-dopamine btn-dopamine-primary mt-2"
          @click="router.push('/routines/new')"
        >
          <i class="bi bi-plus me-1" aria-hidden="true"></i> Create your first
          routine
        </button>
      </div>

      <div v-else class="d-flex flex-column gap-3" role="list">
        <article
          v-for="(routine, index) in filteredRoutines"
          :key="routine.id"
          class="routine-card fade-up"
          :class="
            parseInt(routine.done_today) == 2
              ? 'card-done'
              : parseInt(routine.done_today) == 1
                ? 'card-tried'
                : 'card-pending'
          "
          :style="{ animationDelay: index * 0.05 + 's' }"
          role="listitem"
        >
          <div class="rcard-header">
            <div class="flex-grow-1" style="min-width: 0">
              <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                <span
                  v-if="routine.icon"
                  class="rcard-user-icon"
                  aria-hidden="true"
                  >{{ routine.icon }}</span
                >
                <i
                  v-else
                  class="bi rcard-freq-icon"
                  :class="
                    routine.frecuency === 'daily'
                      ? 'bi-sun'
                      : routine.frecuency === 'weekly'
                        ? 'bi-calendar-week'
                        : 'bi-calendar-month'
                  "
                  aria-hidden="true"
                ></i>
                <span class="rcard-title">{{ routine.title }}</span>
                <span
                  class="bdg"
                  :class="
                    routine.frecuency === 'daily'
                      ? 'bdg-daily'
                      : routine.frecuency === 'weekly'
                        ? 'bdg-weekly'
                        : 'bdg-monthly'
                  "
                >
                  {{ routine.frecuency }}
                </span>
                <span
                  v-if="parseInt(routine.done_today) > 0"
                  class="bdg"
                  :class="
                    parseInt(routine.done_today) == 2 ? 'bdg-done' : 'bdg-tried'
                  "
                >
                  {{ getStatusLabel(routine.done_today) }}
                </span>
                <span v-if="routine.hour" class="bdg bdg-info">
                  <i class="bi bi-clock me-1" aria-hidden="true"></i
                  >{{ routine.hour }}
                </span>
              </div>

              <p v-if="routine.descrip" class="rcard-descrip">
                {{ routine.descrip }}
              </p>

              <p
                v-if="
                  routine.frecuency === 'weekly' &&
                  routine.days &&
                  routine.days.length
                "
                class="rcard-days-text"
              >
                <i class="bi bi-calendar-week me-1" aria-hidden="true"></i
                >{{ routine.days.join(", ") }}
              </p>

              <div class="d-flex align-items-center gap-2 mt-2">
                <div
                  class="rcard-progress-bar"
                  role="progressbar"
                  :aria-valuenow="calculatePercentage(routine)"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  :aria-label="calculatePercentage(routine) + '% complete'"
                >
                  <div
                    class="rcard-progress-fill"
                    :style="{
                      width: calculatePercentage(routine) + '%',
                      background:
                        calculatePercentage(routine) === 100
                          ? 'var(--state-ok)'
                          : calculatePercentage(routine) >= 50
                            ? 'var(--state-warn)'
                            : 'var(--cinnamon-mid)',
                    }"
                  ></div>
                </div>
                <small class="rcard-progress-text">
                  {{ routine.done_steps || 0 }}/{{ routine.total_steps || 0 }}
                </small>
              </div>
            </div>

            <div
              class="rcard-streak"
              aria-label="Streak: {{ routine.streak || 0 }} days"
            >
              <i class="bi bi-fire rcard-streak-icon" aria-hidden="true"></i>
              <div class="rcard-streak-num">{{ routine.streak || 0 }}</div>
              <div class="rcard-streak-label">day streak</div>
            </div>
          </div>

          <div class="rcard-footer">
            <button
              class="btn-dopamine btn-dopamine-ghost rcard-btn"
              :aria-label="'Edit routine: ' + routine.title"
              @click="router.push('/routines/edit/' + routine.id)"
            >
              <i class="bi bi-pencil" aria-hidden="true"></i>
            </button>
            <button
              class="btn-dopamine btn-dopamine-danger rcard-btn"
              :aria-label="'Delete routine: ' + routine.title"
              @click="deleteRoutine(routine.id)"
            >
              <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
            <button
              class="btn-dopamine btn-dopamine-ghost rcard-btn"
              :aria-label="
                (expandedRoutines[routine.id] ? 'Collapse' : 'Expand') +
                ' routine: ' +
                routine.title
              "
              :aria-expanded="!!expandedRoutines[routine.id]"
              :aria-controls="'rcard-body-' + routine.id"
              @click="toggleExpand(routine.id)"
            >
              <i
                class="bi"
                :class="
                  expandedRoutines[routine.id]
                    ? 'bi-chevron-up'
                    : 'bi-chevron-down'
                "
                aria-hidden="true"
              ></i>
            </button>
          </div>

          <div
            v-if="expandedRoutines[routine.id]"
            :id="'rcard-body-' + routine.id"
            class="rcard-body"
          >
            <div v-if="routine.checklist && routine.checklist.length > 0">
              <p class="items-label">
                <i class="bi bi-list-check me-1" aria-hidden="true"></i>STEPS
              </p>
              <div
                class="d-flex flex-column gap-2"
                role="list"
                :aria-label="routine.title + ' steps'"
              >
                <div
                  v-for="step in routine.checklist"
                  :key="step.id"
                  class="inner-item step-item"
                  :class="step.done ? 'item-done' : ''"
                  role="listitem"
                >
                  <button
                    class="inner-check"
                    :class="step.done ? 'checked' : ''"
                    :aria-label="
                      (step.done ? 'Uncheck' : 'Check') + ' step: ' + step.title
                    "
                    :aria-pressed="!!step.done"
                    @click="toggleRoutineStep(routine, step)"
                  >
                    <i
                      v-if="step.done"
                      class="bi bi-check"
                      aria-hidden="true"
                    ></i>
                  </button>
                  <span
                    class="inner-text"
                    :class="step.done ? 'text-decoration-line-through' : ''"
                  >
                    {{ step.title }}
                  </span>
                </div>
              </div>
            </div>

            <p v-else class="no-items-text">
              <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
              No steps added yet.
              <button
                class="btn-link-style ms-1"
                @click="router.push('/routines/edit/' + routine.id)"
              >
                Edit to add steps
              </button>
            </p>
          </div>
        </article>
      </div>
    </template>

    <button
      class="fab"
      aria-label="Create new routine"
      @click="router.push('/routines/new')"
    >
      <i class="bi bi-plus" aria-hidden="true"></i>
    </button>
  </div>
</template>

<style scoped>
.routines-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: "Atkinson Hyperlegible", sans-serif;
}

@media (max-width: 768px) {
  .routines-container {
    padding: 1.5rem 1rem 5rem;
  }
}

/* card*/
.routine-card {
  border-radius: 12px;
  border: 1.5px solid var(--vanilla-mid);
  border-left: 4px solid var(--vanilla-mid);
  background: var(--bg-base);
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(92, 51, 23, 0.07);
  transition: box-shadow 0.15s;
}

.routine-card:hover {
  box-shadow: 0 4px 18px rgba(92, 51, 23, 0.12);
}

.card-done {
  border-left-color: var(--state-ok);
  background: var(--state-ok-bg);
}
.card-tried {
  border-left-color: var(--state-warn);
}
.card-pending {
  border-left-color: var(--cinnamon-mid);
}

/* cabecera */
.rcard-header {
  padding: 1.2rem 1.4rem 0.8rem;
  display: flex;
  align-items: flex-start;
  gap: 1.5rem;
}

.rcard-user-icon {
  font-size: 1.3rem;
  flex-shrink: 0;
}

.rcard-freq-icon {
  font-size: 1.1rem;
  color: var(--cinnamon-soft);
  flex-shrink: 0;
  margin-top: 2px;
}

.rcard-title {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--cinnamon-dark);
}

.rcard-descrip {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--cinnamon-soft);
  margin: 0.2rem 0 0;
}

.rcard-days-text {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.82rem;
  font-weight: 600;
  color: #2a5068;
  margin: 0.2rem 0 0;
  display: flex;
  align-items: center;
}

/* barra progreso */
.rcard-progress-bar {
  flex-grow: 1;
  height: 6px;
  background: var(--vanilla-light);
  border-radius: 3px;
  overflow: hidden;
}

.rcard-progress-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.rcard-progress-text {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--cinnamon-soft);
  white-space: nowrap;
}

/* racha */
.rcard-streak {
  flex-shrink: 0;
  text-align: center;
  min-width: 60px;
}

.rcard-streak-icon {
  font-size: 1.3rem;
  color: var(--state-warn);
  display: block;
}

.rcard-streak-num {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--state-warn);
  line-height: 1;
}

.rcard-streak-label {
  font-size: 0.58rem;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* fila interior botones */
.rcard-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.4rem;
  padding: 0 1.4rem 0.8rem;
}

.rcard-btn {
  width: 44px;
  height: 44px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  border-radius: 8px;
}

.rcard-btn:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 3px;
}

/* cuerpo extenso */
.rcard-body {
  padding: 1rem 1.4rem 1.2rem;
  border-top: 1px solid #f0ebe3;
  background: var(--bg-card);
}

.items-label {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1.5px;
  margin-bottom: 0.6rem;
  display: flex;
  align-items: center;
}

/* items */
.inner-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.85rem;
  border-radius: 8px;
  border: 1px solid var(--vanilla-light);
  background: var(--bg-base);
}

.step-item {
  border-left: 3px solid var(--vanilla-mid);
}

.inner-item.item-done {
  background: var(--state-ok-bg);
  border-color: #c8e4ca;
}
.inner-item.item-done.step-item {
  border-left-color: var(--state-ok);
}

.inner-text {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--cinnamon-dark);
  flex-grow: 1;
}

/* checkbox */
.inner-check {
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

.inner-check:hover {
  border-color: var(--cinnamon-mid);
}
.inner-check:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 2px;
}
.inner-check.checked {
  background: var(--state-ok);
  border-color: var(--state-ok);
  color: #fff;
}

.no-items-text {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.85rem;
  color: var(--cinnamon-soft);
  margin: 0;
}

.btn-link-style {
  background: none;
  border: none;
  color: var(--cinnamon-dark);
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 3px;
  cursor: pointer;
  padding: 0;
}

.btn-link-style:hover {
  color: var(--cinnamon-mid);
}
</style>
