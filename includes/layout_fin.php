        </section>
    </main>
</div>

<!-- Modal de confirmación para cerrar sesión -->
<div class="modal fade" id="modalLogout" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body p-4">
                <div class="modal-icono"><i class="bi bi-box-arrow-right"></i></div>
                <h5 class="mb-1">¿Cerrar sesión?</h5>
                <p class="text-muted small mb-4">Tendrás que ingresar de nuevo para continuar.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-accent" id="confirmarLogout" data-url="<?= $BASE ?>logout.php">Salir</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($mensaje !== ''): ?>
    <!-- Notificación (toast) con el resultado de la última acción -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:3000">
        <div class="toast toast-app toast-<?= $tipoMensaje === 'error' ? 'error' : 'exito' ?>" role="alert" data-bs-delay="6000">
            <div class="d-flex align-items-start gap-3 p-3">
                <i class="bi <?= $tipoMensaje === 'error' ? 'bi-exclamation-octagon' : 'bi-check2-circle' ?> fs-4"></i>
                <div class="flex-grow-1 small"><?= h($mensaje) ?></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="<?= $BASE ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= $BASE ?>js/app.js"></script>
</body>
</html>
