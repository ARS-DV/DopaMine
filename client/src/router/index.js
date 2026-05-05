import { createRouter, createWebHistory } from 'vue-router'
import Login from '@/views/login.vue'
import Singup from '@/views/singup.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    
     {
      path: '/login',
      name: 'login',
      component: Login,
    },
     {
      path: '/singup',
      name: 'singup',
      component: Singup,
    },
  ],
})

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
export default router
