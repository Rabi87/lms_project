// إدارة تأثيرات الـ Accordion
document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
        const accordionContent = header.nextElementSibling;
        document.querySelectorAll('.accordion-content').forEach(content => {
            if (content !== accordionContent) {
                content.style.display = 'none';
            }
        });
        if (accordionContent.style.display === 'block') {
            accordionContent.style.display = 'none';
        } else {
            accordionContent.style.display = 'block';
        }
    });
});

// وظيفة البحث
document.getElementById('searchInput').addEventListener('input', function () {
    const searchTerm = this.value.trim().toLowerCase();
    document.querySelectorAll('.card').forEach(card => {
        const cardTitle = card.getAttribute('data-title').toLowerCase();
        if (cardTitle.includes(searchTerm)) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
});

// وظيفة التصنيف
document.querySelectorAll('.filter-btn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        const category = button.getAttribute('data-category');
        document.querySelectorAll('.card').forEach(card => {
            const cardCategory = card.getAttribute('data-category');
            if (category === 'all' || cardCategory === category) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    });
});

// وظيفة فتح وإغلاق النافذة المنبثقة
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// إغلاق النافذة المنبثقة عند النقر خارجها
window.onclick = function (event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};


// تهيئة Swiper.js
const swiper = new Swiper('.mySwiper', {
    loop: true,
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
});