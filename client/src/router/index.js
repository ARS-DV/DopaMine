import { createRouter, createWebHistory } from "vue-router";
import Login from "@/views/login.vue";
import Singup from "@/views/singup.vue";
import HomeView from "@/views/HomeView.vue";
import TasksView from "@/views/TasksView.vue";
import HabitsView from "@/views/HabitsView.vue";
import RoutinesView from "@/views/RoutinesView.vue";
import CalendarView from "@/views/CalendarView.vue";
import ProgressView from "@/views/ProgressView.vue";
import NewHabitView from "@/views/NewHabitView.vue";
import NewTaskView from "@/views/NewTaskView.vue";
import NewRoutineView from "@/views/NewRoutineView.vue";
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/login",
      name: "login",
      component: Login,
    },
    {
      path: "/singup",
      name: "singup",
      component: Singup,
    },
    {
      path: "/",
      component: HomeView,
    },
    {
      path: "/tasks",
      component: TasksView,
    },
    {
      path: "/habits",
      component: HabitsView,
    },
    { 
      path: "/routines", 
      component: RoutinesView 
    },
    { 
      path: "/calendar", 
      component: CalendarView 
    },
    { 
      path: "/progress", 
      component: ProgressView 
    },
    {
      path: "/habits/new",
      name: "habits.new",
      component: NewHabitView,
      meta: { hideHeader: true },
    },
    { 
      path: '/tasks/new', 
      component: NewTaskView, 
      meta: { hideHeader: true } },
  { 
      path: '/routines/new', 
      component: NewRoutineView, 
      meta: { hideHeader: true } 
    },
    { path: '/calendar', component: CalendarView },
    { path: '/progress', component: ProgressView }
  ],
});

/*router.beforeEach((to, from, next) => {
  
  //preguntamos las rutas
  if (to.path == '/usuarios' || to.path == '/rutasadmin' || to.path == '/crearruta') {
    
    const datosSesion = JSON.parse(localStorage.getItem('sesion'));
    
    if (datosSesion && datosSesion.rol == 'admin') {
      next(); //puede entrar
    } else {
      alert("Acceso denegado pilluelo/a");
      next('/'); //vuelve a home
    }
  } else {
    next(); //si es cualquier otra puede entrar
  }

  if (to.path == '/asignaciones') {
    
    const datosSesion = JSON.parse(localStorage.getItem('sesion'));
    
    if (datosSesion && datosSesion.rol == 'guia') {
      next(); //puede entrar
    } else {
      alert("Acceso denegado pilluelo/a");
      next('/'); //vuelve a home
    }
  } else {
    next(); //si es cualquier otra puede entrar
  }
});*/
export default router;
