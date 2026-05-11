<script setup>
// imports para Vue, router y API
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

const userStore = useUserStore();
const router = useRouter();

//variables reactivas
const habits = ref([]);
const loading = ref(true);
const error = ref("");
const filter = ref("all");

//funcion principal
async function fetchHabits() {
  loading.value = true;
  error.value = "";
  let userId = userStore.user.id;

  fetch(rutaApi + "?entity=habits&user_id=" + userId)
    .then((response) => response.json())
    .then((data) => {
      habits.value = data;
      loading.value = false;
    })
    .catch((err) => {
      console.error(err);
      error.value = "Error loading habits";
      loading.value = false;
    });
}

// para cambiar el valor del estado del habito
 function doneLabel(val) {
  if (val == 2) {
    return "Done";
  } else if (val == 1) {
    return "Tried";
  } else {
    return "Pending";
  }
}

//funcion para cambiar el estado del habito
async function cycleState(habit) {
  //calculo del numero
  let currentState = habit.done_today;
  if (currentState == null || currentState == undefined) {
    currentState = 0;
  }
  
  let nextState = (parseInt(currentState) + 1) % 3;

  let updateData = { done: nextState };

  fetch(rutaApi + "?entity=habits&id=" + habit.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(updateData),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        habit.done_today = nextState;
        habit.streak = data.current_streak;
      }
    });
}

//funcion para borrar el habito
async function deleteHabit(id) {
  let confirmation = confirm("Delete this habit?");
  if (confirmation === false) {
    return;
  }

  fetch(rutaApi + "?entity=habits&id=" + id, {
    method: "DELETE",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        // se borra el estado 
        habits.value = habits.value.filter((h) => h.id !== id);
      } else {
        error.value = "Error deleting habit";
      }
    });
}

//filtro 
const filteredHabits = computed(() => {
  if (filter.value == "daily") {
    return habits.value.filter((h) => h.frecuency == "daily");
  } else if (filter.value == "weekly") {
    return habits.value.filter((h) => h.frecuency == "weekly");
  } else if (filter.value == "monthly") {
    return habits.value.filter((h) => h.frecuency == "monthly");
  } else if (filter.value == "done") {
    return habits.value.filter((h) => h.done_today == 2);
  } else {
    return habits.value;
  }
});

//funcion para comprobar si el habito toca hoy
function isTodayScheduled(habit) {
  let today = new Date();
  //se saca el dia de hoy en ingles
  let dayName = today.toLocaleString("en-US", { weekday: "long" }).toLowerCase();
  let dayOfMonth = today.getDate();

  if (habit.frecuency == "daily") {
    return true;
  }
  if (habit.frecuency == "weekly") {
    return habit.days && habit.days.includes(dayName);
  }
  if (habit.frecuency == "monthly") {
    return parseInt(habit.dayOfMonth) === dayOfMonth;
  }
  return false;
}

//al cargar la pagina automaticamente llamamos a la funcion de habito
onMounted(() => {
  fetchHabits();
});
</script>

<template>
  <div>
    <h1>Habits</h1>

    <p v-if="error">{{ error }}</p>
    <p v-if="loading">Loading...</p>

    <template v-else>
      <button @click="router.push('/habits/new')">+ New Habit</button>

      <div>
        <button @click="filter = 'all'">All ({{ habits.length }})</button>
        <button @click="filter = 'daily'">Daily</button>
        <button @click="filter = 'weekly'">Weekly</button>
        <button @click="filter = 'monthly'">Monthly</button>
        <button @click="filter = 'done'">Done today</button>
      </div>

      <p v-if="filteredHabits.length == 0">No habits found</p>

      <ul v-else>
        <li v-for="habit in filteredHabits" :key="habit.id">
          <button @click="cycleState(habit)">
            {{ doneLabel(habit.done_today) }}
          </button>
          
          <span v-if="isTodayScheduled(habit) == false">(not today)</span>

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
          
          <span v-if="habit.done_today == 2">✓ Done today</span>

          <button @click="deleteHabit(habit.id)">Delete</button>
        </li>
      </ul>
    </template>
  </div>
</template>