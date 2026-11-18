</main>
<footer class="ar-footer mt-5">
  <div class="container py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-8">
        <p class="mb-1 fw-semibold">© 2026 AgroRed Hidalgo · Equipo Nexosoft · ITSOEH</p>
        <p class="mb-0 small opacity-75">Proyecto Integrador 5° Semestre · Ingeniería en Sistemas Computacionales</p>
      </div>
      <div class="col-md-4 text-md-end small opacity-75">
        <i class="bi bi-globe-americas"></i> ODS 2 Hambre Cero · ODS 12 Producción y Consumo Responsables
      </div>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($leaflet)): ?><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><?php endif; ?>
<script src="<?= url('assets/js/agrored.js') ?>"></script>
<?php foreach (($scripts ?? []) as $s): ?><script src="<?= url($s) ?>"></script><?php endforeach; ?>
</body>
</html>
