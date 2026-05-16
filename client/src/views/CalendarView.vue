<script setup>
// imports para Vue, FullCalendar y API
import { ref, onMounted } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()

const loading      = ref(false)
const errorMessage = ref('')

// evento seleccionado para el panel lateral
const selectedEvent = ref(null)

// configuracion de FullCalendar
const calendarOptions = ref({
  plugins:      [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView:  'dayGridMonth',
  locale:       'en-GB',
  height:       'auto',
  headerToolbar: {
    left:   'prev,next today',
    center: 'title',
    right:  'dayGridMonth,timeGridWeek'
  },
  // events como función — FullCalendar pasa start y end automáticamente
  events: function(info, successCallback, failureCallback) {
    let start = info.startStr.split('T')[0]
    let end   = info.endStr.split('T')[0]
    let url   = rutaApi + "?entity=calendar&user_id=" + userStore.user.id
              + "&start=" + start + "&end=" + end

    fetch(url)
      .then(function(res) { return res.json() })
      .then(function(data) {
        successCallback(data)
        loading.value = false
      })
      .catch(function() {
        errorMessage.value = 'Error loading calendar'
        failureCallback()
        loading.value = false
      })
  },
  eventClick:   handleEventClick,
  eventDisplay: 'block',
  dayMaxEvents: 3,
})

// funcion al hacer clic en un evento
function handleEventClick(info) {
  selectedEvent.value = {
    title:      info.event.title,
    start:      info.event.startStr,
    end:        info.event.endStr,
    type:       info.event.extendedProps.type       || '',
    difficulty: info.event.extendedProps.difficulty || '',
    frecuency:  info.event.extendedProps.frecuency  || '',
    descrip:    info.event.extendedProps.descrip    || '',
    done:       info.event.extendedProps.done       || false,
    color:      info.event.backgroundColor
  }
}

// funcion para cerrar panel lateral
function closePanel() {
  selectedEvent.value = null
}

// cuenta regresiva desde ahora hasta la fecha de vencimiento de una tarea
function getCountdown(dateStr) {
  if (!dateStr) { return null }

  let now    = new Date()
  let target = new Date(dateStr)
  let diffMs = target - now

  if (diffMs < 0) { return { expired: true } }

  let diffSecs    = Math.floor(diffMs / 1000)
  let diffMinutes = Math.floor(diffSecs / 60)
  let diffHours   = Math.floor(diffMinutes / 60)
  let diffDays    = Math.floor(diffHours / 24)
  let diffWeeks   = Math.floor(diffDays / 7)
  let diffMonths  = Math.floor(diffDays / 30)

  if (diffHours < 24) {
    let h = diffHours
    let m = diffMinutes % 60
    return { mode: 'hours', hours: h, minutes: m }
  }

  if (diffDays < 7) {
    return { mode: 'days', days: diffDays }
  }

  if (diffDays < 30) {
    let weeks      = diffWeeks
    let remainDays = diffDays - (weeks * 7)
    return { mode: 'weeks', weeks: weeks, days: remainDays }
  }

  let months      = diffMonths
  let remainDays2 = diffDays - (months * 30)
  let remainWeeks = Math.floor(remainDays2 / 7)
  return { mode: 'months', months: months, weeks: remainWeeks }
}

// funcion para mostrar el tipo en legible
function getTypeLabel(type) {
  if (type === 'task')    { return 'Task' }
  if (type === 'habit')   { return 'Habit' }
  if (type === 'routine') { return 'Routine' }
  return type
}

// icono segun tipo de evento
function getTypeIcon(type) {
  if (type === 'task')    { return 'bi-check2-square' }
  if (type === 'habit')   { return 'bi-arrow-repeat' }
  if (type === 'routine') { return 'bi-list-check' }
  return 'bi-calendar-event'
}
</script>

<template>
  <div class="calendar-container">

    <!-- CABECERA -->
    <div class="d-flex align-items-end justify-content-between mb-4 fade-up">
      <h1 class="page-title">
        <em>plan your</em>
        Calendar
      </h1>
      <!-- LEYENDA -->
      <div class="d-flex gap-3 align-items-center" aria-label="Event types legend">
        <div class="legend-item">
          <span class="legend-dot dot-task" aria-hidden="true"></span>
          <span class="legend-label">Tasks</span>
        </div>
        <div class="legend-item">
          <span class="legend-dot dot-habit" aria-hidden="true"></span>
          <span class="legend-label">Habits</span>
        </div>
        <div class="legend-item">
          <span class="legend-dot dot-routine" aria-hidden="true"></span>
          <span class="legend-label">Routines</span>
        </div>
      </div>
    </div>

    <!-- ERROR -->
    <div v-if="errorMessage" class="error-text mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>{{ errorMessage }}
    </div>

    <div class="row g-4">

      <!-- CALENDARIO -->
      <div :class="selectedEvent ? 'col-12 col-lg-8' : 'col-12'">
        <div class="calendar-card fade-up delay-1">
          <FullCalendar :options="calendarOptions" />
        </div>
      </div>

      <!-- PANEL LATERAL — evento seleccionado -->
      <div v-if="selectedEvent" class="col-12 col-lg-4 fade-up">
        <div class="event-panel" role="complementary" aria-label="Event details">

          <!-- CABECERA DEL PANEL -->
          <div class="event-panel-header" :style="{ borderLeftColor: selectedEvent.color }">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="event-type-badge">
                <i class="bi me-1" :class="getTypeIcon(selectedEvent.type)" aria-hidden="true"></i>
                {{ getTypeLabel(selectedEvent.type) }}
              </span>
              <button
                class="btn-dopamine btn-dopamine-ghost btn-close-panel"
                aria-label="Close event details panel"
                @click="closePanel"
              >
                <i class="bi bi-x" aria-hidden="true"></i>
              </button>
            </div>
            <h2 class="event-panel-title">{{ selectedEvent.title }}</h2>
          </div>

          <!-- DETALLES -->
          <div class="event-panel-body">

            <!-- FECHA -->
            <div v-if="selectedEvent.start" class="event-detail-row">
              <i class="bi bi-calendar3 detail-icon" aria-hidden="true"></i>
              <div>
                <div class="detail-label">Date</div>
                <div class="detail-value">{{ selectedEvent.start }}</div>
              </div>
            </div>

            <!-- CUENTA REGRESIVA — solo tareas no completadas ✅ fuera del div de fecha -->
            <div
              v-if="selectedEvent.type === 'task' && !selectedEvent.done && selectedEvent.end"
              class="event-detail-row"
            >
              <i class="bi bi-hourglass-split detail-icon" aria-hidden="true"></i>
              <div>
                <div class="detail-label">Time remaining</div>
                <div class="detail-value">
                  <span v-if="getCountdown(selectedEvent.end).expired" class="countdown-expired">
                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Overdue
                  </span>
                  <span v-else-if="getCountdown(selectedEvent.end).mode === 'hours'" class="countdown-urgent">
                    <i class="bi bi-clock me-1" aria-hidden="true"></i>
                    {{ getCountdown(selectedEvent.end).hours }}h
                    {{ getCountdown(selectedEvent.end).minutes }}min
                  </span>
                  <span v-else-if="getCountdown(selectedEvent.end).mode === 'days'" class="countdown-days">
                    <i class="bi bi-calendar2 me-1" aria-hidden="true"></i>
                    {{ getCountdown(selectedEvent.end).days }} day(s)
                  </span>
                  <span v-else-if="getCountdown(selectedEvent.end).mode === 'weeks'" class="countdown-weeks">
                    <i class="bi bi-calendar-week me-1" aria-hidden="true"></i>
                    {{ getCountdown(selectedEvent.end).weeks }} week(s)
                    <span v-if="getCountdown(selectedEvent.end).days > 0">
                      {{ getCountdown(selectedEvent.end).days }} day(s)
                    </span>
                  </span>
                  <span v-else class="countdown-months">
                    <i class="bi bi-calendar-month me-1" aria-hidden="true"></i>
                    {{ getCountdown(selectedEvent.end).months }} month(s)
                    <span v-if="getCountdown(selectedEvent.end).weeks > 0">
                      {{ getCountdown(selectedEvent.end).weeks }} week(s)
                    </span>
                  </span>
                </div>
              </div>
            </div>

            <!-- DIFICULTAD -->
            <div v-if="selectedEvent.difficulty" class="event-detail-row">
              <i class="bi bi-bar-chart detail-icon" aria-hidden="true"></i>
              <div>
                <div class="detail-label">Difficulty</div>
                <div class="detail-value">
                  <span class="bdg" :class="'bdg-' + selectedEvent.difficulty">
                    {{ selectedEvent.difficulty }}
                  </span>
                </div>
              </div>
            </div>

            <!-- FRECUENCIA -->
            <div v-if="selectedEvent.frecuency" class="event-detail-row">
              <i class="bi bi-arrow-repeat detail-icon" aria-hidden="true"></i>
              <div>
                <div class="detail-label">Frequency</div>
                <div class="detail-value">
                  <span class="bdg" :class="selectedEvent.frecuency === 'daily' ? 'bdg-daily' : selectedEvent.frecuency === 'weekly' ? 'bdg-weekly' : 'bdg-monthly'">
                    {{ selectedEvent.frecuency }}
                  </span>
                </div>
              </div>
            </div>

            <!-- DESCRIPCIÓN -->
            <div v-if="selectedEvent.descrip" class="event-detail-row">
              <i class="bi bi-text-left detail-icon" aria-hidden="true"></i>
              <div>
                <div class="detail-label">Description</div>
                <div class="detail-value">{{ selectedEvent.descrip }}</div>
              </div>
            </div>

            <!-- ESTADO -->
            <div class="event-detail-row">
              <i class="bi detail-icon" :class="selectedEvent.done ? 'bi-check-circle-fill' : 'bi-circle'" aria-hidden="true"></i>
              <div>
                <div class="detail-label">Status</div>
                <div class="detail-value">
                  <span class="bdg" :class="selectedEvent.done ? 'bdg-done' : 'bdg-daily'">
                    {{ selectedEvent.done ? 'Completed' : 'Pending' }}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

    </div>

  </div>
</template>

<style scoped>
/* CONTENEDOR */
.calendar-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
}

@media (max-width: 768px) {
  .calendar-container { padding: 1.5rem 1rem 4rem; }
}

/* LEYENDA */
.legend-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.legend-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.dot-task    { background: var(--state-error); }
.dot-habit   { background: var(--cinnamon-mid); }
.dot-routine { background: var(--btn-info); }

.legend-label {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--cinnamon-mid);
}

/* CARD DEL CALENDARIO */
.calendar-card {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 12px rgba(92, 51, 23, 0.08);
}

/* ── OVERRIDE FULLCALENDAR ── */
:deep(.fc) {
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-size: 1rem !important;
}

:deep(.fc-toolbar-title) {
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-size: 1.3rem !important;
  font-weight: 700 !important;
  color: var(--cinnamon-dark) !important;
}

:deep(.fc-button) {
  background: var(--btn-primary) !important;
  border-color: var(--btn-primary) !important;
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-size: 0.9rem !important;
  padding: 0.5rem 0.9rem !important;
  border-radius: 8px !important;
  min-height: 44px !important;
}

:deep(.fc-button:hover) {
  background: var(--btn-primary-h) !important;
  border-color: var(--btn-primary-h) !important;
}

:deep(.fc-button-active) {
  background: var(--cinnamon-mid) !important;
  border-color: var(--cinnamon-mid) !important;
}

:deep(.fc-col-header-cell-cushion) {
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-size: 0.9rem !important;
  font-weight: 700 !important;
  color: var(--cinnamon-mid) !important;
  padding: 0.5rem 0 !important;
  text-decoration: none !important;
}

:deep(.fc-daygrid-day-number) {
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-size: 0.95rem !important;
  font-weight: 700 !important;
  color: var(--cinnamon-dark) !important;
  text-decoration: none !important;
  padding: 0.4rem !important;
}

:deep(.fc-day-today) {
  background: var(--vanilla-light) !important;
  opacity: 1 !important;
}

:deep(.fc-day-today .fc-daygrid-day-number) {
  background: var(--cinnamon-dark) !important;
  color: var(--bg-base) !important;
  border-radius: 50% !important;
  width: 32px !important;
  height: 32px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

:deep(.fc-event) {
  border-radius: 6px !important;
  border: none !important;
  padding: 0.15rem 0.4rem !important;
  font-size: 0.82rem !important;
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-weight: 600 !important;
  cursor: pointer !important;
}

:deep(.fc-event:hover) {
  filter: brightness(0.9) !important;
}

:deep(.fc-daygrid-more-link) {
  font-size: 0.8rem !important;
  color: var(--cinnamon-mid) !important;
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
}

:deep(.fc-scrollgrid) {
  border-color: var(--vanilla-mid) !important;
}

:deep(.fc-scrollgrid td),
:deep(.fc-scrollgrid th) {
  border-color: var(--vanilla-light) !important;
}

/* ── PANEL LATERAL ── */
.event-panel {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(92, 51, 23, 0.08);
  position: sticky;
  top: 80px;
}

.event-panel-header {
  padding: 1.2rem 1.4rem;
  border-left: 5px solid var(--vanilla-mid);
  background: var(--bg-base);
}

.event-type-badge {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.event-panel-title {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  margin: 0.4rem 0 0;
  line-height: 1.3;
}

.btn-close-panel {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.event-panel-body {
  padding: 1rem 1.4rem 1.4rem;
}

.event-detail-row {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--bg-subtle);
}

.event-detail-row:last-child {
  border-bottom: none;
}

.detail-icon {
  font-size: 1.1rem;
  color: var(--cinnamon-soft);
  margin-top: 2px;
  flex-shrink: 0;
}

.detail-label {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.72rem;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 0.2rem;
}

.detail-value {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--cinnamon-dark);
}

.countdown-expired { color: var(--state-error);  font-weight: 700; }
.countdown-urgent  { color: var(--state-error);  font-weight: 700; }
.countdown-days    { color: var(--state-warn);   font-weight: 700; }
.countdown-weeks   { color: var(--cinnamon-mid); font-weight: 700; }
.countdown-months  { color: var(--state-ok);     font-weight: 700; }
</style>