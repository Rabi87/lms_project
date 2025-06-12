<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
?>

<?php if (isset($_SESSION['error'])): ?>
<script>
Swal.fire({
    icon: 'warning',
    title: 'انتبه.. !',
    text: '<?= $_SESSION['error'] ?>'
});
</script>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">تقديم شكوى</div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>process.php" method="POST" id="complaintForm">
                        
                        <div class="mb-3">
                            <?php if (isset($_SESSION['user_email'])): ?>
                                <input type="email" class="form-control" name="email" 
                                       value="<?= htmlspecialchars($_SESSION['user_email']) ?>" 
                                       readonly>
                            <?php else: ?>
                                <input type="email" class="form-control" name="email" 
                                       placeholder="البريد الإلكتروني" required>
                            <?php endif; ?>
                            
                        </div>
                        
                        <!-- حقل نوع الشكوى -->
                        <div class="mb-3">
                            <label class="form-label">نوع الشكوى</label>
                            <select class="form-select" id="complaintType" name="complaint_type" onchange="showAdditionalFields()" required>
                                <option value="">-- اختر نوع الشكوى --</option>
                                <option value="account">مشكلة في الحساب</option>
                                <option value="payment">مشكلة في عملية الدفع</option>
                                <option value="book">كتاب غير متوفر</option>
                                <option value="borrow">مشكلة في الاستعارة</option>
                                <option value="other">شكوى أخرى</option>
                            </select>
                        </div>
                        
                        <!-- حقول إضافية حسب نوع الشكوى -->
                        <div id="additionalFields"></div>
                        <input type="hidden" name="problem_type" id="problemType" value="">
                        
                        <!-- حقل نص الشكوى -->
                        <div class="mb-3">
                            <label class="form-label">تفاصيل الشكوى</label>
                            <textarea class="form-control" name="complaint_details" rows="5" 
                                      placeholder="وصف المشكلة بالتفصيل" required></textarea>
                        </div>
                        
                        <button type="submit" name="submit_complaint" class="btn btn-primary">إرسال</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const problemTypeFields = {
    account: 'account_issue_type',
    quality: 'quality_issue_type'
};
function showAdditionalFields() {
    const type = document.getElementById('complaintType').value;
    const container = document.getElementById('additionalFields');
    
    if (type && complaintFields[type]) {
        container.innerHTML = complaintFields[type];
    } else {
        container.innerHTML = '';
    }
    
    // إعادة تعيين حقل المشكلة الفرعية
    document.getElementById('problemType').value = '';
}

// دالة جديدة لتحديث حقل المشكلة الفرعية
function updateProblemType() {
    const complaintType = document.getElementById('complaintType').value;
    const problemTypeField = problemTypeFields[complaintType];
    const problemTypeValue = problemTypeField 
        ? document.querySelector(`[name="${problemTypeField}"]`)?.value 
        : '';
    
    document.getElementById('problemType').value = problemTypeValue || '';
}

// تحديث الحقول عند تغيير أي مدخل
document.addEventListener('change', function(e) {
    if (e.target.name === 'complaint_type' || 
        e.target.name === 'account_issue_type' || 
        e.target.name === 'quality_issue_type') {
        updateProblemType();
    }
});
// حقول إضافية لكل نوع شكوى
const complaintFields = {
    account: `
        <div class="mb-3" name="account_issue_type">
            <label class="form-label">نوع المشكلة</label>
            <select class="form-select" name="account_issue_type" id="accountIssueType" onchange="toggleLastLoginField()" required>
                <option value="">-- اختر --</option>
                <option value="creation">مشكلة تسجيل الحساب</option>
                <option value="login">مشكلة تسجيل الدخول</option>
                <option value="activation">تفعيل الحساب</option>
                <option value="update">تحديث المعلومات</option>
                <option value="password">استعادة كلمة المرور</option>
            </select>
        </div>
        <div class="mb-3" id="lastLoginField">
            <label class="form-label">تاريخ آخر وصول ناجح</label>
            <input type="date" class="form-control" name="last_success_login">
        </div>
    `,
    
    payment: `
        <div class="mb-3">
            <label class="form-label">رقم العملية</label>
            <input type="text" class="form-control" name="transaction_id" required>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">طريقة الدفع</label>
                <select class="form-select" name="payment_method" required>
                    <option value="">-- اختر --</option>
                    <option value="credit_card">بطاقة ائتمانية</option>
                    <option value="apple_pay">Apple Pay</option>
                    <option value="paypal">PayPal</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">المبلغ (ل.س)</label>
                <input type="number" class="form-control" name="amount" step="0.01" required>
            </div>
        </div>
    `,
    
    book: `
        <div class="mb-3">
            <label class="form-label">اسم الكتاب</label>
            <input type="text" class="form-control" name="book_title" required>
        </div>
        <div class="mb-3">
            <label class="form-label">المؤلف</label>
            <input type="text" class="form-control" name="book_author">
        </div>
        <div class="mb-3">
            <label class="form-label">رقم ISBN</label>
            <input type="text" class="form-control" name="isbn">
        </div>
    `,
    
    borrow: `
        <div class="mb-3">
            <label class="form-label">اسم الكتاب المستعار</label>
            <input type="text" class="form-control" name="borrowed_book" required>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">تاريخ الاستعارة</label>
                <input type="date" class="form-control" name="borrow_date" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">تاريخ الاستحقاق</label>
                <input type="date" class="form-control" name="due_date" required>
            </div>
        </div>
    `,
    
    
    
    other: ''
};
// إظهار/إخفاء حقل آخر وصول ناجح بناءً على نوع المشكلة
function toggleLastLoginField() {
    const issueType = document.getElementById('accountIssueType')?.value;
    const lastLoginField = document.getElementById('lastLoginField');
    
    if (lastLoginField) {
        if (issueType === 'creation') {
            lastLoginField.style.display = 'none';
        } else {
            lastLoginField.style.display = 'block';
        }
    }
}
// تحديث دالة updateProblemType
function updateProblemType() {
    const complaintType = document.getElementById('complaintType').value;
    let problemTypeValue = '';
    
    if (complaintType === 'account') {
        const accountIssue = document.querySelector('[name="account_issue_type"]');
        problemTypeValue = accountIssue ? accountIssue.value : '';
    } 
    else if (complaintType === 'quality') {
        const qualityIssue = document.querySelector('[name="quality_issue_type"]');
        problemTypeValue = qualityIssue ? qualityIssue.value : '';
    }
    
    document.getElementById('problemType').value = problemTypeValue;
}

// إضافة هذا الحدث لضمان تحديث الحقل المخفي
document.addEventListener('DOMContentLoaded', function() {
    showAdditionalFields();
    
    document.body.addEventListener('change', function(e) {
        if (e.target.name === 'account_issue_type' || 
            e.target.name === 'quality_issue_type') {
            updateProblemType();
        }
    });
});

function showAdditionalFields() {
    const type = document.getElementById('complaintType').value;
    const container = document.getElementById('additionalFields');
    
    if (type && complaintFields[type]) {
        container.innerHTML = complaintFields[type];
    } else {
        container.innerHTML = '';
    }
    
    // تحديث حقل المشكلة الفرعية فوراً
    updateProblemType();
}


// تحديث الحقل المخفي عند تغيير أي إدخال
document.addEventListener('change', function(e) {
    if (e.target.name === 'complaint_type' || 
        e.target.name === 'account_issue_type' || 
        e.target.name === 'quality_issue_type') {
        updateProblemType();
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>