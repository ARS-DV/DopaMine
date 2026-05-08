<script setup>
import AppHeader from './components/AppHeader.vue'
import AppFooter from './components/AppFooter.vue'
</script>

<template>
    <div class="layout">
     <AppHeader v-if="!$route.meta.hideHeader"/>
      <RouterView/>
     <AppFooter/>
    </div>
</template>

<style scoped>
.layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
</style>
