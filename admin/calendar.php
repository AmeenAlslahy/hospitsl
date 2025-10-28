<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = new Auth();
// التحقق من صلاحيات المدير
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_PATH . "/login.php");
    exit();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-calendar py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>تقويم المواعيد</h1>
            <div>
                <a href="<?php echo BASE_PATH; ?>/admin/appointments.php" class="btn btn-secondary me-2">
                    <i class="fas fa-list me-2"></i>عرض القائمة
                </a>
                <a href="<?php echo BASE_PATH; ?>/admin/add-appointment.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>إضافة موعد
                </a>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS -->
<link href="<?php echo ASSETS_PATH; ?>/css/fullcalendar.min.css" rel="stylesheet">

<!-- FullCalendar JS -->
<script src="<?php echo ASSETS_PATH; ?>/js/fullcalendar.min.js"></script>
<script src="<?php echo ASSETS_PATH; ?>/js/locales/ar.js"></script>

<script>
$(document).ready(function() {
    $('#calendar').fullCalendar({
        locale: 'ar',
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        defaultView: 'month',
        navLinks: true,
        editable: true,
        eventLimit: true,
        events: '<?php echo BASE_PATH; ?>/ajax/get-calendar-events.php',
        eventClick: function(calEvent, jsEvent, view) {
            window.location.href = '<?php echo BASE_PATH; ?>/admin/view-appointment.php?id=' + calEvent.id;
        },
        eventRender: function(event, element) {
            element.find('.fc-title').html(event.title + ' - ' + event.patient);
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>