<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Location';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<section class="container py-5">
    <div class="page-header">
        <span class="eyebrow-text">Find Us</span>
        <h1 class="section-heading">Visit Us</h1>
    </div>

    <div class="row g-4 fade-in-section">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5><i class="bi bi-geo-alt me-2"></i>Address</h5>
                    <p class="text-muted">
                        123 Jalan Kopi,<br>
                        Taman Aroma,<br>
                        50000 Kuala Lumpur,<br>
                        Malaysia
                    </p>

                    <h5 class="mt-4"><i class="bi bi-clock me-2"></i>Operating Hours</h5>
                    <ul class="list-unstyled text-muted mb-0">
                        <li class="d-flex justify-content-between"><span>Monday – Friday</span><span>7:00 AM – 9:00 PM</span></li>
                        <li class="d-flex justify-content-between"><span>Saturday – Sunday</span><span>8:00 AM – 10:00 PM</span></li>
                        <li class="d-flex justify-content-between"><span>Public Holidays</span><span>9:00 AM – 6:00 PM</span></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="ratio ratio-4x3 shadow-sm rounded overflow-hidden">
                <iframe
                    src="https://www.google.com/maps?q=Kuala+Lumpur+City+Centre&output=embed"
                    style="border:0;" allowfullscreen loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
