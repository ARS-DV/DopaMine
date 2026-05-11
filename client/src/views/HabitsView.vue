<script setup>
//importaciones necesaria para el codigo
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";  //acceso router del navegador entre vistas
import { useUserStore } from "@/stores/userStore"; //acceso Store Pinia del usuario logueado
import { rutaApi } from "@/config.js"; //url de la base API

const userStore = useUserStore();
const router = useRouter();

//variables reactivas para estados, guardar habitos y filtro
const habits = ref([]);
const loading = ref(true);
const error = ref("");
const filter = ref("all");



async function fetchHabits() {
  loading.value = true;
  error.value = "";
  let usuario = userStore.user.id;
  try {
    const res = await fetch(
      rutaApi+'?entity=habits&user_id='+usuario,
    );
    const data = await res.json();
    habits.value = data;
  } catch (e) {
    error.value = "Error loading habits";
  } finally {
    loading.value = false;
  }
}
//fucion para procesar el valor de realizacion de l atarea
function doneLabel(val) {
  if (val == 2){
      return "Done";
  }else if (val == 1){
    return "Tried";
  }else{
    return "Pending";
  } 
}

//funcion principal
async function cycleState(habit) {
  //
  const next = (parseInt(habit.done_today ?? 0) + 1) % 3;

  const res = await fetch(`${rutaApi}?entity=habits&id=${habit.id}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ done: next }),
  });
  const data = await res.json();

  if (data.status === "success") {
    habit.done_today = next;
    habit.streak = data.current_streak;
  }
}
//funcion para borrar el habito
async function deleteHabit(id) {
  if (!confirm("Delete this habit?")) return;

  const res = await fetch(`${rutaApi}?entity=habits&id=${id}`, {
    method: "DELETE",
  });
  const data = await res.json();

  if (data.status === "success") {
    habits.value = habits.value.filter((h) => h.id !== id);
  } else {
    error.value = "Error deleting habit";
  }
}


//funcion para filtrar los habitos
const filteredHabits = computed(() => {
  switch (filter.value) {
    case "daily":
      return habits.value.filter((h) => h.frecuency === "daily");
    case "weekly":
      return habits.value.filter((h) => h.frecuency === "weekly");
    case "monthly":
      return habits.value.filter((h) => h.frecuency === "monthly");
    case "done":
      return habits.value.filter((h) => h.done_today);
    default:
      return habits.value;
  }
});

//funcion para comprobar si hoy toca hacer el habito
function isTodayScheduled(habit) {
  const today = new Date();
  const dayName = today
    .toLocaleString("en-US", { weekday: "long" })
    .toLowerCase();
  const dayOfMonth = today.getDate();

  if (habit.frecuency == "daily") return true;

  if (habit.frecuency == "weekly") {
    return habit.days && habit.days.includes(dayName);
  }

  if (habit.frecuency == "monthly") {
    return parseInt(habit.dayOfMonth) === dayOfMonth;
  }

  return false;
}

// ── LIFECYCLE ─────────────────────────────────────────────────

onMounted(() => {
  fetchHabits();
});
</script>

<template>
  <div>
    <h1>Habits</h1>

    <!-- template si hay algun error -->
    <p v-if="error">{{ error }}</p>

    <!-- template para indicar que esta cargando ls datos -->
    <p v-if="loading">Loading...</p>

    <template v-else>
      <!-- boton para ir a la pantalla de crear un nuevo habito -->
      <button @click="router.push('/habits/new')">+ New Habit</button>

      <!-- botones para filtrar los habitos -->
      <div>
        <button @click="filter = 'all'">All ({{ habits.length }})</button>
        <button @click="filter = 'daily'">Daily</button>
        <button @click="filter = 'weekly'">Weekly</button>
        <button @click="filter = 'monthly'">Monthly</button>
        <button @click="filter = 'done'">Done today</button>
      </div>

      <!-- si no hay habitos, sale habitos no encontrados -->
      <p v-if="filteredHabits.length === 0">No habits found</p>

      <!-- lista de habitos -->
      <ul v-else>
        <li v-for="habit in filteredHabits" :key="habit.id">
          <button @click="cycleState(habit)">
            {{ doneLabel(habit.done_today) }}
          </button>
          <span v-if="!isTodayScheduled(habit)">(not today)</span>

          <span>{{ habit.icon }} {{ habit.title }}</span>
          <span>{{ habit.frecuency }}</span>

          <span v-if="habit.days && habit.days.length">
            ({{ habit.days.join(", ") }})
          </span>

          <span v-if="habit.frecuency === 'monthly'">
            day {{ habit.dayOfMonth }}
          </span>

          <span> {{ habit.streak }} streak</span>
          <span>Total: {{ habit.total_done }}</span>
          <span v-if="habit.done_today">✓ Done today</span>

          <button @click="deleteHabit(habit.id)">Delete</button>
        </li>
      </ul>
    </template>
  </div>
</template>
