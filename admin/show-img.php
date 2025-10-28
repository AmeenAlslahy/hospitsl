<div class="card-body">
            <?php if (!empty($doctor['profile_picture']) && file_exists(UPLOADS_DIR . '/profiles/' . $doctor['profile_picture'])): ?>
                <div class="text-center mb-4">
                    <img src="<?php echo UPLOADS_PATH . '/profiles/' . htmlspecialchars($doctor['profile_picture']); ?>" 
                         class="rounded-circle" 
                         width="150" 
                         height="150" 
                         alt="صورة الطبيب"
                         onerror="this.src='<?php echo ASSETS_PATH; ?>/images/default-doctor.png'">
                </div>
            <?php else: ?>
                <div class="text-center mb-4">
                    <img src="<?php echo ASSETS_PATH; ?>/images/default-doctor.png" 
                         class="rounded-circle" 
                         width="150" 
                         height="150" 
                         alt="صورة افتراضية">
                </div>
            <?php endif; ?>