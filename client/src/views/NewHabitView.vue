<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

// inicializar las herramientas de Pinia y router
const userStore = useUserStore();
const router = useRouter();

//variables para el formulario
const title = ref("");
const description = ref("");
const icon = ref("");
const frecuency = ref("daily");
const dayOfMonth = ref(null);
const selectedDays = ref([]); // array para los dias de la senana

const error = ref(""); //variable para errores

//array de los dias de la semana para el v for
const daysList = [
  "monday", "tuesday", "wednesday", "thursday", 
  "friday", "saturday", "sunday"
];

//array con emojis
const emojis = ["🔄", "💧", "🏃", "📚", "🧘", "🥗", "💊", "🛌", "🧹", "💼"];

//funcion para seleccionar o desmarcar dias
function selectDay(day) {
  let position = selectedDays.value.indexOf(day);
  
  if (position == -1) {
    //si no está, se mete
    selectedDays.value.push(day);
  } else {
    //si está, lo quitamos
    selectedDays.value.splice(position, 1);
  }
}

//funcion principal para guardar el habito
async function saveHabit() {
  error.value = "";

  //validaciones
  if (title.value == "") {
    error.value = "Title is required";
    return;
  }
  if (frecuency.value == "weekly" && selectedDays.value.length == 0) {
    error.value = "You must choose a day of the week";
    return;
  }

  //variable que contiene los datos a pasar al backend para su procesamiento
  let habitData = {
    user_id: userStore.user.id, //para el id se saca de Pinia
    title: title.value,
    descrip: description.value,
    icon: icon.value,
    frecuency: frecuency.value,
    dayOfMonth: frecuency.value == "monthly" ? dayOfMonth.value : null,
    days: frecuency.value == "weekly" ? selectedDays.value : []
  };

  // peticion post con fetch
  fetch(rutaApi + "?entity=habits", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(habitData)
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        alert("Habit successfully created!");
        router.push("/habits"); //volvemos a la lista
      } else {
        error.value = "Error: " + data.message;
      }
    })
    .catch((err) => {
      console.error("Request error", err);
      error.value = "Unable to connect to the server";
    });
}
</script>

<template>
  <div class="container">
    <p>
      <router-link to="/">Today</router-link> > 
      <router-link to="/habits">Habits</router-link> > 
      <span>New habit</span>
    </p>

    <h1>Create a new habit</h1>

    <div v-if="error" style="color: red; font-weight: bold; margin-bottom: 10px;">
      {{ error }}
    </div>

    <form @submit.prevent="saveHabit">
      <div>
        <label>Habit name *</label><br>
        <input v-model="title" type="text" placeholder="Ej: Drink water...">
      </div>

      <br>

      <div>
        <label>Description</label><br>
        <input v-model="description" type="text">
      </div>

      <br>

      <div>
        <label>Select an icon:</label>
        <div class="emoji-list">
          <button 
            v-for="e in emojis" 
            :key="e" 
            type="button"
            @click="icon = e"
            :style="icon == e ? 'background: lightblue' : ''"
          >
            {{ e }}
          </button>
        </div>
        <p>Seleccionado: {{ icon }}</p>
      </div>

      <br>

      <div>
        <label>How often? *</label><br>
        <select v-model="frecuency">
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
        </select>
      </div>

      <br>

      <div v-if="frecuency == 'weekly'">
        <label>Days of the week:</label><br>
        <div v-for="day in daysList" :key="day" style="display: inline-block; margin-right: 10px;">
          <input 
            type="checkbox" 
            :value="day" 
            @change="selectDay(day)"
          >
          {{ day }}
        </div>
      </div>
      <!--TODO : Validar febrero-->
      <div v-if="frecuency == 'monthly'">
        <label>Day of the month (1-31):</label><br>
        <input v-model="dayOfMonth" type="number" min="1" max="31">
      </div>

      <br><br>

      <button type="submit">Create habit</button>
      <button type="button" @click="router.push('/habits')">Cancel</button>
    </form>
  </div>
</template>

