<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require __DIR__ . '/includes/config.php';

?>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    text-decoration:none;
}


/* الحاوية الرئيسية */
.main-container {
    max-width: 1200px;
    margin: 0 auto 30px;
    padding: 0 15px;
    display: flex;
    gap: 20px;
    position: relative;
}

/* قسم السلايدر */
.slideshow-container {
    flex: 0 0 70%;
    height: 400px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.mySlides {
    display: none;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 1s ease-in-out;
}

.mySlides.active {
    display: block;
    opacity: 1;
}

.mySlides img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}



/* تجاوبية */
@media (max-width: 992px) {
    .main-container {
        flex-direction: column;
    }

    .slideshow-container,
    .side-cards {
        flex: 0 0 100%;
    }

/* أزرار التحكم */
.prev, .next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    padding: 16px;
    color: white;
    font-weight: bold;
    font-size: 20px;
    cursor: pointer;
    background: rgba(0,0,0,0.3);
    z-index: 100;
}

.next { right: 0; }
.prev { left: 0; }

/* المؤشرات */
.indicators {
    position: absolute;
    bottom: 15px;
    width: 100%;
    text-align: center;
    z-index: 100;
}

.indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    margin: 0 5px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
}

.indicator.active {
    background: white;
}
</style>


<!-- السلايدر + البطاقات الجانبية -->
<div class="main-container">
    <div class="slideshow-container">
        <?php
        $slides = $conn->query("SELECT * FROM slider_images WHERE is_active = 1");
        $first = true;
        while ($slide = $slides->fetch_assoc()):
        ?>
        <div class="mySlides <?= $first ? 'active' : '' ?>">
            <img src="<?= BASE_URL . $slide['image_path'] ?>" alt="Slider Image">
        </div>
        <?php $first = false; endwhile; ?>
    </div>
    <!-- أزرار التحكم -->
    <a class="prev" onclick="changeSlide(-1)">❮</a>
    <a class="next" onclick="changeSlide(1)">❯</a>
    
    <!-- المؤشرات -->
    <div class="indicators">
        <?php for ($i = 0; $i < $slides->num_rows; $i++): ?>
            <span class="indicator <?= $i === 0 ? 'active' : '' ?>" onclick="showSlide(<?= $i ?>)"></span>
        <?php endfor; ?>
    </div>

  
</div>



<script>

document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.mySlides');
    if (slides.length === 0) return;

    let currentIndex = 0;
    let slideInterval;

    function showSlide(index) {
        // إخفاء جميع الشرائح
        slides.forEach(slide => {
            slide.classList.remove('active');
        });
        
        // إظهار الشريحة المطلوبة
        slides[index].classList.add('active');
        currentIndex = index;
    }

    function nextSlide() {
        let nextIndex = (currentIndex + 1) % slides.length;
        showSlide(nextIndex);
    }

    function startAutoSlide() {
        if (slides.length > 1) {
            slideInterval = setInterval(nextSlide, 5000);
        }
    }

    function stopAutoSlide() {
        clearInterval(slideInterval);
    }

    // بدء التمرير التلقائي
    startAutoSlide();
    
    // إيقاف التمرير عند تحويم الماوس
    const sliderContainer = document.querySelector('.slideshow-container');
    if (sliderContainer) {
        sliderContainer.addEventListener('mouseenter', stopAutoSlide);
        sliderContainer.addEventListener('mouseleave', startAutoSlide);
    }
});
window.changeSlide = function(n) {
    stopAutoSlide();
    let newIndex = currentIndex + n;
    
    if (newIndex < 0) newIndex = slides.length - 1;
    else if (newIndex >= slides.length) newIndex = 0;
    
    showSlide(newIndex);
    startAutoSlide();
}

window.showSlide = function(index) {
    stopAutoSlide();
    showSlide(index);
    startAutoSlide();
}

</script>

