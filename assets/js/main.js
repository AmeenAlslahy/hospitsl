// // يمكن إضافة أي كود JavaScript عام هنا
// $(document).ready(function() {
//     // تفعيل عناصر التلميح
//     $('[data-toggle="tooltip"]').tooltip();
    
//     // تفعيل العناصر المنبثقة
//     $('[data-toggle="popover"]').popover();
    
//     // إخفاء التنبيهات تلقائيًا بعد 5 ثوان
//     setTimeout(function() {
//         $('.alert').fadeOut('slow');
//     }, 5000);
// });

// // دالة لتحديث التواريخ المحظورة في التقويم
// function updateDisabledDates() {
//     var today = new Date();
//     var dd = String(today.getDate()).padStart(2, '0');
//     var mm = String(today.getMonth() + 1).padStart(2, '0');
//     var yyyy = today.getFullYear();
    
//     today = yyyy + '-' + mm + '-' + dd;
//     $('input[type="date"]').attr('min', today);
// }

// // استدعاء الدالة عند تحميل الصفحة وعند تغيير التاريخ
// $(document).ready(updateDisabledDates);
// $(document).on('change', 'input[type="date"]', updateDisabledDates);


// كود JavaScript العام للتطبيق
$(document).ready(function() {
    // تفعيل عناصر التلميح
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // تفعيل العناصر المنبثقة
    $('[data-bs-toggle="popover"]').popover();
    
    // إخفاء التنبيهات تلقائيًا بعد 5 ثوان
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // التحقق من صحة كلمات المرور المتطابقة
    $('#confirm_password').on('keyup', function() {
        if ($(this).val() !== $('#password').val()) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // تعطيل تواريخ الماضي في انتقاء التاريخ
    $('input[type="date"]').attr('min', new Date().toISOString().split('T')[0]);
    
    // إضافة تأثيرات للبطاقات عند التحويم
    $('.card').hover(
        function() {
            $(this).addClass('shadow');
        },
        function() {
            $(this).removeClass('shadow');
        }
    );
    
    // إضافة تأثيرات للروابط في القوائم
    $('.nav-link').hover(
        function() {
            $(this).addClass('bg-light');
        },
        function() {
            $(this).removeClass('bg-light');
        }
    );
    
    // تحميل الأطباء حسب التخصص (AJAX مثال)
    $('#specialty').change(function() {
        var specialtyId = $(this).val();
        if (specialtyId) {
            $.ajax({
                url: BASE_PATH + '/ajax/get_doctors.php',
                method: 'GET',
                data: { specialty_id: specialtyId },
                success: function(data) {
                    $('#doctor').html(data).prop('disabled', false);
                }
            });
        } else {
            $('#doctor').html('<option value="">-- اختر الطبيب --</option>').prop('disabled', true);
        }
    });
    
    // تحميل الأوقات المتاحة حسب الطبيب والتاريخ (AJAX مثال)
    $('#doctor, #appointment_date').change(function() {
        var doctorId = $('#doctor').val();
        var date = $('#appointment_date').val();
        
        if (doctorId && date) {
            $.ajax({
                url: BASE_PATH + '/ajax/get_available_times.php',
                method: 'GET',
                data: { doctor_id: doctorId, date: date },
                success: function(data) {
                    $('#appointment_time').html(data).prop('disabled', false);
                }
            });
        } else {
            $('#appointment_time').html('<option value="">-- اختر الوقت --</option>').prop('disabled', true);
        }
    });
});

// دالة لعرض رسائل SweetAlert
function showAlert(type, title, message) {
    Swal.fire({
        icon: type,
        title: title,
        text: message,
        confirmButtonText: 'حسناً'
    });
}

// تعريف BASE_PATH للاستخدام في ملفات JavaScript
const BASE_PATH = '<?php echo BASE_PATH; ?>';