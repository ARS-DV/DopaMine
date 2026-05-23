<script setup>
</script>

<template>
  <footer class="app-footer">
    <div class="footer-inner">

      <!-- marca -->
      <div class="footer-brand">
        DopaMine
      </div>

      <!-- links -->
      <div class="footer-links">
        <a href="#" class="footer-link">Privacy</a>
        <a href="#" class="footer-link">Terms</a>
        <a href="#" class="footer-link">Help</a>
      </div>

      <!-- COPYRIGHT -->
      <div class="footer-copy">
        &copy; 2026 Dopamine. All rights reserved.
      </div>

    </div>
  </footer>
</template>

<style scoped>

.footer-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  height: 100%;
}

.footer-links {
  display: flex;
  gap: 1.5rem;
}

@media (max-width: 576px) {
  .footer-inner {
    padding: 0 1rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>